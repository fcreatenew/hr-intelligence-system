from __future__ import annotations

import numpy as np
import pytest

from src.data.dataset import (
    build_features,
    encode,
    load_turnover,
    prepare_dataset,
    standardize,
    train_test_split,
)
from src.evaluation.baseline import baseline_turnover
from src.evaluation.metrics import (
    best_threshold,
    evaluate_binary,
    macro_f1,
    roc_auc,
)
from src.experiments.tracker import ExperimentTracker
from src.models.mlp import MLP, relu, sigmoid, softmax
from src.training.trainer import train_turnover_mlp


# ------------------------------------------------------------------ lapisan

def test_activations_ranges():
    x = np.array([-2.0, 0.0, 3.0])

    assert np.allclose(relu(x), [0.0, 0.0, 3.0])
    assert sigmoid(np.array([0.0]))[0] == pytest.approx(0.5)

    probs = softmax(np.array([[1.0, 2.0, 3.0]]))
    assert probs.sum() == pytest.approx(1.0)
    assert probs.argmax() == 2


def test_mlp_forward_shapes():
    model = MLP(n_inputs=5, hidden_sizes=(8, 4), n_outputs=1, seed=0)
    out = model.predict_proba(np.zeros((3, 5)))

    assert out.shape == (3, 1)
    assert np.all((out >= 0) & (out <= 1))


def test_mlp_learns_xor_ish_pattern():
    """MLP harus mampu memisahkan pola non-linear sederhana."""
    rng = np.random.default_rng(0)
    x = rng.normal(size=(400, 2))
    y = (x[:, 0] * x[:, 1] > 0).astype(float)

    model = MLP(n_inputs=2, hidden_sizes=(16, 8), n_outputs=1, learning_rate=0.1, seed=1)
    model.fit(x, y, epochs=400, batch_size=32, patience=400)

    acc = (model.predict(x) == y).mean()
    assert acc > 0.85


def test_mlp_serialization_roundtrip():
    model = MLP(n_inputs=4, hidden_sizes=(6,), n_outputs=1, seed=2)
    x = np.random.default_rng(0).normal(size=(5, 4))
    before = model.predict_proba(x)

    restored = MLP.from_dict(model.to_dict())
    after = restored.predict_proba(x)

    assert np.allclose(before, after)


def test_mlp_multiclass_output():
    model = MLP(n_inputs=3, hidden_sizes=(6,), n_outputs=3, seed=3)
    proba = model.predict_proba(np.zeros((4, 3)))

    assert proba.shape == (4, 3)
    assert np.allclose(proba.sum(axis=1), 1.0)


# -------------------------------------------------------------------- data

def test_feature_contract_matches_api():
    """Fitur harus memakai kontrak yang sama dengan hr_intelligence_api."""
    df = load_turnover()
    fitur = build_features(df)

    expected = {
        "usia",
        "masa_kerja_bulan",
        "jam_lembur_bulanan",
        "log_gaji",
        "usia_saat_masuk",
        "ada_promosi",
        "bulan_sejak_promosi",
        "bulan_hingga_promosi",
        "departemen",
        "status_kerja",
        "pendidikan",
    }
    assert set(fitur.columns) == expected


def test_encode_produces_numeric_matrix():
    df = load_turnover().head(50)
    x, columns = encode(build_features(df))

    assert x.dtype == float
    assert x.shape[0] == 50
    assert len(columns) == x.shape[1]
    assert np.isfinite(x).all()


def test_prepare_dataset_shapes():
    x, y, columns = prepare_dataset()

    assert x.shape[0] == y.shape[0] == len(load_turnover())
    assert len(columns) == x.shape[1]


def test_standardize_uses_train_stats_only():
    x_train = np.array([[0.0], [2.0]])
    x_test = np.array([[10.0]])

    _, x_test_scaled, stats = standardize(x_train, x_test)

    assert stats["mean"] == [1.0]
    assert x_test_scaled[0, 0] == pytest.approx((10.0 - 1.0) / 1.0)


def test_train_test_split_is_disjoint():
    x = np.arange(100).reshape(-1, 1).astype(float)
    y = np.arange(100)
    x_tr, x_te, y_tr, y_te = train_test_split(x, y, 0.2, seed=7)

    assert len(x_tr) == 80 and len(x_te) == 20
    assert set(y_tr).isdisjoint(set(y_te))


# ----------------------------------------------------------------- metrik

def test_roc_auc_perfect_and_random():
    y = np.array([0, 0, 1, 1])

    assert roc_auc(y, np.array([0.1, 0.2, 0.8, 0.9])) == pytest.approx(1.0)
    assert roc_auc(y, np.array([0.5, 0.5, 0.5, 0.5])) == pytest.approx(0.5)


def test_evaluate_binary_keys():
    y = np.array([0, 1, 1, 0])
    proba = np.array([0.2, 0.7, 0.6, 0.4])
    hasil = evaluate_binary(y, proba, threshold=0.5)

    assert set(hasil) >= {"accuracy", "precision", "recall", "f1", "roc_auc"}
    assert hasil["accuracy"] == pytest.approx(1.0)


def test_macro_f1_multiclass():
    y = np.array([0, 1, 2, 2])
    pred = np.array([0, 1, 2, 0])

    assert 0.0 <= macro_f1(y, pred) <= 1.0


def test_best_threshold_returns_reasonable_value():
    y = np.array([0, 0, 1, 1])
    proba = np.array([0.1, 0.3, 0.6, 0.9])
    threshold, score = best_threshold(y, proba)

    assert 0.05 <= threshold <= 0.95
    assert score > 0.9


# ---------------------------------------------------------------- training

def test_train_turnover_mlp_runs_and_scores():
    result = train_turnover_mlp(epochs=60, seed=42)

    assert 0.0 <= result.metrics["roc_auc"] <= 1.0
    assert result.metrics["roc_auc"] > 0.55
    assert result.threshold is not None
    assert result.columns
    assert len(result.history["loss"]) > 0


def test_baseline_turnover_beats_majority_on_auc():
    hasil = baseline_turnover(seed=42)

    assert hasil["logistic_regression"]["roc_auc"] > 0.6
    assert hasil["logistic_regression"]["roc_auc"] > hasil["majority_class"]["roc_auc"]


def test_experiment_tracker_roundtrip(tmp_path):
    tracker = ExperimentTracker(base_dir=tmp_path)
    tracker.log("uji", {"roc_auc": 0.7}, {"seed": 1})

    runs = tracker.list_runs()

    assert len(runs) == 1
    assert runs[0]["metrics"]["roc_auc"] == 0.7
    assert tracker.best("roc_auc")["nama"] == "uji"

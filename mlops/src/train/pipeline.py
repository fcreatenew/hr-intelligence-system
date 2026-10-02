"""Pipeline training reproducible untuk model turnover.

Modul ini adalah sumber kebenaran tunggal untuk langkah-langkah training:
pemuatan data, rekayasa fitur, pemilihan model, kalibrasi ambang, dan
penulisan artifact. Notebook boleh memakainya untuk eksplorasi, tetapi
artifact yang dipakai API hanya boleh dihasilkan dari sini agar dapat
direproduksi dan divalidasi `mlops` gate.

Kontrak fitur harus identik dengan `hr_intelligence_api/app/features/features.py`
dan notebook training (lihat AGENTS.md).
"""

from __future__ import annotations

import json
import random
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path

import numpy as np
import pandas as pd
import joblib
from sklearn.base import clone
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import (
    HistGradientBoostingClassifier,
    RandomForestClassifier,
    VotingClassifier,
)
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score,
    average_precision_score,
    brier_score_loss,
    f1_score,
    precision_score,
    recall_score,
    roc_auc_score,
)
from sklearn.model_selection import StratifiedKFold, cross_val_predict, train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

from src.config import DATA_DIR, MODELS_DIR

# ---------------------------------------------------------------------------
# Kontrak fitur (harus sama dengan features.py dan notebook)
# ---------------------------------------------------------------------------
NUM_COLS = [
    "usia",
    "masa_kerja_bulan",
    "jam_lembur_bulanan",
    "log_gaji",
    "usia_saat_masuk",
    "ada_promosi",
    "bulan_sejak_promosi",
    "bulan_hingga_promosi",
]
CAT_COLS = ["departemen", "status_kerja", "pendidikan"]
FEATURES = NUM_COLS + CAT_COLS

RAW_COLUMNS_REQUIRED = [
    "usia",
    "masa_kerja_bulan",
    "jam_lembur_bulanan",
    "gaji_bulanan",
    "tanggal_masuk",
    "tanggal_promosi",
    "departemen",
    "status_kerja",
    "pendidikan",
]

TARGET = "target_turnover"
REF_DATE = pd.Timestamp("2025-01-01")
SEED = 42
TEST_SIZE = 0.2


@dataclass(frozen=True)
class TrainingConfig:
    seed: int = SEED
    test_size: float = TEST_SIZE
    ref_date: str = "2025-01-01"
    data_file: str = "hr_turnover.csv"
    version: str = "v2"
    min_roc_auc: float = 0.6

    @property
    def ref_timestamp(self) -> pd.Timestamp:
        return pd.Timestamp(self.ref_date)


def set_seed(seed: int) -> None:
    random.seed(seed)
    np.random.seed(seed)


def load_data(data_path: Path, ref_date: pd.Timestamp) -> pd.DataFrame:
    if not data_path.exists():
        raise FileNotFoundError(f"Dataset tidak ditemukan: {data_path}")

    df = pd.read_csv(
        data_path,
        parse_dates=["tanggal_masuk", "tanggal_promosi"],
    )

    kurang = [c for c in RAW_COLUMNS_REQUIRED + [TARGET] if c not in df.columns]
    if kurang:
        raise ValueError(f"Kolom wajib belum ada di dataset: {kurang}")

    # Data masa depan akan membocorkan informasi; buang dengan peringatan.
    df = df[df["tanggal_masuk"] <= ref_date].reset_index(drop=True)

    return df


def buat_fitur(df: pd.DataFrame, ref_date: pd.Timestamp) -> pd.DataFrame:
    d = df.copy()

    masuk = pd.to_datetime(d["tanggal_masuk"])
    promosi = pd.to_datetime(d["tanggal_promosi"])

    d["ada_promosi"] = promosi.notna().astype(float)
    d["bulan_sejak_promosi"] = (ref_date - promosi).dt.days / 30.44
    d["bulan_hingga_promosi"] = (promosi - masuk).dt.days / 30.44
    d["usia_saat_masuk"] = d["usia"] - d["masa_kerja_bulan"] / 12
    d["log_gaji"] = np.log1p(d["gaji_bulanan"])

    fitur = d[FEATURES].copy()
    fitur[NUM_COLS] = fitur[NUM_COLS].astype(float)

    return fitur


def make_preprocessor() -> ColumnTransformer:
    numeric = Pipeline(
        [
            ("imputer", SimpleImputer(strategy="median")),
            ("scaler", StandardScaler()),
        ]
    )
    categorical = OneHotEncoder(handle_unknown="ignore")

    return ColumnTransformer(
        [
            ("num", numeric, NUM_COLS),
            ("cat", categorical, CAT_COLS),
        ]
    )


def build_models(seed: int = SEED) -> dict:
    return {
        "Logistic Regression": LogisticRegression(max_iter=2000, random_state=seed),
        "Random Forest": RandomForestClassifier(
            n_estimators=400,
            min_samples_leaf=3,
            random_state=seed,
            n_jobs=1,
        ),
        "Hist Gradient Boosting": HistGradientBoostingClassifier(random_state=seed),
    }


def to_pipeline(estimator) -> Pipeline:
    return Pipeline([("prep", make_preprocessor()), ("clf", estimator)])


def _cv_roc_auc(pipeline: Pipeline, X: pd.DataFrame, y: pd.Series, seed: int) -> float:
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=seed)
    proba = cross_val_predict(pipeline, X, y, cv=cv, method="predict_proba", n_jobs=1)[:, 1]

    return float(roc_auc_score(y, proba))


def best_threshold(y_true: pd.Series, proba: np.ndarray) -> float:
    thresholds = np.arange(0.05, 0.96, 0.01)
    f1_scores = [f1_score(y_true, (proba >= t).astype(int), zero_division=0) for t in thresholds]

    return round(float(thresholds[int(np.argmax(f1_scores))]), 2)


def evaluate(y_true, proba: np.ndarray, threshold: float) -> dict:
    pred = (proba >= threshold).astype(int)

    return {
        "roc_auc": float(roc_auc_score(y_true, proba)),
        "pr_auc": float(average_precision_score(y_true, proba)),
        "brier": float(brier_score_loss(y_true, proba)),
        "accuracy": float(accuracy_score(y_true, pred)),
        "precision": float(precision_score(y_true, pred, zero_division=0)),
        "recall": float(recall_score(y_true, pred, zero_division=0)),
        "f1": float(f1_score(y_true, pred, zero_division=0)),
    }


def train(
    config: TrainingConfig,
    data_dir: Path = DATA_DIR,
    models_dir: Path = MODELS_DIR,
) -> dict:
    """Latih model turnover dan kembalikan metadata hasil training.

    Menghasilkan dua artifact di `models_dir/<version>/`:
    `turnover_model.joblib` dan `model_metadata.json`. Fungsi ini deterministik
    untuk seed yang sama, sehingga gate validasi dapat membandingkan hasil
    training ulang.
    """
    set_seed(config.seed)

    df = load_data(data_dir / config.data_file, config.ref_timestamp)
    X = buat_fitur(df, config.ref_timestamp)
    y = df[TARGET].astype(int)

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=config.test_size, stratify=y, random_state=config.seed
    )

    candidates: dict[str, Pipeline] = {}
    cv_scores: dict[str, float] = {}

    for name, estimator in build_models(config.seed).items():
        pipeline = to_pipeline(estimator)
        cv_scores[name] = _cv_roc_auc(pipeline, X_train, y_train, config.seed)
        candidates[name] = pipeline

    champion_name = max(cv_scores, key=cv_scores.get)
    champion = candidates[champion_name]

    # Soft voting dari seluruh kandidat: rata-rata probabilitas mengurangi
    # varians tanpa memerlukan tuning terpisah yang mahal.
    voter = VotingClassifier(
        estimators=[(name, clone(p.named_steps["clf"])) for name, p in candidates.items()],
        voting="soft",
    )
    voting_pipe = Pipeline([("prep", make_preprocessor()), ("clf", voter)])
    voting_score = _cv_roc_auc(voting_pipe, X_train, y_train, config.seed)

    if voting_score >= cv_scores[champion_name]:
        champion_name = "Soft Voting (ensemble)"
        champion = voting_pipe
        cv_scores[champion_name] = voting_score

    # Ambang dipilih dari prediksi out-of-fold pada data train, bukan dari test,
    # agar test set tetap bersih untuk evaluasi akhir.
    cv = StratifiedKFold(n_splits=5, shuffle=True, random_state=config.seed)
    oof = cross_val_predict(champion, X_train, y_train, cv=cv, method="predict_proba", n_jobs=1)[:, 1]
    threshold = best_threshold(y_train, oof)

    # Refit pada seluruh data agar model final memakai semua informasi, lalu
    # metrik dilaporkan dari holdout test yang belum pernah dilihat model.
    test_proba = champion.fit(X_train, y_train).predict_proba(X_test)[:, 1]
    metrics = evaluate(y_test, test_proba, threshold)

    model_final = clone(champion).fit(X, y)

    artifact_dir = models_dir / config.version
    artifact_dir.mkdir(parents=True, exist_ok=True)

    model_path = artifact_dir / "turnover_model.joblib"
    joblib.dump(model_final, model_path)

    metadata = {
        "versi_model": config.version,
        "dibuat_pada": datetime.now().isoformat(timespec="seconds"),
        "tujuan": "Prediksi turnover karyawan (1 = keluar)",
        "sumber_data": config.data_file,
        "jumlah_baris": int(len(df)),
        "rasio_positif": round(float(y.mean()), 4),
        "model": champion_name,
        "metode": "cli_train",
        "threshold": threshold,
        "refit_seluruh_data": True,
        "fitur_numerik": NUM_COLS,
        "fitur_kategorik": CAT_COLS,
        "kolom_mentah_dibutuhkan": RAW_COLUMNS_REQUIRED,
        "tanggal_acuan": config.ref_date,
        "random_state": config.seed,
        "test_size": config.test_size,
        "metrik_cv_roc_auc": {name: round(score, 4) for name, score in cv_scores.items()},
        "metrik_holdout_test": {key: round(value, 4) for key, value in metrics.items()},
    }

    (artifact_dir / "model_metadata.json").write_text(
        json.dumps(metadata, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    return {
        "artifact_dir": str(artifact_dir),
        "model_path": str(model_path),
        "champion": champion_name,
        "threshold": threshold,
        "metrics": metrics,
        "cv_roc_auc": cv_scores,
        "jumlah_baris": int(len(df)),
    }

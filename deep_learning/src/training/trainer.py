"""Loop pelatihan Deep Learning yang dapat direproduksi.

Menggabungkan pemuatan data, standarisasi, pelatihan MLP, evaluasi, dan
pencatatan eksperimen. Semua hasil disimpan agar dapat dibandingkan antar run.
"""

from __future__ import annotations

from dataclasses import dataclass, field

import numpy as np

from src.config import (
    BATCH_SIZE,
    EPOCHS,
    HIDDEN_SIZES,
    LEARNING_RATE,
    PATIENCE,
    SEED,
    VAL_FRACTION,
)
from src.data.dataset import (
    build_features,
    encode,
    load_turnover,
    standardize,
    train_test_split,
)
from src.evaluation.metrics import best_threshold, evaluate_binary
from src.models.mlp import MLP


@dataclass
class TrainResult:
    model: MLP
    metrics: dict
    history: dict
    columns: list[str]
    stats: dict
    threshold: float
    config: dict = field(default_factory=dict)

    def to_dict(self) -> dict:
        return {
            "metrics": self.metrics,
            "threshold": self.threshold,
            "columns": self.columns,
            "stats": self.stats,
            "config": self.config,
            "history": self.history,
        }


def train_turnover_mlp(
    data_path=None,
    hidden_sizes: tuple[int, ...] = HIDDEN_SIZES,
    learning_rate: float = LEARNING_RATE,
    epochs: int = EPOCHS,
    batch_size: int = BATCH_SIZE,
    patience: int = PATIENCE,
    val_fraction: float = VAL_FRACTION,
    dropout: float = 0.0,
    seed: int = SEED,
    verbose: bool = False,
) -> TrainResult:
    """Latih MLP untuk prediksi turnover.

    Alur: muat data → bangun fitur (kontrak API) → one-hot → split →
    standarisasi (statistik dari train) → latih → evaluasi pada test.
    """
    df = load_turnover(data_path)
    fitur = build_features(df)
    x, columns = encode(fitur)
    y = df["target_turnover"].to_numpy(dtype=float)

    x_train, x_test, y_train, y_test = train_test_split(x, y, 0.2, seed)

    # Acak ulang sebelum memisahkan validasi: data mentah terurut per kelas,
    # sehingga potongan naif bisa menghasilkan val yang tidak representatif.
    rng = np.random.default_rng(seed)
    order = rng.permutation(len(x_train))
    x_train, y_train = x_train[order], y_train[order]

    cut = int(len(x_train) * (1 - val_fraction))
    x_fit, y_fit = x_train[:cut], y_train[:cut]
    x_val, y_val = x_train[cut:], y_train[cut:]

    x_fit, x_val, stats = standardize(x_fit, x_val)
    x_test_scaled = (x_test - np.asarray(stats["mean"])) / np.asarray(stats["std"])

    model = MLP(
        n_inputs=x_fit.shape[1],
        hidden_sizes=hidden_sizes,
        n_outputs=1,
        learning_rate=learning_rate,
        dropout=dropout,
        seed=seed,
    )

    history = model.fit(
        x_fit,
        y_fit,
        x_val=x_val,
        y_val=y_val,
        epochs=epochs,
        batch_size=batch_size,
        patience=patience,
        verbose=verbose,
    )

    proba = model.predict_proba(x_test_scaled).ravel()
    threshold, _ = best_threshold(y_test, proba)
    metrics = evaluate_binary(y_test, proba, threshold=threshold)

    config = {
        "hidden_sizes": list(hidden_sizes),
        "learning_rate": learning_rate,
        "epochs": epochs,
        "batch_size": batch_size,
        "patience": patience,
        "dropout": dropout,
        "seed": seed,
        "n_features": x_fit.shape[1],
    }

    return TrainResult(
        model=model,
        metrics=metrics,
        history=history,
        columns=columns,
        stats=stats,
        threshold=threshold,
        config=config,
    )


def train_multiclass(
    x: np.ndarray,
    y: np.ndarray,
    n_outputs: int,
    hidden_sizes: tuple[int, ...] = HIDDEN_SIZES,
    learning_rate: float = LEARNING_RATE,
    epochs: int = EPOCHS,
    batch_size: int = BATCH_SIZE,
    patience: int = PATIENCE,
    seed: int = SEED,
) -> tuple[MLP, dict]:
    """Latih MLP multikelas generik (dipakai untuk kategori kinerja)."""
    x_train, x_test, y_train, y_test = train_test_split(x, y, 0.2, seed)
    x_train, x_test, stats = standardize(x_train, x_test)

    model = MLP(
        n_inputs=x_train.shape[1],
        hidden_sizes=hidden_sizes,
        n_outputs=n_outputs,
        learning_rate=learning_rate,
        seed=seed,
    )
    model.fit(
        x_train,
        y_train,
        x_val=x_test,
        y_val=y_test,
        epochs=epochs,
        batch_size=batch_size,
        patience=patience,
    )

    pred = model.predict(x_test)
    metrics = {
        "accuracy": float((pred == y_test).mean()),
        "macro_f1": _macro_f1(y_test, pred),
        "stats": stats,
    }

    return model, metrics


def _macro_f1(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    from src.evaluation.metrics import macro_f1

    return macro_f1(y_true, y_pred)

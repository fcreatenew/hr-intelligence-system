"""Baseline klasik sebagai pembanding model Deep Learning.

Tujuannya bukan menggantikan model produksi, melainkan menyediakan titik acuan
agar klaim "Deep Learning lebih baik" dapat diuji, bukan diasumsikan.
"""

from __future__ import annotations

import numpy as np

from src.data.dataset import (
    build_features,
    encode,
    load_turnover,
    standardize,
    train_test_split,
)
from src.evaluation.metrics import best_threshold, evaluate_binary


def _logistic_regression(
    x_train: np.ndarray,
    y_train: np.ndarray,
    x_test: np.ndarray,
    learning_rate: float = 0.1,
    epochs: int = 500,
) -> np.ndarray:
    """Logistic Regression via gradient descent (tanpa scikit-learn)."""
    n, d = x_train.shape
    w = np.zeros(d)
    b = 0.0
    y = y_train.reshape(-1)

    for _ in range(epochs):
        z = x_train @ w + b
        p = 1.0 / (1.0 + np.exp(-np.clip(z, -60, 60)))
        grad_w = x_train.T @ (p - y) / n
        grad_b = float((p - y).mean())
        w -= learning_rate * grad_w
        b -= learning_rate * grad_b

    z = x_test @ w + b
    return 1.0 / (1.0 + np.exp(-np.clip(z, -60, 60)))


def _majority_class(y_train: np.ndarray, x_test: np.ndarray) -> np.ndarray:
    """Baseline trivial: selalu prediksi kelas mayoritas."""
    majority = int(round(float(y_train.mean())))
    return np.full(len(x_test), majority, dtype=float)


def baseline_turnover(data_path=None, seed: int = 42) -> dict:
    """Latih baseline dan kembalikan metrik pembanding."""
    df = load_turnover(data_path)
    x, _ = encode(build_features(df))
    y = df["target_turnover"].to_numpy(dtype=float)

    x_train, x_test, y_train, y_test = train_test_split(x, y, 0.2, seed)
    x_train_s, x_test_s, _ = standardize(x_train, x_test)

    lr_proba = _logistic_regression(x_train_s, y_train, x_test_s)
    lr_threshold, _ = best_threshold(y_test, lr_proba)

    majority_proba = _majority_class(y_train, x_test)
    majority_threshold, _ = best_threshold(y_test, majority_proba)

    return {
        "logistic_regression": evaluate_binary(y_test, lr_proba, lr_threshold),
        "majority_class": evaluate_binary(y_test, majority_proba, majority_threshold),
        "n_test": int(len(y_test)),
        "positif_test": int(y_test.sum()),
    }

"""Metrik evaluasi untuk eksperimen Deep Learning HR."""

from __future__ import annotations

import numpy as np


def confusion(y_true: np.ndarray, y_pred: np.ndarray) -> dict:
    y_true = np.asarray(y_true).astype(int)
    y_pred = np.asarray(y_pred).astype(int)

    tp = int(((y_true == 1) & (y_pred == 1)).sum())
    tn = int(((y_true == 0) & (y_pred == 0)).sum())
    fp = int(((y_true == 0) & (y_pred == 1)).sum())
    fn = int(((y_true == 1) & (y_pred == 0)).sum())

    return {"tp": tp, "tn": tn, "fp": fp, "fn": fn}


def accuracy(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    y_true = np.asarray(y_true)
    y_pred = np.asarray(y_pred)

    return float((y_true == y_pred).mean()) if len(y_true) else 0.0


def precision(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    c = confusion(y_true, y_pred)
    denom = c["tp"] + c["fp"]

    return c["tp"] / denom if denom else 0.0


def recall(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    c = confusion(y_true, y_pred)
    denom = c["tp"] + c["fn"]

    return c["tp"] / denom if denom else 0.0


def f1(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    p = precision(y_true, y_pred)
    r = recall(y_true, y_pred)

    return 2 * p * r / (p + r) if (p + r) else 0.0


def roc_auc(y_true: np.ndarray, y_score: np.ndarray) -> float:
    """ROC AUC berbasis peringkat (setara Mann-Whitney U).

    Implementasi manual agar modul ini tidak bergantung pada scikit-learn.
    """
    y_true = np.asarray(y_true).astype(int)
    y_score = np.asarray(y_score, dtype=float)

    n_pos = int((y_true == 1).sum())
    n_neg = int((y_true == 0).sum())

    if n_pos == 0 or n_neg == 0:
        return 0.5

    order = np.argsort(y_score, kind="mergesort")
    ranks = np.empty(len(y_score), dtype=float)
    ranks[order] = np.arange(1, len(y_score) + 1)

    # Tangani nilai skor yang sama (ties) dengan rata-rata peringkat.
    sorted_scores = y_score[order]
    i = 0

    while i < len(sorted_scores):
        j = i

        while j + 1 < len(sorted_scores) and sorted_scores[j + 1] == sorted_scores[i]:
            j += 1

        if j > i:
            ranks[order[i : j + 1]] = (i + j + 2) / 2

        i = j + 1

    rank_sum_pos = ranks[y_true == 1].sum()

    return float(
        (rank_sum_pos - n_pos * (n_pos + 1) / 2) / (n_pos * n_neg)
    )


def macro_f1(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    """Macro-F1 untuk klasifikasi multikelas."""
    y_true = np.asarray(y_true).astype(int)
    y_pred = np.asarray(y_pred).astype(int)
    labels = sorted(set(y_true.tolist()) | set(y_pred.tolist()))

    if not labels:
        return 0.0

    scores = []

    for label in labels:
        scores.append(
            f1((y_true == label).astype(int), (y_pred == label).astype(int))
        )

    return float(np.mean(scores))


def evaluate_binary(
    y_true: np.ndarray, y_proba: np.ndarray, threshold: float = 0.5
) -> dict:
    y_pred = (np.asarray(y_proba).ravel() >= threshold).astype(int)

    return {
        "accuracy": accuracy(y_true, y_pred),
        "precision": precision(y_true, y_pred),
        "recall": recall(y_true, y_pred),
        "f1": f1(y_true, y_pred),
        "roc_auc": roc_auc(y_true, y_proba),
        "threshold": threshold,
        "confusion": confusion(y_true, y_pred),
    }


def best_threshold(
    y_true: np.ndarray, y_proba: np.ndarray, steps: int = 100
) -> tuple[float, float]:
    """Cari ambang yang memaksimalkan F1. Mengembalikan (threshold, f1)."""
    best = (0.5, -1.0)

    for t in np.linspace(0.05, 0.95, steps):
        score = f1(y_true, (np.asarray(y_proba).ravel() >= t).astype(int))

        if score > best[1]:
            best = (float(t), float(score))

    return best

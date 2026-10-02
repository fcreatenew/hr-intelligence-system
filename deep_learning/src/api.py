"""API eksperimen Deep Learning.

Menyajikan hasil pelatihan MLP dan baseline sebagai JSON agar dapat dipakai
dashboard atau analisis lain. Model disimpan di memori per proses dan hanya
dilatih ulang saat diminta eksplisit (`/train`), bukan setiap request.
"""

from __future__ import annotations

from functools import lru_cache

from fastapi import FastAPI, Query

from src.evaluation.baseline import baseline_turnover
from src.experiments.tracker import ExperimentTracker
from src.training.trainer import TrainResult, train_turnover_mlp


app = FastAPI(
    title="Deep Learning HR API",
    description=(
        "Eksperimen neural network (NumPy) untuk prediksi turnover, "
        "dibandingkan dengan baseline klasik."
    ),
    version="0.1.0",
)


@lru_cache(maxsize=1)
def _trained() -> TrainResult:
    """Latih sekali per proses. Konfigurasi diambil dari env (lihat config)."""
    return train_turnover_mlp()


@app.get("/health", tags=["monitoring"])
async def health() -> dict:
    result = _trained()

    return {
        "status": "ok",
        "arsitektur": [result.model.n_inputs, *result.model.hidden_sizes],
        "jumlah_fitur": len(result.columns),
        "roc_auc": result.metrics["roc_auc"],
    }


@app.get("/metrics", tags=["evaluation"])
async def metrics() -> dict:
    result = _trained()

    return {
        "model": "mlp_numpy",
        "arsitektur": [result.model.n_inputs, *result.model.hidden_sizes],
        "threshold": result.threshold,
        "metrik": result.metrics,
        "config": result.config,
    }


@app.get("/baseline", tags=["evaluation"])
async def baseline() -> dict:
    return baseline_turnover()


@app.get("/experiments", tags=["experiments"])
async def experiments(metric: str = Query("roc_auc")) -> dict:
    tracker = ExperimentTracker()
    runs = tracker.list_runs()
    best = tracker.best(metric)

    return {"jumlah": len(runs), "terbaik": best}

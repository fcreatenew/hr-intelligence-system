from __future__ import annotations

import time

import httpx
import pandas as pd
from fastapi import FastAPI, HTTPException, Response

from src.config import (
    DRIFT_CRITICAL,
    DRIFT_WARNING,
    HR_INTELLIGENCE_API_KEY,
    HR_INTELLIGENCE_BASE_URL,
    HR_INTELLIGENCE_TIMEOUT,
)
from src.monitoring.drift import detect_feature_drift
from src.monitoring.metrics import MetricsCollector
from src.registry.model_registry import ModelRegistry
from src.validation.model_validator import validate_all


app = FastAPI(
    title="MLOps API",
    description=(
        "Model registry, validasi model, monitoring, dan drift detection untuk "
        "HR Intelligence System."
    ),
    version="0.1.0",
)

registry = ModelRegistry()
metrics = MetricsCollector()


def _api_client() -> httpx.Client:
    headers = {"X-API-Key": HR_INTELLIGENCE_API_KEY} if HR_INTELLIGENCE_API_KEY else {}

    return httpx.Client(
        base_url=HR_INTELLIGENCE_BASE_URL,
        headers=headers,
        timeout=HR_INTELLIGENCE_TIMEOUT,
    )


@app.get("/health", tags=["monitoring"])
async def health() -> dict:
    """Status MLOps: registry, validasi model, dan koneksi API HR."""
    validation = validate_all(registry)

    api_status: dict = {"reachable": False}

    try:
        with _api_client() as client:
            started = time.perf_counter()
            response = client.get("/health")
            latency_ms = (time.perf_counter() - started) * 1000

            metrics.set_gauge("hr_api_up", 1)
            metrics.observe("hr_api_latency_ms", latency_ms)

            api_status = {"reachable": response.status_code < 400, **response.json()}
    except Exception as exc:  # noqa: BLE001 - health tidak boleh gagal total
        metrics.set_gauge("hr_api_up", 0)
        api_status = {"reachable": False, "error": str(exc)}

    return {
        "status": "ok",
        "registry": registry.summary(),
        "validasi": validation,
        "hr_intelligence_api": api_status,
    }


@app.get("/registry", tags=["registry"])
async def list_registry() -> dict:
    return registry.summary()


@app.get("/registry/{use_case}", tags=["registry"])
async def get_model(use_case: str) -> dict:
    try:
        record = registry.get(use_case)
    except KeyError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc

    return record.to_dict()


@app.get("/validation", tags=["validation"])
async def validation() -> dict:
    return validate_all(registry)


@app.get("/metrics", tags=["monitoring"])
async def metrics_summary() -> dict:
    return metrics.summary()


@app.get("/metrics/prometheus", tags=["monitoring"])
async def metrics_prometheus() -> Response:
    return Response(content=metrics.to_prometheus(), media_type="text/plain")


@app.post("/monitoring/check-api", tags=["monitoring"])
async def check_api() -> dict:
    """Panggil HR Intelligence API dan catat latensi + status."""
    try:
        with _api_client() as client:
            started = time.perf_counter()
            response = client.get("/health")
            latency_ms = (time.perf_counter() - started) * 1000
    except Exception as exc:  # noqa: BLE001
        metrics.inc_counter("hr_api_check_failed")
        raise HTTPException(status_code=502, detail=f"API tidak tersedia: {exc}") from exc

    metrics.observe("hr_api_latency_ms", latency_ms)
    metrics.inc_counter("hr_api_check_total")

    if response.status_code >= 400:
        metrics.inc_counter("hr_api_check_failed")
        raise HTTPException(status_code=502, detail="API mengembalikan error.")

    return {"status": "ok", "latency_ms": round(latency_ms, 2), **response.json()}


@app.get("/drift", tags=["drift"])
async def drift(
    reference: str,
    current: str,
    warning: float = DRIFT_WARNING,
    critical: float = DRIFT_CRITICAL,
) -> dict:
    """Deteksi drift antara dua CSV (mis. data training vs data produksi)."""
    try:
        ref_df = pd.read_csv(reference)
        cur_df = pd.read_csv(current)
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=400, detail=f"Gagal membaca CSV: {exc}") from exc

    hasil = detect_feature_drift(ref_df, cur_df, warning=warning, critical=critical)
    metrics.set_gauge("drift_max_psi", hasil["fitur"][0]["psi"] if hasil["fitur"] else 0)

    return hasil

from __future__ import annotations

import time
from pathlib import Path

import httpx
import pandas as pd
from fastapi import FastAPI, HTTPException, Response
from pydantic import BaseModel, Field

from src.config import (
    DATA_DIR,
    DRIFT_CRITICAL,
    DRIFT_WARNING,
    HR_INTELLIGENCE_API_KEY,
    HR_INTELLIGENCE_BASE_URL,
    HR_INTELLIGENCE_TIMEOUT,
)
from src.lifecycle import (
    LifecycleError,
    active_version_info,
    list_versions,
    promote,
    rollback,
    run_retraining,
)
from src.lifecycle.retraining import RetrainingConfig
from src.monitoring.drift_monitor import DriftMonitor
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
drift_monitor = DriftMonitor(
    collector=metrics,
    warning=DRIFT_WARNING,
    critical=DRIFT_CRITICAL,
)


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
    use_case: str = "turnover",
    warning: float = DRIFT_WARNING,
    critical: float = DRIFT_CRITICAL,
) -> dict:
    """Deteksi drift antara dua CSV (mis. data training vs data produksi)."""
    try:
        ref_df = pd.read_csv(reference)
        cur_df = pd.read_csv(current)
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=400, detail=f"Gagal membaca CSV: {exc}") from exc

    monitor = DriftMonitor(collector=metrics, warning=warning, critical=critical)
    report = monitor.evaluate(use_case, ref_df, cur_df)

    return report.to_dict()


@app.get("/monitoring/drift/history", tags=["monitoring"])
async def drift_history(use_case: str | None = None) -> dict:
    """Riwayat evaluasi drift, opsional disaring per use case."""
    riwayat = drift_monitor.history(use_case)

    return {
        "jumlah": len(riwayat),
        "riwayat": [report.to_dict() for report in riwayat],
    }


@app.get("/monitoring/drift/alerts", tags=["monitoring"])
async def drift_alerts() -> dict:
    """Evaluasi terbaru per use case yang melewati ambang drift."""
    alerts = drift_monitor.alerts()

    return {
        "jumlah": len(alerts),
        "alerts": [report.to_dict() for report in alerts],
    }


# ---------------------------------------------------------------------------
# Siklus hidup model: versi aktif, promosi, rollback, retraining otomatis.
# ---------------------------------------------------------------------------


class PromoteRequest(BaseModel):
    version: str = Field(..., description="Versi tujuan, mis. v2_mitigated")
    reason: str = "promosi manual"


class RollbackRequest(BaseModel):
    reason: str = "rollback manual"


class RetrainRequest(BaseModel):
    current: str | None = Field(
        None,
        description="Path CSV data produksi untuk deteksi drift (opsional).",
    )
    data: str = "hr_turnover.csv"
    seed: int = 42
    force: bool = False
    min_delta: float = 0.0


@app.get("/lifecycle/{use_case}", tags=["lifecycle"])
async def lifecycle_status(use_case: str = "turnover") -> dict:
    """Versi aktif, versi tersedia, dan metrik versi aktif."""
    try:
        versi = list_versions(use_case)
    except LifecycleError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    return {
        "use_case": use_case,
        "tersedia": versi,
        **active_version_info(use_case),
    }


@app.post("/lifecycle/{use_case}/promote", tags=["lifecycle"])
async def lifecycle_promote(use_case: str, payload: PromoteRequest) -> dict:
    """Pin satu versi sebagai versi aktif (setelah artifact divalidasi ada)."""
    try:
        return promote(payload.version, use_case, reason=payload.reason)
    except LifecycleError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


@app.post("/lifecycle/{use_case}/rollback", tags=["lifecycle"])
async def lifecycle_rollback(use_case: str, payload: RollbackRequest) -> dict:
    """Kembalikan versi aktif ke versi sebelumnya."""
    try:
        return rollback(use_case, reason=payload.reason)
    except LifecycleError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


@app.post("/retraining/run", tags=["lifecycle"])
async def retraining_run(payload: RetrainRequest) -> dict:
    """Jalankan siklus retraining berbasis drift untuk turnover.

    Bila `current` diisi, drift dihitung terhadap dataset training. Bila tidak,
    data training dipakai sebagai pembanding (tidak ada drift kecuali `force`).
    """
    if payload.current:
        current_path = Path(payload.current)
        if not current_path.exists():
            raise HTTPException(
                status_code=400,
                detail=f"Data produksi tidak ditemukan: {current_path}",
            )
        try:
            current = pd.read_csv(current_path)
        except Exception as exc:  # noqa: BLE001
            raise HTTPException(status_code=400, detail=f"Gagal membaca CSV: {exc}") from exc
    else:
        current = pd.read_csv(DATA_DIR / payload.data)

    config = RetrainingConfig(
        seed=payload.seed,
        data_file=payload.data,
        force=payload.force,
        min_metric_delta=payload.min_delta,
    )

    try:
        return run_retraining(config, current=current, collector=metrics)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

from __future__ import annotations

import time
from pathlib import Path

import httpx
import pandas as pd
from fastapi import FastAPI, HTTPException, Response
from pydantic import BaseModel, Field

from src.config import (
    ALERT_TIMEOUT,
    ALERT_WEBHOOK_URL,
    DATA_DIR,
    DRIFT_CRITICAL,
    DRIFT_WARNING,
    HR_INTELLIGENCE_API_KEY,
    HR_INTELLIGENCE_BASE_URL,
    HR_INTELLIGENCE_TIMEOUT,
)
from src.lifecycle import (
    LifecycleError,
    PerformanceMonitorConfig,
    active_version_info,
    list_versions,
    promote,
    rollback,
    run_performance_monitor,
    run_retraining,
)
from src.monitoring.fairness import FairnessConfig, run_fairness_audit
from src.monitoring.feedback import FeedbackStore
from src.lifecycle.retraining import RetrainingConfig
from src.monitoring.alerting import AlertDispatcher, build_alert_sender
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


@app.post("/monitoring/check-drift", tags=["monitoring"])
async def check_drift(payload: DriftCheckRequest | None = None) -> dict:
    """Evaluasi drift data produksi vs data training dan kirim alert bila perlu.

    Dipakai oleh penjadwal (cron) sebagai pemeriksaan berkala. Alert dikirim ke
    webhook bila `ALERT_WEBHOOK_URL` diisi; jika tidak, hanya dicatat sebagai
    metrik.
    """
    payload = payload or DriftCheckRequest()

    from src.config import DATA_DIR
    from src.lifecycle.retraining import RetrainingConfig, reference_features

    config = RetrainingConfig(data_file=payload.data)
    referensi = reference_features(config, DATA_DIR)

    if payload.current:
        current_path = Path(payload.current)
        if not current_path.exists():
            raise HTTPException(
                status_code=400,
                detail=f"Data produksi tidak ditemukan: {current_path}",
            )
        current = pd.read_csv(current_path)
    else:
        current = pd.read_csv(DATA_DIR / payload.data)

    report = drift_monitor.evaluate("turnover", referensi, current)

    dispatcher = AlertDispatcher(
        sender=build_alert_sender(payload.webhook or ALERT_WEBHOOK_URL, ALERT_TIMEOUT),
        collector=metrics,
    )
    pengiriman = dispatcher.dispatch(report)

    return {"drift": report.to_dict(), "alert": pengiriman}


# ---------------------------------------------------------------------------
# Siklus hidup model: versi aktif, promosi, rollback, retraining otomatis.
# ---------------------------------------------------------------------------


class PromoteRequest(BaseModel):
    version: str = Field(..., description="Versi tujuan, mis. v2_mitigated")
    reason: str = "promosi manual"


class RollbackRequest(BaseModel):
    reason: str = "rollback manual"


class FeedbackEntryRequest(BaseModel):
    prediction: dict = Field(..., description="Fitur mentah + skor/probabilitas prediksi")
    actual: dict = Field(..., description="Label sebenarnya, mis. {'target_turnover': 1}")
    use_case: str = "turnover"
    version: str | None = None


class FeedbackExportRequest(BaseModel):
    destination: str = Field(..., description="Path CSV tujuan ekspor")
    use_case: str = "turnover"


class RetrainRequest(BaseModel):
    current: str | None = Field(
        None,
        description="Path CSV data produksi untuk deteksi drift (opsional).",
    )
    data: str = "hr_turnover.csv"
    seed: int = 42
    force: bool = False
    min_delta: float = 0.0


class DriftCheckRequest(BaseModel):
    current: str | None = Field(
        None,
        description="Path CSV data produksi untuk deteksi drift (opsional).",
    )
    data: str = "hr_turnover.csv"
    webhook: str | None = Field(
        None,
        description="URL webhook alert; default dari env ALERT_WEBHOOK_URL.",
    )


class PerformanceCheckRequest(BaseModel):
    current: str = Field(
        ...,
        description="Path CSV umpan balik berlabel (kolom skor + label_aktual).",
    )
    tolerance: float = 0.05
    min_samples: int = 30


class FairnessAuditRequest(BaseModel):
    version: str | None = Field(None, description="Versi turnover; default versi aktif.")
    data: str = "hr_turnover.csv"
    threshold: float | None = None
    min_group_size: int = 20
    impact_ratio: float = 0.8


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


@app.post("/monitoring/performance", tags=["monitoring"])
async def performance_check(payload: PerformanceCheckRequest) -> dict:
    """Evaluasi performa produksi dan rollback otomatis bila turun.

    `current` harus CSV umpan balik berlabel dengan kolom `skor` dan
    `label_aktual`. Bila performa (ROC AUC) turun melebihi `tolerance`
    dibanding metrik holdout versi aktif, versi sebelumnya dipin kembali.
    """
    current_path = Path(payload.current)
    if not current_path.exists():
        raise HTTPException(
            status_code=400,
            detail=f"Data umpan balik tidak ditemukan: {current_path}",
        )

    try:
        current = pd.read_csv(current_path)
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=400, detail=f"Gagal membaca CSV: {exc}") from exc

    config = PerformanceMonitorConfig(
        tolerance=payload.tolerance,
        min_samples=payload.min_samples,
    )

    return run_performance_monitor(current, config=config, collector=metrics)


@app.post("/monitoring/fairness", tags=["monitoring"])
async def fairness_audit(payload: FairnessAuditRequest) -> dict:
    """Audit fairness model turnover per atribut pelindung.

    Menghitung demographic parity difference, impact ratio (four-fifths),
    equal opportunity difference, dan equalized odds difference untuk atribut
    usia/departemen/status kerja/pendidikan. Mengembalikan `ringkasan.lolos`
    beserta daftar pelanggaran.
    """
    config = FairnessConfig(
        version=payload.version,
        data_file=payload.data,
        threshold=payload.threshold,
        min_group_size=payload.min_group_size,
        impact_ratio_threshold=payload.impact_ratio,
    )

    try:
        return run_fairness_audit(config)
    except FileNotFoundError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc
    except KeyError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc


# ---------------------------------------------------------------------------
# Umpan balik produksi (prediksi + label sebenarnya)
# ---------------------------------------------------------------------------

feedback_store = FeedbackStore()


@app.post("/feedback", tags=["monitoring"])
async def record_feedback(payload: FeedbackEntryRequest) -> dict:
    """Simpan pasangan prediksi–label sebenarnya untuk monitoring performa.

    Dipakai sistem HR / job ETL untuk mengirim label aktual (mis. karyawan
    benar-benar keluar) agar `run_performance_monitor` punya data.
    """
    try:
        entry = feedback_store.record(
            payload.prediction,
            payload.actual,
            use_case=payload.use_case,
            version=payload.version,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc)) from exc

    return {"tersimpan": True, "total": len(feedback_store.entries(payload.use_case)), "entri": entry}


@app.get("/feedback", tags=["monitoring"])
async def feedback_summary(use_case: str = "turnover") -> dict:
    """Ringkasan umpan balik tersimpan untuk sebuah use case."""
    entries = feedback_store.entries(use_case)
    df = feedback_store.to_dataframe(use_case)

    return {
        "use_case": use_case,
        "total": len(entries),
        "kolom": list(df.columns),
        "siap_dievaluasi": len(df) > 0,
    }


@app.post("/feedback/export", tags=["monitoring"])
async def feedback_export(payload: FeedbackExportRequest) -> dict:
    """Ekspor umpan balik ke CSV agar bisa dipakai monitoring performa/drift."""
    destination = Path(payload.destination)
    df = feedback_store.to_dataframe(payload.use_case)

    if df.empty:
        raise HTTPException(
            status_code=400,
            detail=f"Tidak ada umpan balik untuk use case '{payload.use_case}'.",
        )

    feedback_store.export_csv(destination, payload.use_case)

    return {"diekspor": str(destination), "baris": len(df), "kolom": list(df.columns)}

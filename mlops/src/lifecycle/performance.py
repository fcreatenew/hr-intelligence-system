"""Monitor performa produksi dan rollback otomatis.

Retraining otomatis (`retraining.py`) bereaksi terhadap **drift input**. Celah
yang tersisa: model bisa lolos gate saat dilatih, tetapi performanya menurun di
produksi. Modul ini menutup celah itu dengan umpan balik label aktual:

    prediksi + label aktual (hasil) → ROC AUC produksi
        │  bandingkan dengan metrik holdout incumbent
        ▼
    produksi turun > toleransi? ── ya ──▶ rollback ke versi sebelumnya

Data umpan balik bersifat opsional (organisasi mungkin belum punya aliran
label). Bila tidak ada data, evaluasi melaporkan `tanpa_data` dan tidak
mengubah apa pun.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path

import pandas as pd
from sklearn.metrics import roc_auc_score

from src.config import MODELS_DIR
from src.lifecycle.model_lifecycle import (
    LifecycleError,
    read_active_version,
    resolve_active_version,
    rollback,
)
from src.monitoring.metrics import MetricsCollector
from src.registry.model_registry import ModelRegistry
from src.validation.model_validator import primary_metric


# Nama kolom kanonik untuk data umpan balik.
SKOR_COLUMN = "skor"
LABEL_COLUMN = "label_aktual"


@dataclass
class PerformanceMonitorConfig:
    use_case: str = "turnover"
    # Penurunan ROC AUC yang masih dapat ditoleransi sebelum rollback.
    tolerance: float = 0.05
    # Minimum baris berlabel agar evaluasi dianggap cukup meyakinkan.
    min_samples: int = 30
    label_column: str = LABEL_COLUMN
    score_column: str = SKOR_COLUMN


@dataclass
class PerformanceReport:
    use_case: str
    status: str  # ok | turun | tanpa_data
    roc_auc_produksi: float | None
    roc_auc_referensi: float | None
    selisih: float | None
    samples: int
    label_column: str = LABEL_COLUMN
    score_column: str = SKOR_COLUMN

    def to_dict(self) -> dict:
        return {
            "use_case": self.use_case,
            "status": self.status,
            "roc_auc_produksi": self.roc_auc_produksi,
            "roc_auc_referensi": self.roc_auc_referensi,
            "selisih": self.selisih,
            "samples": self.samples,
            "label_column": self.label_column,
            "score_column": self.score_column,
        }


def evaluate_performance(
    current: pd.DataFrame,
    reference_auc: float | None,
    config: PerformanceMonitorConfig | None = None,
) -> PerformanceReport:
    """Hitung ROC AUC produksi dan bandingkan dengan performa referensi."""
    config = config or PerformanceMonitorConfig()
    kolom = {config.score_column, config.label_column}

    if not kolom.issubset(current.columns):
        return PerformanceReport(
            use_case=config.use_case,
            status="tanpa_data",
            roc_auc_produksi=None,
            roc_auc_referensi=reference_auc,
            selisih=None,
            samples=0,
            label_column=config.label_column,
            score_column=config.score_column,
        )

    valid = current.dropna(subset=[config.score_column, config.label_column])

    if len(valid) < config.min_samples:
        return PerformanceReport(
            use_case=config.use_case,
            status="tanpa_data",
            roc_auc_produksi=None,
            roc_auc_referensi=reference_auc,
            selisih=None,
            samples=len(valid),
            label_column=config.label_column,
            score_column=config.score_column,
        )

    y_true = valid[config.label_column].astype(int)
    y_score = valid[config.score_column].astype(float)

    if y_true.nunique() < 2:
        return PerformanceReport(
            use_case=config.use_case,
            status="tanpa_data",
            roc_auc_produksi=None,
            roc_auc_referensi=reference_auc,
            selisih=None,
            samples=len(valid),
            label_column=config.label_column,
            score_column=config.score_column,
        )

    produksi = float(roc_auc_score(y_true, y_score))
    selisih = None if reference_auc is None else round(produksi - reference_auc, 4)
    turun = selisih is not None and selisih < -config.tolerance

    return PerformanceReport(
        use_case=config.use_case,
        status="turun" if turun else "ok",
        roc_auc_produksi=round(produksi, 4),
        roc_auc_referensi=reference_auc,
        selisih=selisih,
        samples=len(valid),
        label_column=config.label_column,
        score_column=config.score_column,
    )


def run_performance_monitor(
    current: pd.DataFrame | None = None,
    *,
    config: PerformanceMonitorConfig | None = None,
    models_dir: Path = MODELS_DIR,
    collector: MetricsCollector | None = None,
) -> dict:
    """Evaluasi performa produksi; rollback otomatis bila turun melewati toleransi."""
    config = config or PerformanceMonitorConfig()
    collector = collector if collector is not None else MetricsCollector()
    registry = ModelRegistry(models_dir)

    aktif = resolve_active_version(config.use_case, registry, models_dir)

    try:
        record = registry.get(config.use_case, versi=aktif)
        primary = primary_metric(record.metrics)
        reference_auc = primary[1] if primary else None
    except (KeyError, FileNotFoundError):
        reference_auc = None

    if current is None or current.empty:
        report = PerformanceReport(
            use_case=config.use_case,
            status="tanpa_data",
            roc_auc_produksi=None,
            roc_auc_referensi=reference_auc,
            selisih=None,
            samples=0,
        )
    else:
        report = evaluate_performance(current, reference_auc, config)

    hasil: dict = {
        "use_case": config.use_case,
        "versi_aktif": aktif,
        "performa": report.to_dict(),
    }

    if report.status != "turun":
        collector.inc_counter("performance_monitor_total", status=report.status)
        hasil["status"] = report.status
        hasil["rollback"] = None
        return hasil

    # Hanya bisa rollback bila ada pin dengan riwayat versi sebelumnya.
    if read_active_version(config.use_case, models_dir) is None:
        collector.inc_counter("performance_monitor_total", status="turun_tanpa_pin")
        hasil["status"] = "turun_tanpa_pin"
        hasil["rollback"] = None
        hasil["alasan"] = (
            "Performa turun tetapi belum ada pin versi, sehingga tidak ada "
            "versi sebelumnya untuk rollback."
        )
        return hasil

    try:
        hasil_rollback = rollback(
            config.use_case,
            registry=registry,
            models_dir=models_dir,
            reason=(
                f"rollback otomatis: ROC AUC produksi "
                f"{report.roc_auc_produksi} turun dari referensi "
                f"{report.roc_auc_referensi}"
            ),
        )
    except LifecycleError as exc:
        collector.inc_counter("performance_monitor_total", status="rollback_gagal")
        hasil["status"] = "rollback_gagal"
        hasil["rollback"] = None
        hasil["alasan"] = str(exc)
        return hasil

    collector.inc_counter("performance_monitor_total", status="rollback")
    collector.inc_counter(
        "model_rollback_total",
        use_case=config.use_case,
        dari=hasil_rollback["sebelumnya"],
        ke=hasil_rollback["aktif"],
    )
    hasil["status"] = "rollback"
    hasil["rollback"] = hasil_rollback
    hasil["alasan"] = (
        f"ROC AUC produksi {report.roc_auc_produksi} turun dari referensi "
        f"{report.roc_auc_referensi}; dikembalikan ke {hasil_rollback['aktif']}."
    )
    return hasil

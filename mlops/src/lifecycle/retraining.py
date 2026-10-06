"""Orkestrasi retraining otomatis: drift → latih → gate → promosi.

Menutup celah Fase 6: "retraining terjadwal + rollback model". Modul ini
merangkai komponen yang sudah ada (drift PSI, pipeline training reproducible,
gate validasi, registry) menjadi satu keputusan yang dapat diaudit:

    data produksi
        │  DriftMonitor.evaluate (PSI)
        ▼
    drift terdeteksi? ── tidak ──▶ selesai (tidak ada perubahan)
        │ ya
        ▼
    latih versi baru (src.train) di models/<vN>/
        │
        ▼
    gate validate_record ── gagal ──▶ tolak, versi aktif tetap
        │ lulus
        ▼
    metrik kandidat ≥ metrik incumbent?
        │ ya                    │ tidak
        ▼                       ▼
    promosikan (pin)         tolak, versi aktif tetap

Kandidat yang ditolak tetap tersimpan di disk sebagai artifact versi baru
(append-only) agar dapat diperiksa; yang berubah hanyalah pin versi aktif.
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from pathlib import Path

import pandas as pd

from src.config import DATA_DIR, DRIFT_CRITICAL, DRIFT_WARNING, MIN_ROC_AUC, MODELS_DIR
from src.lifecycle.model_lifecycle import promote, read_active_version, resolve_active_version
from src.monitoring.drift_monitor import DriftMonitor
from src.monitoring.metrics import MetricsCollector
from src.registry.model_registry import ModelRegistry
from src.train.pipeline import (
    RAW_COLUMNS_REQUIRED,
    TrainingConfig,
    buat_fitur,
    load_data,
    train,
)
from src.validation.model_validator import primary_metric, validate_record


@dataclass
class RetrainingConfig:
    use_case: str = "turnover"
    seed: int = 42
    test_size: float = 0.2
    ref_date: str = "2025-01-01"
    data_file: str = "hr_turnover.csv"
    min_roc_auc: float = MIN_ROC_AUC
    drift_warning: float = DRIFT_WARNING
    drift_critical: float = DRIFT_CRITICAL
    # Kandidat harus setidaknya menyamai incumbent; naikkan untuk syarat lebih ketat.
    min_metric_delta: float = 0.0
    force: bool = False

    @property
    def ref_timestamp(self) -> pd.Timestamp:
        return pd.Timestamp(self.ref_date)


def next_version(models_dir: Path = MODELS_DIR, use_case: str = "turnover") -> str:
    """Versi berikutnya (`v<N+1>`) dari versi bernomor yang sudah ada.

    Hanya pola `v<angka>` yang dihitung; `v2_mitigated` dan `v_ci` dilewati
    agar penomoran otomatis tidak melompat atau menimpa versi khusus.
    """
    registry = ModelRegistry(models_dir)
    nomor: list[int] = []

    for versi in registry.list_versions(use_case):
        cocok = re.fullmatch(r"v(\d+)", versi)
        if cocok:
            nomor.append(int(cocok.group(1)))

    return f"v{max(nomor, default=0) + 1}"


def _as_features(df: pd.DataFrame, config: RetrainingConfig) -> pd.DataFrame:
    """Ubah data mentah menjadi fitur bila kolom mentahnya lengkap.

    Data produksi bisa datang sebagai baris mentah (kolom `usia`,
    `gaji_bulanan`, ...) atau sudah berupa fitur. Deteksi sederhana ini menjaga
    drift dihitung pada representasi yang sama dengan data training.
    """
    if all(kolom in df.columns for kolom in RAW_COLUMNS_REQUIRED):
        return buat_fitur(df, config.ref_timestamp)

    return df


def reference_features(
    config: RetrainingConfig,
    data_dir: Path = DATA_DIR,
) -> pd.DataFrame:
    """Distribusi referensi = fitur dari dataset training yang tercatat."""
    df = load_data(data_dir / config.data_file, config.ref_timestamp)

    return buat_fitur(df, config.ref_timestamp)


def run_retraining(
    config: RetrainingConfig | None = None,
    *,
    current: pd.DataFrame,
    reference: pd.DataFrame | None = None,
    data_dir: Path = DATA_DIR,
    models_dir: Path = MODELS_DIR,
    collector: MetricsCollector | None = None,
) -> dict:
    """Jalankan satu siklus retraining berbasis drift.

    `current` adalah data produksi terbaru; `reference` adalah distribusi
    pembanding (default: fitur dataset training). Kembalikan dict keputusan yang
    memuat status, drift, versi, dan gate — cukup untuk dilog/diaudit.
    """
    config = config or RetrainingConfig()

    if config.use_case != "turnover":
        raise ValueError(
            "Retraining otomatis saat ini hanya untuk use case 'turnover'."
        )

    collector = collector if collector is not None else MetricsCollector()

    monitor = DriftMonitor(
        collector=collector,
        warning=config.drift_warning,
        critical=config.drift_critical,
    )

    reference = reference if reference is not None else reference_features(config, data_dir)
    reference = _as_features(reference, config)
    current = _as_features(current, config)

    drift = monitor.evaluate(
        config.use_case,
        reference,
        current,
    )

    hasil: dict = {
        "use_case": config.use_case,
        "drift": drift.to_dict(),
    }

    if not drift.drift_terdeteksi and not config.force:
        collector.inc_counter("retrain_total", status="tidak_perlu")
        hasil.update(
            {
                "status": "tidak_perlu",
                "alasan": (
                    f"Drift maksimum {drift.max_psi} di bawah ambang "
                    f"{config.drift_warning}; model tidak dilatih ulang."
                ),
            }
        )
        return hasil

    versi_baru = next_version(models_dir, config.use_case)

    training = train(
        TrainingConfig(
            seed=config.seed,
            test_size=config.test_size,
            ref_date=config.ref_date,
            data_file=config.data_file,
            version=versi_baru,
            min_roc_auc=config.min_roc_auc,
        ),
        data_dir=data_dir,
        models_dir=models_dir,
    )

    registry = ModelRegistry(models_dir)
    kandidat = registry.get(config.use_case, versi=versi_baru)
    gate = validate_record(kandidat, min_auc=config.min_roc_auc)

    hasil.update(
        {
            "versi_baru": versi_baru,
            "artifact_dir": training["artifact_dir"],
            "gate": gate,
            "metrik_kandidat": training["metrics"],
        }
    )

    if not gate["lulus"]:
        collector.inc_counter("retrain_total", status="gagal_gate")
        hasil.update(
            {
                "status": "gagal_gate",
                "versi_aktif": resolve_active_version(
                    config.use_case, registry, models_dir
                ),
                "alasan": "Kandidat tidak lolos gate validasi; versi aktif tidak diubah.",
            }
        )
        return hasil

    incumbent_version = resolve_active_version(config.use_case, registry, models_dir)
    kandidat_metric = primary_metric(kandidat.metrics)

    try:
        incumbent = registry.get(config.use_case, versi=incumbent_version)
        incumbent_metric = primary_metric(incumbent.metrics)
    except (KeyError, FileNotFoundError):
        incumbent = None
        incumbent_metric = None

    hasil["incumbent"] = {
        "versi": incumbent_version,
        "metrik": incumbent_metric,
    }

    if (
        incumbent_metric is not None
        and kandidat_metric is not None
        and kandidat_metric[1] < incumbent_metric[1] + config.min_metric_delta
    ):
        collector.inc_counter("retrain_total", status="ditolak")
        hasil.update(
            {
                "status": "ditolak",
                "versi_aktif": incumbent_version,
                "alasan": (
                    f"Metrik kandidat {kandidat_metric[0]}={kandidat_metric[1]} "
                    f"lebih rendah dari incumbent {incumbent_metric[0]}="
                    f"{incumbent_metric[1]}; promosi dibatalkan."
                ),
            }
        )
        return hasil

    promosi = promote(
        versi_baru,
        config.use_case,
        registry=registry,
        models_dir=models_dir,
        reason=f"retraining otomatis (drift {drift.level})",
    )

    collector.inc_counter("retrain_total", status="dipromosikan")
    hasil.update(
        {
            "status": "dipromosikan",
            "versi_aktif": versi_baru,
            "promosi": promosi,
            "alasan": (
                f"Kandidat lolos gate dan metrik tidak lebih rendah; "
                f"{versi_baru} dipromosikan menggantikan {incumbent_version}."
            ),
        }
    )
    return hasil


def active_version_info(
    use_case: str = "turnover",
    *,
    models_dir: Path = MODELS_DIR,
) -> dict:
    """Ringkasan versi aktif + versi sebelumnya untuk ditampilkan/di-audit."""
    registry = ModelRegistry(models_dir)
    aktif = resolve_active_version(use_case, registry, models_dir)
    pin = read_active_version(use_case, models_dir)

    info: dict = {
        "use_case": use_case,
        "aktif": aktif,
        "dipin": pin is not None,
    }

    try:
        record = registry.get(use_case, versi=aktif)
        info["versi"] = record.versi
        info["metrik"] = primary_metric(record.metrics)
        info["dilatih_pada"] = record.trained_at
    except (KeyError, FileNotFoundError):
        info["versi"] = aktif

    return info

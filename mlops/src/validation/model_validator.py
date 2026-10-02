from __future__ import annotations

from src.config import MIN_ROC_AUC
from src.registry.model_registry import ModelRecord


def _primary_metric(metrics: dict) -> tuple[str, float] | None:
    """Pilih metrik kualitas utama sesuai jenis model.

    ROC AUC dipakai bila ada; model ordinal multi-kelas (kinerja) memakai
    macro-F1, dan model survival memakai C-index. Model unsupervised payroll
    ditangani terpisah karena tidak punya label.
    """
    for key in ("roc_auc", "isolation_forest_roc_auc", "c_index", "f1_macro"):
        if isinstance(metrics.get(key), (int, float)):
            return key, float(metrics[key])

    return None


def validate_record(
    record: ModelRecord,
    min_auc: float = MIN_ROC_AUC,
) -> dict:
    """Validasi satu model sebelum dianggap layak deploy.

    Pemeriksaan menyasar hal yang paling sering membuat model gagal di
    produksi: metadata kosong, versi tidak tercatat, dan performa di bawah
    ambang. Ambang dibandingkan dengan metrik utama sesuai jenis model.
    """
    checks: list[dict] = []
    primary = _primary_metric(record.metrics)
    unsupervised = "isolation" in record.model_type.lower() or "unsupervised" in (
        record.target or ""
    ).lower()

    checks.append(
        {
            "nama": "metadata_lengkap",
            "lulus": bool(record.versi and record.model_type),
            "detail": f"versi={record.versi!r}, model={record.model_type!r}",
        }
    )

    checks.append(
        {
            "nama": "metrik_tersedia",
            "lulus": bool(record.metrics),
            "detail": f"{len(record.metrics)} metrik",
        }
    )

    if unsupervised:
        checks.append(
            {
                "nama": "metrik_unsupervised",
                "lulus": bool(record.metrics.get("isolation_forest_pr_auc")),
                "detail": "PR AUC tersedia untuk model unsupervised",
            }
        )
    else:
        checks.append(
            {
                "nama": "metrik_utama_minimum",
                "lulus": primary is not None and primary[1] >= min_auc,
                "detail": (
                    f"{primary[0]}={primary[1]} (min {min_auc})"
                    if primary
                    else "metrik utama tidak ditemukan"
                ),
            }
        )

    if "accuracy" in record.metrics:
        checks.append(
            {
                "nama": "akurasi_wajar",
                "lulus": float(record.metrics["accuracy"]) >= 0.5,
                "detail": f"accuracy={record.metrics['accuracy']}",
            }
        )

    lulus = all(check["lulus"] for check in checks)

    return {
        "use_case": record.use_case,
        "versi": record.versi,
        "lulus": lulus,
        "checks": checks,
    }


def validate_all(registry) -> dict:
    hasil = [validate_record(record) for record in registry.all()]

    return {
        "lulus_semua": all(item["lulus"] for item in hasil),
        "jumlah": len(hasil),
        "hasil": hasil,
    }

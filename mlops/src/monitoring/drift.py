from __future__ import annotations

import numpy as np
import pandas as pd


def psi(reference: np.ndarray, current: np.ndarray, bins: int = 10) -> float:
    """Population Stability Index antara distribusi referensi dan produksi.

    PSI mengukur seberapa jauh distribusi bergeser. Nilai 0 berarti identik;
    > 0.1 mulai perlu dicermati dan > 0.2 biasanya dianggap signifikan.
    """
    reference = np.asarray(reference, dtype=float)
    current = np.asarray(current, dtype=float)

    reference = reference[np.isfinite(reference)]
    current = current[np.isfinite(current)]

    if len(reference) == 0 or len(current) == 0:
        return 0.0

    # Tepi bin dari kuantil referensi, unik agar tidak ada bin kosong.
    edges = np.unique(np.quantile(reference, np.linspace(0, 1, bins + 1)))

    if len(edges) < 3:
        return 0.0

    ref_counts, _ = np.histogram(reference, bins=edges)
    cur_counts, _ = np.histogram(current, bins=edges)

    ref_pct = np.clip(ref_counts / max(ref_counts.sum(), 1), 1e-6, None)
    cur_pct = np.clip(cur_counts / max(cur_counts.sum(), 1), 1e-6, None)

    return float(np.sum((cur_pct - ref_pct) * np.log(cur_pct / ref_pct)))


def drift_level(value: float, warning: float = 0.1, critical: float = 0.2) -> str:
    if value >= critical:
        return "kritis"
    if value >= warning:
        return "peringatan"
    return "stabil"


def detect_feature_drift(
    reference: pd.DataFrame,
    current: pd.DataFrame,
    features: list[str] | None = None,
    warning: float = 0.1,
    critical: float = 0.2,
) -> dict:
    """Deteksi drift per fitur numerik antara data referensi dan produksi."""
    features = features or [
        col
        for col in reference.columns
        if col in current.columns and pd.api.types.is_numeric_dtype(reference[col])
    ]

    hasil: list[dict] = []

    for feature in features:
        nilai = psi(reference[feature].values, current[feature].values)
        hasil.append(
            {
                "fitur": feature,
                "psi": round(nilai, 4),
                "level": drift_level(nilai, warning, critical),
            }
        )

    hasil.sort(key=lambda item: item["psi"], reverse=True)
    drift_terdeteksi = any(item["level"] != "stabil" for item in hasil)

    return {
        "jumlah_fitur": len(hasil),
        "drift_terdeteksi": drift_terdeteksi,
        "level_tertinggi": hasil[0]["level"] if hasil else "stabil",
        "fitur": hasil,
    }

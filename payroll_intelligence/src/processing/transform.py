from __future__ import annotations

import numpy as np
import pandas as pd


DERIVED_COLUMNS = [
    "biaya_lembur",
    "rasio_tunjangan",
    "rasio_potongan",
    "total_per_pokok",
]

# Kontrak fitur yang sama dengan hr_intelligence_api/app/features/features.py
# (buat_fitur_payroll), supaya hasil analitik konsisten dengan model anomali.
PAYROLL_MODEL_FEATURES = [
    "gaji_pokok",
    "tunjangan",
    "lembur_jam",
    "tarif_lembur",
    "potongan",
    "total_gaji",
    "rasio_tunjangan",
    "rasio_potongan",
    "total_per_pokok",
    "biaya_lembur",
]


def enrich(df: pd.DataFrame) -> pd.DataFrame:
    """Tambahkan kolom turunan payroll yang dipakai analitik dan model."""
    d = df.copy()

    pokok = d["gaji_pokok"].replace(0, np.nan)

    d["biaya_lembur"] = d["lembur_jam"] * d["tarif_lembur"]
    d["rasio_tunjangan"] = d["tunjangan"] / pokok
    d["rasio_potongan"] = d["potongan"] / pokok
    d["total_per_pokok"] = d["total_gaji"] / pokok

    return d


def model_features(df: pd.DataFrame) -> pd.DataFrame:
    """Susun matriks fitur model anomali dari data mentah."""
    d = enrich(df) if "biaya_lembur" not in df.columns else df.copy()

    return d[PAYROLL_MODEL_FEATURES].astype(float)


def clean(df: pd.DataFrame) -> pd.DataFrame:
    """Bersihkan nilai ekstrem yang jelas tidak valid (gaji negatif)."""
    d = df.copy()

    d = d[d["gaji_pokok"] >= 0]
    d = d[d["total_gaji"] >= 0]

    return d.reset_index(drop=True)

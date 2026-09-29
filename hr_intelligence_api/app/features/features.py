from __future__ import annotations

import numpy as np
import pandas as pd

NUM_COLS = [
    "usia",
    "masa_kerja_bulan",
    "jam_lembur_bulanan",
    "log_gaji",
    "usia_saat_masuk",
    "ada_promosi",
    "bulan_sejak_promosi",
    "bulan_hingga_promosi",
]
CAT_COLS = ["departemen", "status_kerja", "pendidikan"]
FEATURES = NUM_COLS + CAT_COLS

RAW_COLUMNS_REQUIRED = [
    "usia",
    "masa_kerja_bulan",
    "jam_lembur_bulanan",
    "gaji_bulanan",
    "tanggal_masuk",
    "tanggal_promosi",
    "departemen",
    "status_kerja",
    "pendidikan",
]


def buat_fitur(df: pd.DataFrame, ref_date: pd.Timestamp) -> pd.DataFrame:
    d = df.copy()

    masuk = pd.to_datetime(d["tanggal_masuk"])
    promosi = pd.to_datetime(d["tanggal_promosi"])

    d["ada_promosi"] = promosi.notna().astype(float)
    d["bulan_sejak_promosi"] = (ref_date - promosi).dt.days / 30.44
    d["bulan_hingga_promosi"] = (promosi - masuk).dt.days / 30.44
    d["usia_saat_masuk"] = d["usia"] - d["masa_kerja_bulan"] / 12
    d["log_gaji"] = np.log1p(d["gaji_bulanan"])

    fitur = d[FEATURES].copy()
    fitur[NUM_COLS] = fitur[NUM_COLS].astype(float)
    return fitur

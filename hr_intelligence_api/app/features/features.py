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


# ---------------------------------------------------------------------------
# Absensi (target_absen_tinggi)
# ---------------------------------------------------------------------------
ABSENSI_NUM_COLS = [
    "usia",
    "masa_kerja_bulan",
    "jarak_km",
    "beban_kerja",
    "lembur_jam_bulanan",
    "kepuasan_kerja",
    "riwayat_absen_90hari",
]
ABSENSI_CAT_COLS = ["departemen", "status_kerja", "pendidikan"]


def buat_fitur_absensi(df: pd.DataFrame) -> pd.DataFrame:
    fitur = df[ABSENSI_NUM_COLS + ABSENSI_CAT_COLS].copy()
    fitur[ABSENSI_NUM_COLS] = fitur[ABSENSI_NUM_COLS].astype(float)
    return fitur


# ---------------------------------------------------------------------------
# Kinerja (kategori_kinerja: Rendah/Sedang/Tinggi)
# ---------------------------------------------------------------------------
KINERJA_NUM_COLS = [
    "usia",
    "masa_kerja_bulan",
    "jam_lembur_bulanan",
    "jumlah_training_setahun",
    "proyek_selesai",
    "skor_umpan_balik_360",
    "hari_absen_setahun",
]
KINERJA_CAT_COLS = ["departemen", "pendidikan"]


def buat_fitur_kinerja(df: pd.DataFrame) -> pd.DataFrame:
    fitur = df[KINERJA_NUM_COLS + KINERJA_CAT_COLS].copy()
    fitur[KINERJA_NUM_COLS] = fitur[KINERJA_NUM_COLS].astype(float)
    return fitur


# ---------------------------------------------------------------------------
# Anomali payroll
# ---------------------------------------------------------------------------
PAYROLL_BASE_NUM = [
    "gaji_pokok",
    "tunjangan",
    "lembur_jam",
    "tarif_lembur",
    "potongan",
    "total_gaji",
]
PAYROLL_DERIVED = [
    "rasio_tunjangan",
    "rasio_potongan",
    "total_per_pokok",
    "biaya_lembur",
]
PAYROLL_NUM_COLS = PAYROLL_BASE_NUM + PAYROLL_DERIVED


def buat_fitur_payroll(df: pd.DataFrame) -> pd.DataFrame:
    d = df.copy()

    pokok = d["gaji_pokok"].replace(0, np.nan)
    d["rasio_tunjangan"] = d["tunjangan"] / pokok
    d["rasio_potongan"] = d["potongan"] / pokok
    d["total_per_pokok"] = d["total_gaji"] / pokok
    d["biaya_lembur"] = d["lembur_jam"] * d["tarif_lembur"]

    fitur = d[PAYROLL_NUM_COLS].copy()
    fitur[PAYROLL_NUM_COLS] = fitur[PAYROLL_NUM_COLS].astype(float)
    return fitur


# ---------------------------------------------------------------------------
# Survival attrition (Cox PH)
# ---------------------------------------------------------------------------
SURVIVAL_NUM_COLS = [
    "usia",
    "gaji_bulanan",
    "jam_lembur_bulanan",
    "kepuasan_kerja",
    "bulan_sejak_promosi",
    "jarak_km",
]
SURVIVAL_CAT_COLS = ["departemen", "pendidikan"]

# Baseline yang di-drop saat training (drop_first=True): Finance & Diploma.
# Kolom dummy di bawah harus sama persis dengan kolom saat training; dibuat
# eksplisit agar kolom tetap ada walau batch hanya berisi satu nilai kategori.
SURVIVAL_DEPARTEMEN_LEVELS = [
    "HR",
    "IT",
    "Legal",
    "Marketing",
    "Operations",
    "Sales",
]
SURVIVAL_PENDIDIKAN_LEVELS = ["S1", "S2", "SMA"]


def buat_fitur_survival(df: pd.DataFrame) -> pd.DataFrame:
    """Bangun matriks desain Cox PH dengan kolom yang sama seperti saat training."""
    d = df.copy()

    out = d[SURVIVAL_NUM_COLS].astype(float).copy()

    departemen = d["departemen"].astype(str)
    pendidikan = d["pendidikan"].astype(str)

    for col in SURVIVAL_DEPARTEMEN_LEVELS:
        out[f"departemen_{col}"] = (departemen == col).astype(float)

    for col in SURVIVAL_PENDIDIKAN_LEVELS:
        out[f"pendidikan_{col}"] = (pendidikan == col).astype(float)

    return out

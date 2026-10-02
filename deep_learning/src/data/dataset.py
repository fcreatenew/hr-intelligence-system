"""Pemuatan data dan persiapan fitur untuk eksperimen Deep Learning.

Kontrak fitur turnover **wajib identik** dengan `hr_intelligence_api` dan
notebook training (lihat AGENTS.md). Untuk menghindari duplikasi rumus, modul
ini memakai ulang `buat_fitur` dari API bila tersedia; bila tidak, memakai
implementasi cadangan yang setara.
"""

from __future__ import annotations

import sys
from pathlib import Path

import numpy as np
import pandas as pd

from src.config import REF_DATE, REPO_ROOT, TURNOVER_DATA_PATH

# Kolom kategorikal yang di-one-hot. Urutannya harus stabil agar bobot model
# tetap cocok saat inference.
CATEGORICAL = ["departemen", "status_kerja", "pendidikan"]


def _load_feature_builder():
    """Ambil `buat_fitur` dari hr_intelligence_api bila dapat diimpor."""
    api_dir = REPO_ROOT / "hr_intelligence_api"

    if api_dir.exists() and str(api_dir) not in sys.path:
        sys.path.insert(0, str(api_dir))

    try:
        from app.features.features import buat_fitur  # type: ignore

        return buat_fitur
    except Exception:
        return _buat_fitur_cadangan


def _buat_fitur_cadangan(df: pd.DataFrame, ref_date: pd.Timestamp) -> pd.DataFrame:
    d = df.copy()
    masuk = pd.to_datetime(d["tanggal_masuk"])
    promosi = pd.to_datetime(d["tanggal_promosi"])

    d["ada_promosi"] = promosi.notna().astype(float)
    d["bulan_sejak_promosi"] = (ref_date - promosi).dt.days / 30.44
    d["bulan_hingga_promosi"] = (promosi - masuk).dt.days / 30.44
    d["usia_saat_masuk"] = d["usia"] - d["masa_kerja_bulan"] / 12
    d["log_gaji"] = np.log1p(d["gaji_bulanan"])

    num = [
        "usia",
        "masa_kerja_bulan",
        "jam_lembur_bulanan",
        "log_gaji",
        "usia_saat_masuk",
        "ada_promosi",
        "bulan_sejak_promosi",
        "bulan_hingga_promosi",
    ]

    fitur = d[num + CATEGORICAL].copy()
    fitur[num] = fitur[num].astype(float)
    return fitur


def load_turnover(path: Path | None = None) -> pd.DataFrame:
    data_path = path or TURNOVER_DATA_PATH

    if not data_path.exists():
        raise FileNotFoundError(f"Dataset turnover tidak ditemukan: {data_path}")

    return pd.read_csv(data_path)


def build_features(df: pd.DataFrame, ref_date: str | None = None) -> pd.DataFrame:
    """Bangun fitur turnover memakai kontrak yang sama dengan API."""
    builder = _load_feature_builder()
    reference = pd.Timestamp(ref_date or REF_DATE)
    return builder(df, reference)


def encode(
    fitur: pd.DataFrame,
) -> tuple[np.ndarray, list[str]]:
    """One-hot encode kolom kategorikal menjadi matriks numerik.

    Mengembalikan (matriks, daftar nama kolom). Nama kolom disimpan bersama
    artifact agar urutan fitur konsisten saat inference.
    """
    encoded = pd.get_dummies(fitur, columns=CATEGORICAL, dtype=float)
    encoded = encoded.replace([np.inf, -np.inf], np.nan).fillna(0.0)

    return encoded.to_numpy(dtype=float), list(encoded.columns)


def prepare_dataset(
    path: Path | None = None,
    ref_date: str | None = None,
    target: str = "target_turnover",
) -> tuple[np.ndarray, np.ndarray, list[str]]:
    df = load_turnover(path)
    fitur = build_features(df, ref_date)
    x, columns = encode(fitur)
    y = df[target].to_numpy(dtype=float)

    return x, y, columns


def standardize(
    x_train: np.ndarray,
    x_other: np.ndarray | None = None,
) -> tuple[np.ndarray, np.ndarray | None, dict]:
    """Standarisasi z-score memakai statistik data train saja.

    Statistik disimpan agar transformasi saat inference tidak bocor dari data
    uji (mencegah data leakage).
    """
    mean = x_train.mean(axis=0)
    std = x_train.std(axis=0)
    std[std == 0] = 1.0

    scaled_train = (x_train - mean) / std
    scaled_other = None

    if x_other is not None:
        scaled_other = (x_other - mean) / std

    stats = {"mean": mean.tolist(), "std": std.tolist()}

    return scaled_train, scaled_other, stats


def train_test_split(
    x: np.ndarray,
    y: np.ndarray,
    test_size: float = 0.2,
    seed: int = 42,
) -> tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    rng = np.random.default_rng(seed)
    n = len(x)
    idx = rng.permutation(n)
    cut = int(n * (1 - test_size))

    train_idx, test_idx = idx[:cut], idx[cut:]

    return x[train_idx], x[test_idx], y[train_idx], y[test_idx]

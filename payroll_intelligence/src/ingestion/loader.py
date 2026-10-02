from __future__ import annotations

from pathlib import Path

import pandas as pd

from src.config import DATA_PATH


PAYROLL_COLUMNS = [
    "employee_id",
    "periode",
    "departemen",
    "level",
    "gaji_pokok",
    "tunjangan",
    "lembur_jam",
    "tarif_lembur",
    "potongan",
    "total_gaji",
]

NUMERIC_COLUMNS = [
    "gaji_pokok",
    "tunjangan",
    "lembur_jam",
    "tarif_lembur",
    "potongan",
    "total_gaji",
]


class PayrollDataError(RuntimeError):
    ...


def load_payroll(path: Path | str | None = None) -> pd.DataFrame:
    """Muat data payroll dari CSV dan validasi kolom wajib.

    Data payroll memuat kolom label (`is_anomaly`) hanya untuk evaluasi; kolom
    itu tidak dipakai oleh detektor karena model berjalan tanpa label.
    """
    path = Path(path) if path else DATA_PATH

    if not path.exists():
        raise PayrollDataError(f"Data payroll tidak ditemukan: {path}")

    df = pd.read_csv(path)

    missing = [col for col in PAYROLL_COLUMNS if col not in df.columns]
    if missing:
        raise PayrollDataError(
            f"Kolom wajib tidak ada di {path.name}: {', '.join(missing)}"
        )

    df["periode"] = df["periode"].astype(str)
    for col in NUMERIC_COLUMNS:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df = df.dropna(subset=["gaji_pokok", "total_gaji"]).reset_index(drop=True)

    return df


def available_periods(df: pd.DataFrame) -> list[str]:
    return sorted(df["periode"].unique().tolist())


def available_departments(df: pd.DataFrame) -> list[str]:
    return sorted(df["departemen"].dropna().unique().tolist())

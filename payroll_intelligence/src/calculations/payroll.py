from __future__ import annotations

import pandas as pd


def total_gaji(row: dict) -> float:
    """Rumus total gaji: pokok + tunjangan + upah lembur - potongan."""
    return (
        row["gaji_pokok"]
        + row["tunjangan"]
        + row["lembur_jam"] * row["tarif_lembur"]
        - row["potongan"]
    )


def biaya_lembur(row: dict) -> float:
    return row["lembur_jam"] * row["tarif_lembur"]


def hitung_total_gaji(df: pd.DataFrame) -> pd.Series:
    """Hitung ulang total gaji untuk seluruh baris."""
    return (
        df["gaji_pokok"]
        + df["tunjangan"]
        + df["lembur_jam"] * df["tarif_lembur"]
        - df["potongan"]
    )


def selisih_total_gaji(df: pd.DataFrame) -> pd.Series:
    """Selisih antara total_gaji tercatat dan hasil perhitungan ulang.

    Nilai yang jauh dari nol menandakan ketidaksesuaian komponen payroll.
    """
    return (df["total_gaji"] - hitung_total_gaji(df)).abs()


def total_payroll(df: pd.DataFrame) -> float:
    return float(df["total_gaji"].sum())


def komponen_agregat(df: pd.DataFrame) -> dict[str, float]:
    return {
        "gaji_pokok": float(df["gaji_pokok"].sum()),
        "tunjangan": float(df["tunjangan"].sum()),
        "biaya_lembur": float((df["lembur_jam"] * df["tarif_lembur"]).sum()),
        "potongan": float(df["potongan"].sum()),
        "total_gaji": float(df["total_gaji"].sum()),
    }

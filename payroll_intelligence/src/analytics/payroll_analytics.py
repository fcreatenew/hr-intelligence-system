from __future__ import annotations

import pandas as pd

from src.processing.transform import enrich


def summary(df: pd.DataFrame) -> dict:
    d = enrich(df)

    return {
        "jumlah_baris": int(len(d)),
        "jumlah_karyawan": int(d["employee_id"].nunique()),
        "jumlah_periode": int(d["periode"].nunique()),
        "periode_awal": str(d["periode"].min()),
        "periode_akhir": str(d["periode"].max()),
        "total_payroll": float(d["total_gaji"].sum()),
        "rata_rata_gaji": float(d["total_gaji"].mean()),
        "median_gaji": float(d["total_gaji"].median()),
        "total_biaya_lembur": float(d["biaya_lembur"].sum()),
        "rata_rata_lembur_jam": float(d["lembur_jam"].mean()),
    }


def by_department(df: pd.DataFrame) -> list[dict]:
    d = enrich(df)

    grouped = (
        d.groupby("departemen")
        .agg(
            jumlah_karyawan=("employee_id", "nunique"),
            total_payroll=("total_gaji", "sum"),
            rata_rata_gaji=("total_gaji", "mean"),
            total_biaya_lembur=("biaya_lembur", "sum"),
            total_lembur_jam=("lembur_jam", "sum"),
        )
        .reset_index()
        .sort_values("total_payroll", ascending=False)
    )

    return grouped.round(2).to_dict(orient="records")


def by_period(df: pd.DataFrame) -> list[dict]:
    d = enrich(df)

    grouped = (
        d.groupby("periode")
        .agg(
            total_payroll=("total_gaji", "sum"),
            rata_rata_gaji=("total_gaji", "mean"),
            total_biaya_lembur=("biaya_lembur", "sum"),
            jumlah_karyawan=("employee_id", "nunique"),
        )
        .reset_index()
        .sort_values("periode")
    )

    return grouped.round(2).to_dict(orient="records")


def by_level(df: pd.DataFrame) -> list[dict]:
    d = enrich(df)

    grouped = (
        d.groupby("level")
        .agg(
            jumlah_karyawan=("employee_id", "nunique"),
            rata_rata_gaji=("total_gaji", "mean"),
            total_payroll=("total_gaji", "sum"),
        )
        .reset_index()
        .sort_values("rata_rata_gaji", ascending=False)
    )

    return grouped.round(2).to_dict(orient="records")


def top_overtime(df: pd.DataFrame, limit: int = 10) -> list[dict]:
    d = enrich(df)

    grouped = (
        d.groupby("employee_id")
        .agg(
            total_lembur_jam=("lembur_jam", "sum"),
            total_biaya_lembur=("biaya_lembur", "sum"),
            periode=("periode", "count"),
        )
        .reset_index()
        .sort_values("total_biaya_lembur", ascending=False)
        .head(limit)
    )

    return grouped.round(2).to_dict(orient="records")


def salary_distribution(df: pd.DataFrame, bins: int = 10) -> list[dict]:
    d = enrich(df)

    counts, edges = pd.cut(d["total_gaji"], bins=bins, retbins=True)
    histogram = counts.value_counts().sort_index()

    return [
        {
            "rentang": f"{int(interval.left):,} - {int(interval.right):,}",
            "jumlah": int(count),
        }
        for interval, count in histogram.items()
    ]

from __future__ import annotations

import pandas as pd

from src.forecasting.forecast import ForecastResult, forecast_series


def forecast_total_payroll(
    df: pd.DataFrame,
    horizon: int = 6,
    seasonal_period: int = 12,
) -> ForecastResult:
    """Forecast total payroll bulanan."""
    per_periode = df.groupby("periode")["total_gaji"].sum().sort_index()

    return forecast_series(
        periods=[str(p) for p in per_periode.index],
        values=per_periode.values.tolist(),
        horizon=horizon,
        seasonal_period=seasonal_period,
    )


def forecast_overtime_cost(
    df: pd.DataFrame,
    horizon: int = 6,
    seasonal_period: int = 12,
) -> ForecastResult:
    """Forecast biaya lembur bulanan."""
    d = df.copy()
    d["biaya_lembur"] = d["lembur_jam"] * d["tarif_lembur"]

    per_periode = d.groupby("periode")["biaya_lembur"].sum().sort_index()

    return forecast_series(
        periods=[str(p) for p in per_periode.index],
        values=per_periode.values.tolist(),
        horizon=horizon,
        seasonal_period=seasonal_period,
    )


def forecast_headcount_cost(
    df: pd.DataFrame,
    horizon: int = 6,
    seasonal_period: int = 12,
) -> ForecastResult:
    """Forecast rata-rata biaya payroll per karyawan per bulan."""
    per_periode = df.groupby("periode")["total_gaji"].mean().sort_index()

    return forecast_series(
        periods=[str(p) for p in per_periode.index],
        values=per_periode.values.tolist(),
        horizon=horizon,
        seasonal_period=seasonal_period,
    )

from __future__ import annotations

from dataclasses import dataclass

import numpy as np
import pandas as pd


@dataclass
class ForecastResult:
    periode: list[str]
    nilai: list[float]
    model: str
    seasonal_period: int | None = None
    residual_std: float | None = None

    def to_dict(self) -> dict:
        return {
            "model": self.model,
            "periode": self.periode,
            "nilai": [round(v, 2) for v in self.nilai],
            "seasonal_period": self.seasonal_period,
            "residual_std": (
                round(self.residual_std, 2)
                if self.residual_std is not None
                else None
            ),
        }


def _period_index(periods: list[str]) -> pd.PeriodIndex:
    return pd.PeriodIndex(periods, freq="M")


def _next_periods(last_period: str, horizon: int) -> list[str]:
    start = pd.Period(last_period, freq="M") + 1

    return [str(start + i) for i in range(horizon)]


def forecast_series(
    periods: list[str],
    values: list[float],
    horizon: int = 6,
    seasonal_period: int = 12,
) -> ForecastResult:
    """Forecast deret payroll bulanan.

    Memakai Holt-Winters (tren + musiman) bila data cukup panjang, lalu jatuh
    ke regresi linier sederhana untuk deret pendek. Pemilihan ini disengaja:
    Holt-Winters butuh minimal dua siklus musiman, sedangkan data periode bisa
    lebih pendek dari itu.
    """
    series = pd.Series(
        np.asarray(values, dtype=float),
        index=_period_index(periods),
    ).sort_index()

    if len(series) < 4:
        rata_rata = float(series.mean()) if len(series) else 0.0
        future = _next_periods(str(series.index[-1]), horizon) if len(series) else []

        return ForecastResult(
            periode=future,
            nilai=[rata_rata] * len(future),
            model="rata-rata",
        )

    use_seasonal = seasonal_period and len(series) >= 2 * seasonal_period

    try:
        from statsmodels.tsa.holtwinters import ExponentialSmoothing

        if use_seasonal:
            model = ExponentialSmoothing(
                series,
                trend="add",
                seasonal="add",
                seasonal_periods=seasonal_period,
                initialization_method="estimated",
            )
        else:
            model = ExponentialSmoothing(
                series,
                trend="add",
                seasonal=None,
                initialization_method="estimated",
            )

        fit = model.fit(optimized=True)
        ramalan = fit.forecast(horizon)
        residual_std = float(np.std(fit.resid))

        return ForecastResult(
            periode=[str(p) for p in ramalan.index],
            nilai=[float(v) for v in ramalan.values],
            model="holt-winters" if use_seasonal else "holt-linear",
            seasonal_period=seasonal_period if use_seasonal else None,
            residual_std=residual_std,
        )
    except Exception:  # noqa: BLE001 - fallback deterministik bila gagal
        x = np.arange(len(series), dtype=float)
        koef = np.polyfit(x, series.values, 1)

        masa_depan = np.arange(len(series), len(series) + horizon, dtype=float)
        prediksi = np.polyval(koef, masa_depan)
        residual_std = float(np.std(series.values - np.polyval(koef, x)))

        return ForecastResult(
            periode=_next_periods(str(series.index[-1]), horizon),
            nilai=[float(v) for v in prediksi],
            model="regresi-linier",
            residual_std=residual_std,
        )

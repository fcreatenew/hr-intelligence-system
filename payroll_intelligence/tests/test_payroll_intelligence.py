from __future__ import annotations

import numpy as np
import pandas as pd
import pytest

from src.analytics import anomaly, payroll_analytics
from src.calculations import payroll as calc
from src.forecasting.service import forecast_overtime_cost, forecast_total_payroll
from src.ingestion.loader import available_periods, load_payroll
from src.processing.transform import enrich, model_features


@pytest.fixture(scope="module")
def df() -> pd.DataFrame:
    return enrich(load_payroll())


def test_loader_returns_expected_columns(df):
    assert {"employee_id", "periode", "total_gaji", "lembur_jam"} <= set(df.columns)
    assert len(df) > 100
    assert len(available_periods(df)) >= 3


def test_enrich_adds_derived_columns(df):
    for col in ("biaya_lembur", "rasio_tunjangan", "rasio_potongan", "total_per_pokok"):
        assert col in df.columns


def test_model_features_match_api_contract(df):
    features = model_features(df)

    assert list(features.columns) == [
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


def test_total_gaji_formula(df):
    hitung = calc.hitung_total_gaji(df)
    selisih = (df["total_gaji"] - hitung).abs()

    # Data sintetis konsisten dengan rumus.
    assert selisih.median() < 1000


def test_summary_is_consistent(df):
    s = payroll_analytics.summary(df)

    assert s["total_payroll"] == pytest.approx(df["total_gaji"].sum())
    assert s["jumlah_karyawan"] == df["employee_id"].nunique()
    assert s["rata_rata_gaji"] > 0


def test_analytics_breakdowns(df):
    dept = payroll_analytics.by_department(df)
    periode = payroll_analytics.by_period(df)

    assert dept and all(row["total_payroll"] > 0 for row in dept)
    assert [row["periode"] for row in periode] == sorted(
        row["periode"] for row in periode
    )


def test_detect_anomaly_has_reasons_and_levels(df):
    hasil = anomaly.detect(df, threshold=0.5)

    assert {"skor_anomali", "is_anomali", "level", "alasan"} <= set(hasil.columns)
    assert hasil["skor_anomali"].between(0, 1).all()
    assert set(hasil["level"]) <= {"Normal", "Perlu Ditinjau", "Anomali"}
    # Data sintetis memuat anomali yang disuntikkan, harus terdeteksi.
    assert hasil["is_anomali"].sum() > 0
    assert (hasil[hasil["is_anomali"]]["alasan"] != "-").any()


def test_rule_flags_catch_inconsistent_total(df):
    rusak = df.head(1).copy()
    rusak.loc[rusak.index[0], "total_gaji"] = rusak["total_gaji"].iloc[0] + 5_000_000

    flags = anomaly.rule_based_flags(rusak)

    assert flags["total_tidak_konsisten"].iloc[0]


def test_forecast_returns_horizon(df):
    hasil = forecast_total_payroll(df, horizon=6)

    assert len(hasil.periode) == 6
    assert len(hasil.nilai) == 6
    assert all(np.isfinite(hasil.nilai))
    assert hasil.model in {"holt-winters", "holt-linear", "regresi-linier", "rata-rata"}


def test_forecast_overtime(df):
    hasil = forecast_overtime_cost(df, horizon=3)

    assert len(hasil.periode) == 3
    assert all(v >= 0 for v in hasil.nilai)


def test_forecast_short_series_falls_back():
    hasil = forecast_total_payroll(
        pd.DataFrame(
            {
                "periode": ["2025-01", "2025-02"],
                "total_gaji": [1000.0, 1100.0],
                "lembur_jam": [1, 1],
                "tarif_lembur": [10.0, 10.0],
            }
        ),
        horizon=2,
    )

    assert len(hasil.periode) == 2
    assert hasil.model == "rata-rata"

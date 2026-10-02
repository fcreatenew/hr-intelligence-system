from __future__ import annotations

from functools import lru_cache

import pandas as pd
from fastapi import FastAPI, HTTPException, Query
from pydantic import BaseModel, Field

from src.analytics import anomaly, payroll_analytics
from src.calculations import payroll as calc
from src.config import ANOMALY_THRESHOLD, FORECAST_HORIZON
from src.forecasting.service import forecast_overtime_cost, forecast_total_payroll
from src.ingestion.loader import PayrollDataError, available_periods, load_payroll
from src.processing.transform import clean, enrich


app = FastAPI(
    title="Payroll Intelligence API",
    description=(
        "Analitik payroll, deteksi anomali, dan forecasting untuk HR "
        "Intelligence System."
    ),
    version="0.1.0",
)


@lru_cache(maxsize=1)
def _dataset() -> pd.DataFrame:
    return clean(enrich(load_payroll()))


@lru_cache(maxsize=1)
def _reference_detector() -> anomaly.AnomalyDetector:
    """Detektor acuan yang dilatih pada data historis.

    Dipakai untuk menilai payroll baru terhadap distribusi historis, bukan
    terhadap dirinya sendiri.
    """
    return anomaly.AnomalyDetector(
        contamination=anomaly.DEFAULT_CONTAMINATION,
        threshold=ANOMALY_THRESHOLD,
    ).fit(_dataset())


def _slice(periode: str | None = None, departemen: str | None = None) -> pd.DataFrame:
    df = _dataset()

    if periode:
        df = df[df["periode"] == periode]
    if departemen:
        df = df[df["departemen"] == departemen]

    return df.reset_index(drop=True)


@app.get("/health", tags=["monitoring"])
async def health() -> dict:
    df = _dataset()

    return {
        "status": "ok",
        "jumlah_baris": int(len(df)),
        "jumlah_periode": int(df["periode"].nunique()),
        "periode": available_periods(df),
    }


@app.get("/summary", tags=["analytics"])
async def summary(
    periode: str | None = None, departemen: str | None = None
) -> dict:
    df = _slice(periode, departemen)

    if df.empty:
        raise HTTPException(status_code=404, detail="Tidak ada data untuk filter ini.")

    return payroll_analytics.summary(df)


@app.get("/departments", tags=["analytics"])
async def departments() -> list[dict]:
    return payroll_analytics.by_department(_dataset())


@app.get("/periods", tags=["analytics"])
async def periods() -> list[dict]:
    return payroll_analytics.by_period(_dataset())


@app.get("/overtime", tags=["analytics"])
async def overtime(limit: int = Query(10, ge=1, le=100)) -> list[dict]:
    return payroll_analytics.top_overtime(_dataset(), limit=limit)


@app.get("/components", tags=["analytics"])
async def components() -> dict:
    return calc.komponen_agregat(_dataset())


@app.get("/anomalies", tags=["anomaly"])
async def anomalies(
    threshold: float = Query(ANOMALY_THRESHOLD, ge=0.0, le=1.0),
    limit: int = Query(50, ge=1, le=1000),
) -> dict:
    df = _dataset()
    hasil = anomaly.detect(df, threshold=threshold)
    flagged = hasil[hasil["is_anomali"]]

    return {
        "threshold": threshold,
        "jumlah_anomali": int(len(flagged)),
        "hasil": flagged.head(limit).to_dict(orient="records"),
    }


@app.get("/forecast/payroll", tags=["forecasting"])
async def forecast_payroll(
    horizon: int = Query(FORECAST_HORIZON, ge=1, le=24)
) -> dict:
    return forecast_total_payroll(_dataset(), horizon=horizon).to_dict()


@app.get("/forecast/overtime", tags=["forecasting"])
async def forecast_overtime(
    horizon: int = Query(FORECAST_HORIZON, ge=1, le=24)
) -> dict:
    return forecast_overtime_cost(_dataset(), horizon=horizon).to_dict()


class PayrollScoreRow(BaseModel):
    employee_id: str
    periode: str
    departemen: str
    level: str
    gaji_pokok: float = Field(..., ge=0)
    tunjangan: float
    lembur_jam: int = Field(..., ge=0)
    tarif_lembur: float = Field(..., ge=0)
    potongan: float
    total_gaji: float


class PayrollScoreRequest(BaseModel):
    employees: list[PayrollScoreRow] = Field(..., min_length=1, max_length=20000)
    threshold: float = Field(ANOMALY_THRESHOLD, ge=0.0, le=1.0)


@app.post("/anomalies/score", tags=["anomaly"])
async def score_anomalies(payload: PayrollScoreRequest) -> dict:
    """Skor anomali untuk baris payroll yang dikirim klien.

    Memakai detektor acuan yang dilatih pada data historis, sehingga payroll
    yang baru dihitung dapat dinilai tanpa mengirim seluruh dataset.
    """
    rows = [row.model_dump() for row in payload.employees]
    df = pd.DataFrame(rows)

    hasil = anomaly.score_with(
        _reference_detector(), df, threshold=payload.threshold
    )

    return {
        "threshold": payload.threshold,
        "jumlah_diproses": int(len(hasil)),
        "jumlah_anomali": int(hasil["is_anomali"].sum()),
        "hasil": hasil.to_dict(orient="records"),
    }

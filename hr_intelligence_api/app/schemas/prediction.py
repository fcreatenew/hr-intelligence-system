from pydantic import BaseModel, Field
from typing import Literal

from app.schemas.employee import EmployeeInput


class BatchPredictRequest(BaseModel):
    employees: list[EmployeeInput] = Field(
        ...,
        min_length=1,
        max_length=20000,
    )


class PredictionResult(BaseModel):
    employee_id: str
    probabilitas_keluar: float
    prediksi_keluar: int
    level_risiko: Literal[
        "Rendah",
        "Sedang",
        "Tinggi",
    ]


class BatchPredictResponse(BaseModel):
    hasil: list[PredictionResult]
    jumlah_diproses: int
    model_version: str
    threshold: float
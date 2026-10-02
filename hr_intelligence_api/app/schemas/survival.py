from typing import Literal

from pydantic import BaseModel, Field

from app.schemas.employee import Departemen, Pendidikan


class SurvivalInput(BaseModel):
    employee_id: str = Field(..., examples=["EMP0001"])

    usia: int = Field(..., ge=17, le=75)
    gaji_bulanan: float = Field(..., gt=0)
    jam_lembur_bulanan: float = Field(..., ge=0, le=300)
    kepuasan_kerja: float = Field(..., ge=1, le=5)
    bulan_sejak_promosi: float = Field(..., ge=0, le=600)
    jarak_km: float = Field(..., ge=0, le=200)

    departemen: Departemen
    pendidikan: Pendidikan

    model_config = {
        "json_schema_extra": {
            "example": {
                "employee_id": "EMP0001",
                "usia": 34,
                "gaji_bulanan": 9500000,
                "jam_lembur_bulanan": 18.0,
                "kepuasan_kerja": 3.4,
                "bulan_sejak_promosi": 22.0,
                "jarak_km": 11.5,
                "departemen": "IT",
                "pendidikan": "S1",
            }
        }
    }


class SurvivalBatchRequest(BaseModel):
    employees: list[SurvivalInput] = Field(..., min_length=1, max_length=20000)


class SurvivalResult(BaseModel):
    employee_id: str
    risiko_relatif: float = Field(
        ...,
        description="exp(partial hazard) relatif terhadap rata-rata populasi training",
    )
    level_risiko: Literal["Rendah", "Sedang", "Tinggi"]
    median_survival_bulan: float | None = None


class SurvivalBatchResponse(BaseModel):
    hasil: list[SurvivalResult]
    jumlah_diproses: int
    model_version: str
    baseline_median_survival_bulan: float

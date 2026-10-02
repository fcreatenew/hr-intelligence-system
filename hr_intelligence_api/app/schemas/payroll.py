from typing import Literal

from pydantic import BaseModel, Field

from app.schemas.employee import Departemen


Level = Literal["Staff", "Senior", "Lead", "Manager"]


class PayrollInput(BaseModel):
    employee_id: str = Field(..., examples=["EMP0001"])
    periode: str = Field(..., examples=["2025-01"], description="Periode payroll (YYYY-MM)")
    departemen: Departemen
    level: Level

    gaji_pokok: float = Field(..., ge=0)
    tunjangan: float = Field(..., description="Boleh negatif bila ada koreksi")
    lembur_jam: int = Field(..., ge=0, le=1000)
    tarif_lembur: float = Field(..., ge=0)
    potongan: float = Field(...)
    total_gaji: float = Field(..., description="Total gaji yang dibayarkan")

    model_config = {
        "json_schema_extra": {
            "example": {
                "employee_id": "EMP0001",
                "periode": "2025-01",
                "departemen": "Finance",
                "level": "Senior",
                "gaji_pokok": 9000000,
                "tunjangan": 1800000,
                "lembur_jam": 8,
                "tarif_lembur": 78000,
                "potongan": 720000,
                "total_gaji": 10704000,
            }
        }
    }


class PayrollBatchRequest(BaseModel):
    employees: list[PayrollInput] = Field(..., min_length=1, max_length=20000)


class PayrollResult(BaseModel):
    employee_id: str
    periode: str
    skor_anomali: float = Field(..., ge=0, le=1)
    is_anomali: bool
    level: Literal["Normal", "Perlu Ditinjau", "Anomali"]
    threshold: float


class PayrollBatchResponse(BaseModel):
    hasil: list[PayrollResult]
    jumlah_diproses: int
    model_version: str
    threshold: float

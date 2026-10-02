from pydantic import BaseModel, Field

from app.schemas.employee import (
    Departemen,
    Pendidikan,
    StatusKerja,
)


class AbsensiInput(BaseModel):
    employee_id: str = Field(..., examples=["EMP0001"])

    usia: int = Field(..., ge=17, le=75)
    masa_kerja_bulan: int = Field(..., ge=0, le=600)
    jarak_km: float = Field(..., ge=0, le=200, description="Jarak rumah ke kantor (km)")
    beban_kerja: float = Field(..., ge=0, le=300)
    lembur_jam_bulanan: float = Field(..., ge=0, le=300)
    kepuasan_kerja: float = Field(..., ge=1, le=5)
    riwayat_absen_90hari: int = Field(..., ge=0, le=90)

    departemen: Departemen
    status_kerja: StatusKerja
    pendidikan: Pendidikan

    model_config = {
        "json_schema_extra": {
            "example": {
                "employee_id": "EMP0001",
                "usia": 32,
                "masa_kerja_bulan": 48,
                "jarak_km": 12.5,
                "beban_kerja": 78.0,
                "lembur_jam_bulanan": 22.0,
                "kepuasan_kerja": 3.2,
                "riwayat_absen_90hari": 4,
                "departemen": "IT",
                "status_kerja": "Tetap",
                "pendidikan": "S1",
            }
        }
    }


class AbsensiBatchRequest(BaseModel):
    employees: list[AbsensiInput] = Field(..., min_length=1, max_length=20000)


class AbsensiResult(BaseModel):
    employee_id: str
    probabilitas_absen_tinggi: float
    prediksi_absen_tinggi: int
    level_risiko: str
    threshold: float


class AbsensiBatchResponse(BaseModel):
    hasil: list[AbsensiResult]
    jumlah_diproses: int
    model_version: str
    threshold: float

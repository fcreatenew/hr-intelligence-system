from typing import Literal

from pydantic import BaseModel, Field

from app.schemas.employee import Departemen, Pendidikan


KategoriKinerja = Literal["Rendah", "Sedang", "Tinggi"]


class KinerjaInput(BaseModel):
    employee_id: str = Field(..., examples=["EMP0001"])

    usia: int = Field(..., ge=17, le=75)
    masa_kerja_bulan: int = Field(..., ge=0, le=600)
    jam_lembur_bulanan: float = Field(..., ge=0, le=300)
    jumlah_training_setahun: int = Field(..., ge=0, le=100)
    proyek_selesai: int = Field(..., ge=0, le=200)
    skor_umpan_balik_360: float = Field(..., ge=1, le=5)
    hari_absen_setahun: int = Field(..., ge=0, le=365)

    departemen: Departemen
    pendidikan: Pendidikan

    model_config = {
        "json_schema_extra": {
            "example": {
                "employee_id": "EMP0001",
                "usia": 34,
                "masa_kerja_bulan": 60,
                "jam_lembur_bulanan": 14.0,
                "jumlah_training_setahun": 4,
                "proyek_selesai": 9,
                "skor_umpan_balik_360": 4.1,
                "hari_absen_setahun": 5,
                "departemen": "IT",
                "pendidikan": "S1",
            }
        }
    }


class KinerjaBatchRequest(BaseModel):
    employees: list[KinerjaInput] = Field(..., min_length=1, max_length=20000)


class KinerjaResult(BaseModel):
    employee_id: str
    kategori_prediksi: KategoriKinerja
    probabilitas: dict[str, float]
    skor_ordinal: float
    level: KategoriKinerja


class KinerjaBatchResponse(BaseModel):
    hasil: list[KinerjaResult]
    jumlah_diproses: int
    model_version: str

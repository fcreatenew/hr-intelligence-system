from datetime import date
from typing import Literal, Optional

from pydantic import BaseModel, Field, field_validator


Departemen = Literal[
    "Sales",
    "Marketing",
    "HR",
    "Operations",
    "IT",
    "Finance",
    "Legal",
]

StatusKerja = Literal[
    "Tetap",
    "Kontrak",
    "Freelance",
]

Pendidikan = Literal[
    "SMA",
    "Diploma",
    "S1",
    "S2",
]


class EmployeeInput(BaseModel):
    employee_id: str = Field(
        ...,
        examples=["EMP0001"],
    )

    usia: int = Field(
        ...,
        ge=17,
        le=75,
        description="Usia karyawan dalam tahun",
    )

    masa_kerja_bulan: int = Field(
        ...,
        ge=0,
        le=600,
        description="Lama bekerja dalam bulan",
    )

    jam_lembur_bulanan: int = Field(
        ...,
        ge=0,
        le=300,
        description="Rata-rata jam lembur per bulan",
    )

    gaji_bulanan: float = Field(
        ...,
        gt=0,
        description="Gaji bulanan dalam Rupiah",
    )

    tanggal_masuk: date

    tanggal_promosi: Optional[date] = Field(
        default=None,
        description="Kosongkan jika belum pernah dipromosikan",
    )

    departemen: Departemen

    status_kerja: StatusKerja

    pendidikan: Pendidikan

    @field_validator("tanggal_promosi")
    @classmethod
    def promosi_tidak_boleh_sebelum_masuk(cls, v, info):
        tanggal_masuk = info.data.get("tanggal_masuk")

        if (
            v is not None
            and tanggal_masuk is not None
            and v < tanggal_masuk
        ):
            raise ValueError(
                "tanggal_promosi tidak boleh sebelum tanggal_masuk"
            )

        return v

    model_config = {
        "json_schema_extra": {
            "example": {
                "employee_id": "EMP0001",
                "usia": 38,
                "masa_kerja_bulan": 172,
                "jam_lembur_bulanan": 2,
                "gaji_bulanan": 14514816,
                "tanggal_masuk": "2010-08-12",
                "tanggal_promosi": "2015-07-28",
                "departemen": "Marketing",
                "status_kerja": "Tetap",
                "pendidikan": "S1",
            }
        }
    }
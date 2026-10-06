from __future__ import annotations

from pathlib import Path

import pandas as pd

from src.validation.data_validation import (
    DataValidationConfig,
    validate_dataset,
)


def _valid_frame(n: int = 200) -> pd.DataFrame:
    return pd.DataFrame(
        {
            "employee_id": [f"EMP{i:05d}" for i in range(n)],
            "usia": [30] * n,
            "masa_kerja_bulan": [24] * n,
            "jam_lembur_bulanan": [10.0] * n,
            "gaji_bulanan": [8_000_000] * n,
            "tanggal_masuk": ["2024-01-01"] * n,
            "tanggal_promosi": ["2024-06-01"] * n,
            "departemen": ["IT"] * n,
            "status_kerja": ["Tetap"] * n,
            "pendidikan": ["S1"] * n,
            "target_turnover": [0, 1] * (n // 2),
        }
    )


def test_valid_dataset_passes():
    hasil = validate_dataset(_valid_frame())

    assert hasil["lulus"] is True
    assert hasil["masalah"] == []


def test_missing_required_column_is_reported():
    df = _valid_frame().drop(columns=["gaji_bulanan"])

    hasil = validate_dataset(df)

    assert hasil["lulus"] is False
    assert any(m["jenis"] == "kolom_hilang" for m in hasil["masalah"])


def test_pii_column_is_rejected():
    df = _valid_frame()
    df["nama"] = "Budi"

    hasil = validate_dataset(df)

    assert any(m["jenis"] == "pii" for m in hasil["masalah"])


def test_out_of_range_numeric_is_reported():
    df = _valid_frame()
    df.loc[0, "usia"] = 200

    hasil = validate_dataset(df)

    assert any(m["jenis"] == "rentang_nilai" for m in hasil["masalah"])


def test_unknown_category_is_reported():
    df = _valid_frame()
    df.loc[0, "departemen"] = "UnknownDept"

    hasil = validate_dataset(df)

    assert any(m["jenis"] == "kategori_tak_dikenal" for m in hasil["masalah"])


def test_non_binary_target_is_reported():
    df = _valid_frame()
    df.loc[0, "target_turnover"] = 7

    hasil = validate_dataset(df)

    assert any(m["jenis"] == "target_tidak_biner" for m in hasil["masalah"])


def test_high_missing_ratio_is_reported():
    df = _valid_frame()
    df.loc[:150, "gaji_bulanan"] = None

    hasil = validate_dataset(df)

    assert any(m["jenis"] == "nilai_kosong" for m in hasil["masalah"])


def test_too_few_rows_is_reported():
    hasil = validate_dataset(_valid_frame(10), DataValidationConfig(min_rows=100))

    assert any(m["jenis"] == "jumlah_baris" for m in hasil["masalah"])


def test_loads_csv_from_path(tmp_path: Path):
    path = tmp_path / "dataset.csv"
    _valid_frame().to_csv(path, index=False)

    hasil = validate_dataset(path)

    assert hasil["lulus"] is True
    assert hasil["ringkasan"]["baris"] == 200


def test_missing_file_raises():
    try:
        validate_dataset("/tmp/tidak-ada-dataset-xyz.csv")
    except FileNotFoundError:
        return

    raise AssertionError("validate_dataset harus melempar FileNotFoundError")

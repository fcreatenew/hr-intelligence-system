import os

os.environ.setdefault("HR_API_KEY", "test-key")

import pytest
from fastapi.testclient import TestClient

from app.core.auth import _request_log
from app.core.config import API_KEY
from app.main import app

HEADERS = {"X-API-Key": API_KEY}


ABSENSI = {
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

KINERJA = {
    "employee_id": "EMP0002",
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

PAYROLL_NORMAL = {
    "employee_id": "EMP0003",
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

PAYROLL_ANOMALI = {
    **PAYROLL_NORMAL,
    "employee_id": "EMP0004",
    "lembur_jam": 380,
    "tarif_lembur": 260000,
    "total_gaji": 107040000,
}

SURVIVAL = {
    "employee_id": "EMP0005",
    "usia": 34,
    "gaji_bulanan": 9500000,
    "jam_lembur_bulanan": 18.0,
    "kepuasan_kerja": 3.4,
    "bulan_sejak_promosi": 22.0,
    "jarak_km": 11.5,
    "departemen": "IT",
    "pendidikan": "S1",
}


@pytest.fixture()
def client():
    _request_log.clear()
    with TestClient(app) as c:
        yield c


def test_health_reports_all_models(client):
    body = client.get("/health").json()
    assert body["status"] == "ok"
    assert body["models_loaded"] == body["models_total"] >= 5
    assert set(body["models"]) >= {"turnover", "absensi", "kinerja", "payroll", "survival"}
    assert all(body["models"].values())


def test_model_listing(client):
    body = client.get("/model", headers=HEADERS).json()
    keys = {u["use_case"] for u in body["use_cases"]}
    assert {"turnover", "absensi", "kinerja", "payroll", "survival"} <= keys


@pytest.mark.parametrize(
    "use_case",
    ["turnover", "absensi", "kinerja", "payroll", "survival"],
)
def test_model_info_per_use_case(client, use_case):
    resp = client.get(f"/model/{use_case}/info", headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["use_case"] == use_case
    assert body["versi_model"].startswith("v")


def test_model_info_unknown_use_case_returns_404(client):
    assert client.get("/model/tidak-ada/info", headers=HEADERS).status_code == 404


def test_absensi_predict(client):
    resp = client.post("/predict/absensi", json=ABSENSI, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert 0.0 <= body["probabilitas_absen_tinggi"] <= 1.0
    assert body["prediksi_absen_tinggi"] in (0, 1)
    assert body["level_risiko"] in ("Rendah", "Sedang", "Tinggi")


def test_absensi_predict_requires_api_key(client):
    assert client.post("/predict/absensi", json=ABSENSI).status_code == 422


def test_absensi_rejects_invalid_range(client):
    payload = {**ABSENSI, "kepuasan_kerja": 9}
    assert client.post("/predict/absensi", json=payload, headers=HEADERS).status_code == 422


def test_absensi_batch(client):
    payload = {"employees": [ABSENSI, {**ABSENSI, "employee_id": "EMP0002"}]}
    resp = client.post("/predict/absensi/batch", json=payload, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["jumlah_diproses"] == 2
    assert body["model_version"].startswith("v")


def test_kinerja_predict(client):
    resp = client.post("/predict/kinerja", json=KINERJA, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["kategori_prediksi"] in ("Rendah", "Sedang", "Tinggi")
    assert set(body["probabilitas"]) == {"Rendah", "Sedang", "Tinggi"}
    assert abs(sum(body["probabilitas"].values()) - 1.0) < 1e-3
    assert 1.0 <= body["skor_ordinal"] <= 3.0


def test_kinerja_batch(client):
    payload = {"employees": [KINERJA]}
    resp = client.post("/predict/kinerja/batch", json=payload, headers=HEADERS)
    assert resp.status_code == 200
    assert resp.json()["jumlah_diproses"] == 1


def test_payroll_normal_vs_anomali(client):
    normal = client.post("/predict/payroll", json=PAYROLL_NORMAL, headers=HEADERS).json()
    anomali = client.post("/predict/payroll", json=PAYROLL_ANOMALI, headers=HEADERS).json()

    assert normal["level"] == "Normal"
    assert anomali["is_anomali"] is True
    assert anomali["level"] == "Anomali"
    assert anomali["skor_anomali"] > normal["skor_anomali"]


def test_payroll_batch(client):
    payload = {"employees": [PAYROLL_NORMAL, PAYROLL_ANOMALI]}
    resp = client.post("/predict/payroll/batch", json=payload, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["jumlah_diproses"] == 2
    assert "threshold" in body


def test_survival_predict(client):
    resp = client.post("/predict/survival", json=SURVIVAL, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["risiko_relatif"] > 0
    assert body["level_risiko"] in ("Rendah", "Sedang", "Tinggi")
    assert body["median_survival_bulan"] is None or body["median_survival_bulan"] > 0


def test_survival_batch_reports_baseline(client):
    payload = {"employees": [SURVIVAL]}
    resp = client.post("/predict/survival/batch", json=payload, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["jumlah_diproses"] == 1
    assert body["baseline_median_survival_bulan"] > 0

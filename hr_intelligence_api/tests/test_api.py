import os

os.environ.setdefault("HR_API_KEY", "test-key")

import pytest
from fastapi.testclient import TestClient

from app.core.config import API_KEY
from app.main import app

client = TestClient(app)
HEADERS = {"X-API-Key": API_KEY}

VALID_EMPLOYEE = {
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


def test_health_check_no_auth_needed():
    resp = client.get("/health")
    assert resp.status_code == 200
    body = resp.json()
    assert body["status"] == "ok"
    assert body["model_loaded"] is True


def test_predict_requires_api_key():
    resp = client.post("/predict", json=VALID_EMPLOYEE)
    assert resp.status_code == 422  # header X-API-Key wajib, hilang -> validation error


def test_predict_rejects_wrong_api_key():
    resp = client.post("/predict", json=VALID_EMPLOYEE, headers={"X-API-Key": "salah"})
    assert resp.status_code == 401


def test_predict_single_employee_success():
    resp = client.post("/predict", json=VALID_EMPLOYEE, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["employee_id"] == "EMP0001"
    assert 0.0 <= body["probabilitas_keluar"] <= 1.0
    assert body["prediksi_keluar"] in (0, 1)
    assert body["level_risiko"] in ("Rendah", "Sedang", "Tinggi")


def test_predict_without_promosi_is_valid():
    payload = {**VALID_EMPLOYEE, "tanggal_promosi": None}
    resp = client.post("/predict", json=payload, headers=HEADERS)
    assert resp.status_code == 200


def test_predict_rejects_promosi_before_masuk():
    payload = {**VALID_EMPLOYEE, "tanggal_masuk": "2020-01-01", "tanggal_promosi": "2019-01-01"}
    resp = client.post("/predict", json=payload, headers=HEADERS)
    assert resp.status_code == 422


def test_predict_rejects_invalid_departemen():
    payload = {**VALID_EMPLOYEE, "departemen": "Tidak Ada Departemen Ini"}
    resp = client.post("/predict", json=payload, headers=HEADERS)
    assert resp.status_code == 422


def test_predict_rejects_negative_age():
    payload = {**VALID_EMPLOYEE, "usia": -5}
    resp = client.post("/predict", json=payload, headers=HEADERS)
    assert resp.status_code == 422


def test_predict_rejects_missing_required_field():
    payload = {k: v for k, v in VALID_EMPLOYEE.items() if k != "gaji_bulanan"}
    resp = client.post("/predict", json=payload, headers=HEADERS)
    assert resp.status_code == 422


def test_predict_batch_success():
    payload = {"employees": [VALID_EMPLOYEE, {**VALID_EMPLOYEE, "employee_id": "EMP0002"}]}
    resp = client.post("/predict/batch", json=payload, headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert body["jumlah_diproses"] == 2
    assert len(body["hasil"]) == 2


def test_predict_batch_rejects_empty_list():
    resp = client.post("/predict/batch", json={"employees": []}, headers=HEADERS)
    assert resp.status_code == 422


def test_model_info_returns_metrics():
    resp = client.get("/model/info", headers=HEADERS)
    assert resp.status_code == 200
    body = resp.json()
    assert "metrik_holdout_test" in body
    assert "threshold" in body


def test_rate_limit_eventually_blocks(monkeypatch):
    monkeypatch.setattr("app.auth.RATE_LIMIT_PER_MINUTE", 3)
    # reset counter supaya tidak kena sisa request dari test lain
    from app.core.auth import _request_log
    _request_log.clear()

    responses = [
        client.post("/predict", json=VALID_EMPLOYEE, headers={"X-API-Key": API_KEY})
        for _ in range(5)
    ]
    statuses = [r.status_code for r in responses]
    assert 429 in statuses, f"Diharapkan ada 429 setelah limit terlampaui, dapat: {statuses}"

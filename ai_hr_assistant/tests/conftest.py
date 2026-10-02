from __future__ import annotations

import httpx
import pytest

from src.embeddings.embedder import HashingEmbedder
from src.memory.session import SessionMemory
from src.rag.service import RagService
from src.tools.hr_intelligence_client import HrIntelligenceClient
from src.tools.prediction_tools import build_tool_registry
from src.agents.hr_assistant import HrAssistantAgent
from src.llm.provider import MockLLM


# Respons contoh per use case, meniru bentuk asli HR Intelligence API.
_PREDICTIONS = {
    "/predict": {
        "employee_id": "EMP0001",
        "probabilitas_keluar": 0.68,
        "prediksi_keluar": 1,
        "level_risiko": "Tinggi",
    },
    "/predict/absensi": {
        "employee_id": "EMP0001",
        "probabilitas_absen_tinggi": 0.41,
        "prediksi_absen_tinggi": 1,
        "level_risiko": "Sedang",
        "threshold": 0.19,
    },
    "/predict/kinerja": {
        "employee_id": "EMP0001",
        "kategori_prediksi": "Sedang",
        "probabilitas": {"Rendah": 0.2, "Sedang": 0.6, "Tinggi": 0.2},
        "skor_ordinal": 2.0,
        "level": "Sedang",
    },
    "/predict/payroll": {
        "employee_id": "EMP0001",
        "periode": "2025-01",
        "skor_anomali": 0.82,
        "is_anomali": True,
        "level": "Anomali",
        "threshold": 0.5,
    },
    "/predict/survival": {
        "employee_id": "EMP0001",
        "risiko_relatif": 2.4,
        "level_risiko": "Tinggi",
        "median_survival_bulan": 9.0,
    },
}


def _handler(request: httpx.Request) -> httpx.Response:
    path = request.url.path

    if path == "/health":
        return httpx.Response(
            200,
            json={
                "status": "ok",
                "models_loaded": 5,
                "models_total": 5,
                "model_loaded": True,
                "models": {k: True for k in ("turnover", "absensi", "kinerja", "payroll", "survival")},
            },
        )

    if path == "/model":
        return httpx.Response(
            200,
            json={
                "use_cases": [
                    {"use_case": uc, "label": uc, "versi_model": "v1", "model": "test",
                     "threshold": None, "predict_path": p, "loaded": True}
                    for uc, p in (
                        ("turnover", "/predict"),
                        ("absensi", "/predict/absensi"),
                        ("kinerja", "/predict/kinerja"),
                        ("payroll", "/predict/payroll"),
                        ("survival", "/predict/survival"),
                    )
                ]
            },
        )

    if path == "/model/info" or path.startswith("/model/") and path.endswith("/info"):
        return httpx.Response(
            200,
            json={
                "use_case": "turnover",
                "versi_model": "v2",
                "label": "Turnover karyawan",
                "model": "Soft Voting (ensemble)",
                "dibuat_pada": "2026-10-01T00:00:00",
                "threshold": 0.33,
                "fitur_numerik": [],
                "fitur_kategorik": [],
                "metrik": {"roc_auc": 0.6993},
                "catatan": "",
            },
        )

    if path in _PREDICTIONS:
        body = dict(_PREDICTIONS[path])
        if request.headers.get("X-API-Key") != "test-key":
            return httpx.Response(401, json={"detail": "API key tidak valid"})
        return httpx.Response(200, json=body)

    return httpx.Response(404, json={"detail": f"tidak ada rute {path}"})


@pytest.fixture
def fake_client() -> HrIntelligenceClient:
    transport = httpx.MockTransport(_handler)
    client = httpx.Client(transport=transport, base_url="http://testserver")

    return HrIntelligenceClient(
        base_url="http://testserver",
        api_key="test-key",
        retry_times=1,
        client=client,
    )


@pytest.fixture
def rag() -> RagService:
    return RagService(embedder=HashingEmbedder(dim=256))


@pytest.fixture
def tools(fake_client):
    return build_tool_registry(fake_client)


@pytest.fixture
def agent(rag, tools) -> HrAssistantAgent:
    return HrAssistantAgent(
        rag=rag,
        tools=tools,
        llm=MockLLM(),
        memory=SessionMemory(),
    )

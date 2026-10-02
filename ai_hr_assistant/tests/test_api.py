import pytest
from fastapi.testclient import TestClient

from src.api import auth as auth_module
from src.api import routes
from src.main import app
from src.services.container import Container


API_KEY = "kunci-uji"


@pytest.fixture(autouse=True)
def _api_key(monkeypatch):
    monkeypatch.setattr(auth_module, "API_KEY", API_KEY)
    auth_module._request_log.clear()


def _headers() -> dict[str, str]:
    return {"X-API-Key": API_KEY}


def _build_container(agent) -> Container:
    """Container uji yang memakai agent dengan API & LLM yang sudah di-fake."""
    container = object.__new__(Container)
    container.rag = agent.rag
    container.tools = agent.tools
    container.memory = agent.memory
    container.agent = agent
    container.client = _FakeClient()

    return container


class _FakeClient:
    def health(self):
        return {"status": "ok", "models_loaded": 5, "models_total": 5}

    def list_use_cases(self):
        return {"use_cases": [{"use_case": "turnover"}]}


def test_health_endpoint(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/health")

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert body["knowledge_documents"] >= 10
    assert "predict_turnover" in body["tools"]


def test_chat_endpoint_returns_answer(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.post(
            "/chat",
            json={"message": "Berapa risiko turnover karyawan EMP0001?", "employee_id": "EMP0001"},
            headers=_headers(),
        )

    assert response.status_code == 200
    body = response.json()
    assert body["intent"] == "employee_insight"
    assert body["tool_calls"]
    assert body["session_id"]


def test_chat_validation_rejects_empty(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.post("/chat", json={"message": ""}, headers=_headers())

    assert response.status_code == 422


def test_tools_endpoint(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/tools", headers=_headers())

    assert response.status_code == 200
    names = {tool["name"] for tool in response.json()["tools"]}
    assert {"predict_turnover", "predict_payroll", "list_models"} <= names


def test_knowledge_endpoint(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/knowledge", headers=_headers())

    assert response.status_code == 200
    body = response.json()
    assert len(body["documents"]) >= 10


def test_protected_endpoint_rejects_missing_key(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/tools")

    assert response.status_code == 422


def test_protected_endpoint_rejects_wrong_key(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/tools", headers={"X-API-Key": "salah"})

    assert response.status_code == 401


def test_health_stays_open(agent, monkeypatch):
    monkeypatch.setattr(routes, "get_container", lambda: _build_container(agent))

    with TestClient(app) as client:
        response = client.get("/health")

    assert response.status_code == 200

import os

os.environ.setdefault("HR_API_KEY", "test-key")

import pytest
from fastapi.testclient import TestClient

from app.core import runtime
from app.main import app


@pytest.fixture()
def client():
    with TestClient(app) as c:
        yield c


@pytest.fixture(autouse=True)
def _reset_thread_state():
    """Isolasi global runtime antar test."""
    previous = runtime._ACTIVE_THREAD_LIMIT
    runtime._ACTIVE_THREAD_LIMIT = None

    yield

    runtime._ACTIVE_THREAD_LIMIT = previous


def test_configure_thread_limits_sets_env(monkeypatch):
    monkeypatch.setenv("HR_NUM_THREADS", "2")

    result = runtime.configure_thread_limits()

    assert result == 2
    assert runtime.active_thread_limit() == 2
    for name in runtime._THREAD_ENV_VARS:
        assert os.environ[name] == "2"


def test_configure_thread_limits_empty_is_default(monkeypatch):
    monkeypatch.setenv("HR_NUM_THREADS", "  ")

    assert runtime.configure_thread_limits() is None


def test_configure_thread_limits_rejects_invalid(monkeypatch):
    monkeypatch.setenv("HR_NUM_THREADS", "bukan-angka")

    assert runtime.configure_thread_limits() is None


def test_configure_thread_limits_rejects_non_positive(monkeypatch):
    monkeypatch.setenv("HR_NUM_THREADS", "0")

    assert runtime.configure_thread_limits() is None


def test_health_reports_cpu_device(client):
    body = client.get("/health").json()

    assert body["compute_device"] == "cpu"
    assert body["thread_limit"] is None

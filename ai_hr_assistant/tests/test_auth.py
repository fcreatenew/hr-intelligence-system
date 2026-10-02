import pytest
from fastapi import HTTPException

from src.api import auth as auth_module
from src.api.auth import rate_limiter, verify_api_key


def test_verify_api_key_rejects_wrong_key(monkeypatch):
    monkeypatch.setattr(auth_module, "API_KEY", "rahasia")

    with pytest.raises(HTTPException) as exc:
        verify_api_key("salah")

    assert exc.value.status_code == 401


def test_verify_api_key_accepts_correct_key(monkeypatch):
    monkeypatch.setattr(auth_module, "API_KEY", "rahasia")

    assert verify_api_key("rahasia") == "rahasia"


def test_verify_api_key_fails_closed_when_unset(monkeypatch):
    monkeypatch.setattr(auth_module, "API_KEY", "")

    with pytest.raises(HTTPException) as exc:
        verify_api_key("apa saja")

    assert exc.value.status_code == 500


def test_rate_limiter_blocks_after_limit(monkeypatch):
    monkeypatch.setattr(auth_module, "API_KEY", "rahasia")
    monkeypatch.setattr(auth_module, "RATE_LIMIT_PER_MINUTE", 2)
    auth_module._request_log.clear()

    rate_limiter("key-uji")
    rate_limiter("key-uji")

    with pytest.raises(HTTPException) as exc:
        rate_limiter("key-uji")

    assert exc.value.status_code == 429
    auth_module._request_log.clear()

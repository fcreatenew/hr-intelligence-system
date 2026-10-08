"""Integrasi ModelService dengan sumber Hugging Face Hub (download di-mock)."""

import os

os.environ.setdefault("HR_API_KEY", "test-key")

import pytest

from app.core import hf_store
from app.core.config import MODELS_DIR
from app.services.model_service import ModelService


def test_model_service_loads_artifact_from_hub(monkeypatch):
    """ModelService memakai hf_store saat sumber `hub`, bukan path lokal."""
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")

    calls: list[str] = []

    def fake_download(filename, **_kwargs):
        calls.append(filename)
        return str(MODELS_DIR / filename)

    import huggingface_hub

    monkeypatch.setattr(huggingface_hub, "hf_hub_download", fake_download)

    service = ModelService(
        use_case="turnover",
        model_path="v2_mitigated/turnover_model.joblib",
        metadata_path="v2_mitigated/model_metadata.json",
    )
    service.load()

    assert service.is_loaded
    assert service.model_version == "v2_mitigated"
    assert "v2_mitigated/turnover_model.joblib" in calls
    assert "v2_mitigated/model_metadata.json" in calls
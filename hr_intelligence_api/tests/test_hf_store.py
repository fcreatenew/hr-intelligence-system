"""Test pengambilan artifact dari Hugging Face Hub (`hf_store`).

Download di-mock agar test berjalan tanpa jaringan, namun jalur kode
pembentukan nama berkas dan pemilihan sumber tetap diuji nyata.
"""

import json

import pytest

from app.core import hf_store


def test_local_source_reads_from_models_dir(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "local")

    path = hf_store.fetch("v2_mitigated/turnover_model.joblib")

    assert path.name == "turnover_model.joblib"
    assert path.parent.name == "v2_mitigated"
    assert path.exists()


def test_local_source_reads_pinned_json(monkeypatch, tmp_path):
    monkeypatch.setenv("HR_MODEL_SOURCE", "local")
    monkeypatch.setattr(hf_store, "MODELS_DIR", tmp_path)

    (tmp_path / "active_version.json").write_text(
        json.dumps({"turnover": "v2"}), encoding="utf-8"
    )

    assert hf_store.read_json("active_version.json") == {"turnover": "v2"}


def test_hub_source_downloads_with_prefixed_path(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")
    monkeypatch.setattr(hf_store, "HF_MODEL_TOKEN", "tok")

    calls: list[dict] = []

    def fake_download(filename, **kwargs):
        calls.append({"filename": filename, **kwargs})
        return "/cache/models/v2_mitigated/turnover_model.joblib"

    import huggingface_hub

    monkeypatch.setattr(huggingface_hub, "hf_hub_download", fake_download)

    path = hf_store.fetch("v2_mitigated/turnover_model.joblib")

    assert str(path) == "/cache/models/v2_mitigated/turnover_model.joblib"
    assert calls == [
        {
            "filename": "models/v2_mitigated/turnover_model.joblib",
            "repo_id": "user/repo",
            "repo_type": "model",
            "revision": "main",
            "token": "tok",
        }
    ]


def test_hub_directory_download_uses_allow_patterns(monkeypatch, tmp_path):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")

    calls: list[dict] = []

    def fake_snapshot(**kwargs):
        calls.append(kwargs)
        return str(tmp_path)

    import huggingface_hub

    monkeypatch.setattr(huggingface_hub, "snapshot_download", fake_snapshot)

    (tmp_path / "models" / "performance" / "v1").mkdir(parents=True)

    result = hf_store.fetch_directory("performance/v1")

    assert result == tmp_path / "models" / "performance" / "v1"
    assert calls[0]["allow_patterns"] == ["models/performance/v1/*"]
    assert calls[0]["repo_id"] == "user/repo"


def test_hub_requires_repo_id(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "")

    with pytest.raises(RuntimeError):
        hf_store.fetch("v1/turnover_model.joblib")
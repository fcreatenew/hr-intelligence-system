"""Test pengambilan artifact dari Hugging Face Hub (`hf_store`).

Download di-mock agar test berjalan tanpa jaringan, namun jalur kode
pembentukan nama berkas dan pemilihan sumber tetap diuji nyata.
"""

import json
import os
from pathlib import Path

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


def test_hub_source_downloads_without_prefix(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")
    monkeypatch.setattr(hf_store, "HF_MODEL_TOKEN", "tok")

    calls: list[dict] = []

    def fake_download(filename, **kwargs):
        calls.append({"filename": filename, **kwargs})
        return "/cache/v2_mitigated/turnover_model.joblib"

    import huggingface_hub

    monkeypatch.setattr(huggingface_hub, "hf_hub_download", fake_download)

    path = hf_store.fetch("v2_mitigated/turnover_model.joblib")

    assert str(path) == "/cache/v2_mitigated/turnover_model.joblib"
    assert len(calls) == 1

    call = calls[0]
    # Tanpa prefix `models/` — artifact berada di root repo Hub.
    assert call["filename"] == "v2_mitigated/turnover_model.joblib"
    assert call["repo_id"] == "user/repo"
    assert call["repo_type"] == "model"
    assert call["revision"] == "main"
    assert call["token"] == "tok"
    # cache_dir menunjuk direktori writable (fallback cache).
    assert Path(call["cache_dir"]).is_dir()


def test_hub_directory_download_uses_allow_patterns(monkeypatch, tmp_path):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")

    calls: list[dict] = []

    def fake_snapshot(**kwargs):
        calls.append(kwargs)
        return str(tmp_path)

    import huggingface_hub

    monkeypatch.setattr(huggingface_hub, "snapshot_download", fake_snapshot)

    (tmp_path / "performance" / "v1").mkdir(parents=True)

    result = hf_store.fetch_directory("performance/v1")

    assert result == tmp_path / "performance" / "v1"
    assert calls[0]["allow_patterns"] == ["performance/v1/*"]
    assert calls[0]["repo_id"] == "user/repo"


def test_hub_requires_repo_id(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "")

    with pytest.raises(RuntimeError):
        hf_store.fetch("v1/turnover_model.joblib")


def test_cache_dir_falls_back_when_hf_home_readonly(monkeypatch, tmp_path):
    """HF_HOME di mount read-only tidak boleh menggagalkan unduhan.

    Regresi untuk `OSError: [Errno 30] Read-only file system: '/data'`.
    """
    readonly = tmp_path / "readonly"
    readonly.mkdir()
    readonly.chmod(0o500)  # tidak bisa membuat file di dalamnya

    fallback = tmp_path / "fallback"
    fallback.mkdir()

    monkeypatch.setenv("HF_HOME", str(readonly))
    monkeypatch.delenv("HF_HUB_CACHE", raising=False)
    monkeypatch.setattr(
        hf_store, "_candidate_cache_dirs", lambda: [readonly, fallback]
    )
    hf_store.cache_dir.cache_clear()

    try:
        assert hf_store.cache_dir() == fallback
    finally:
        hf_store.cache_dir.cache_clear()
        readonly.chmod(0o700)


def test_hub_kwargs_include_writable_cache_dir(monkeypatch, tmp_path):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")

    cache = tmp_path / "cache"
    monkeypatch.setattr(hf_store, "cache_dir", lambda: cache)

    assert hf_store._hub_kwargs()["cache_dir"] == str(cache)


def test_hub_kwargs_omits_cache_dir_when_none(monkeypatch):
    monkeypatch.setenv("HR_MODEL_SOURCE", "hub")
    monkeypatch.setattr(hf_store, "HF_MODEL_REPO", "user/repo")
    monkeypatch.setattr(hf_store, "cache_dir", lambda: None)

    assert "cache_dir" not in hf_store._hub_kwargs()


def test_config_cache_env_redirects_xet_cache(monkeypatch, tmp_path):
    """HF_XET_CACHE harus ikut dialihkan, bukan hanya cache_dir.

    Backend ``hf_xet`` memakai ``HF_XET_CACHE`` yang diturunkan saat import dan
    tak terpengaruh argumen ``cache_dir=``; bila ``HF_HOME`` read-only,
    unduhan gagal ``OSError: [Errno 30]``.
    """
    readonly = tmp_path / "readonly"
    readonly.mkdir()
    readonly.chmod(0o500)

    writable = tmp_path / "writable" / "hub"
    writable.mkdir(parents=True)

    monkeypatch.setattr(
        hf_store, "_candidate_cache_dirs", lambda: [readonly, writable]
    )

    try:
        resolved = hf_store._configure_cache_env()

        assert resolved == writable
        assert os.environ["HF_HUB_CACHE"] == str(writable)
        assert os.environ["HF_XET_CACHE"] == str(writable.parent / "xet")
        assert os.environ["HF_HOME"] == str(writable.parent)
    finally:
        readonly.chmod(0o700)
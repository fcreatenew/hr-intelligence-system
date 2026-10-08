"""Akses artifact model di Hugging Face Hub untuk modul MLOps.

Registry MLOps secara default masih bekerja pada folder ``models/`` lokal
(training menulis ke sana dan test bersifat hermetic). Bila ``HR_MODEL_SOURCE``
di-set ``hub``, artifact yang **tidak ada** di lokal akan diunduh dari Hub
sehingga registry, validasi, dan audit fairness tetap jalan tanpa salinan lokal.

Struktur repo Hub sama dengan yang dipakai ``hr_intelligence_api``, yaitu
artifact berada **langsung di root repo** (tanpa folder ``models/``)::

    <versi>/…                   # turnover: v1, v2, v2_mitigated
    <use-case>/<versi>/…        # absensi, kinerja, payroll, survival
"""

from __future__ import annotations

import json
import os
import tempfile
from functools import lru_cache
from pathlib import Path

from src.config import MODELS_DIR


# Artifact berada langsung di root repo Hub (tanpa folder ``models/``),
# sinkron dengan scripts/upload_models_to_hf.py.
HUB_ROOT = ""


def repo_id() -> str:
    return os.getenv("HR_MODEL_REPO", "fcreatenew/hr-intelligence-models")


def repo_type() -> str:
    return os.getenv("HR_MODEL_REPO_TYPE", "model").strip().lower()


def revision() -> str:
    return os.getenv("HR_MODEL_REVISION", "main")


def token() -> str | None:
    return (
        os.getenv("HF_TOKEN")
        or os.getenv("HUGGINGFACE_HUB_TOKEN")
        or os.getenv("HR_MODEL_TOKEN")
        or None
    )


def source() -> str:
    """Sumber artifact MLOps (``local`` default, ``hub`` bila diaktifkan)."""
    return os.getenv("HR_MODEL_SOURCE", "local").strip().lower()


def _candidate_cache_dirs() -> list[Path]:
    candidates: list[Path] = []

    hub_cache = os.getenv("HF_HUB_CACHE")
    if hub_cache:
        candidates.append(Path(hub_cache))

    hf_home = os.getenv("HF_HOME")
    if hf_home:
        candidates.append(Path(hf_home) / "hub")

    candidates.append(Path.home() / ".cache" / "huggingface" / "hub")
    candidates.append(Path(tempfile.gettempdir()) / "hf-cache" / "hub")

    return candidates


def _writable(path: Path) -> bool:
    try:
        path.mkdir(parents=True, exist_ok=True)
        probe = path / ".write_probe"
        probe.touch()
        probe.unlink()
        return True
    except OSError:
        return False


def _resolve_cache() -> Path | None:
    for candidate in _candidate_cache_dirs():
        if _writable(candidate):
            return candidate
    return None


def _configure_cache_env() -> Path | None:
    """Arahkan cache Hub (termasuk ``HF_XET_CACHE``) ke direktori writable.

    Harus dijalankan **sebelum** ``huggingface_hub`` di-import: konstanta cache
    diturunkan dari environment saat import, dan backend ``hf_xet`` memakai
    ``HF_XET_CACHE`` yang tak terpengaruh argumen ``cache_dir=``.
    """
    resolved = _resolve_cache()
    if resolved is None:
        return None

    home = resolved.parent
    os.environ["HF_HOME"] = str(home)
    os.environ["HF_HUB_CACHE"] = str(resolved)
    os.environ["HF_XET_CACHE"] = str(home / "xet")
    return resolved


_configure_cache_env()


@lru_cache(maxsize=1)
def cache_dir() -> Path | None:
    """Direktori cache Hub pertama yang dapat ditulis (hindari mount read-only)."""
    return _resolve_cache()


def _kwargs() -> dict:
    kwargs = {
        "repo_id": repo_id(),
        "repo_type": repo_type(),
        "revision": revision(),
        "token": token(),
    }

    resolved = cache_dir()
    if resolved is not None:
        kwargs["cache_dir"] = str(resolved)

    return kwargs


def _hub_filename(relative_path: str) -> str:
    """Nama berkas di repo Hub (relatif ke root repo, tanpa prefix)."""
    relative_path = relative_path.lstrip("/")
    return f"{HUB_ROOT}/{relative_path}" if HUB_ROOT else relative_path


def fetch(relative_path: str) -> Path:
    """Unduh satu berkas artifact dari Hub dan kembalikan path cache-nya."""
    from huggingface_hub import hf_hub_download

    return Path(
        hf_hub_download(
            filename=_hub_filename(relative_path),
            **_kwargs(),
        )
    )


def read_json(relative_path: str) -> dict:
    return json.loads(fetch(relative_path).read_text(encoding="utf-8"))


def fetch_directory(relative_dir: str) -> Path:
    """Unduh seluruh folder artifact dari Hub dan kembalikan path cache-nya."""
    from huggingface_hub import snapshot_download

    relative_dir = relative_dir.strip("/")

    local_root = snapshot_download(
        allow_patterns=[f"{_hub_filename(relative_dir)}/*"],
        **_kwargs(),
    )

    return Path(local_root) / _hub_filename(relative_dir)


def is_hub_enabled() -> bool:
    return source() == "hub"
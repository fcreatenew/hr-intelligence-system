"""Akses artifact model di Hugging Face Hub untuk modul MLOps.

Registry MLOps secara default masih bekerja pada folder ``models/`` lokal
(training menulis ke sana dan test bersifat hermetic). Bila ``HR_MODEL_SOURCE``
di-set ``hub``, artifact yang **tidak ada** di lokal akan diunduh dari Hub
sehingga registry, validasi, dan audit fairness tetap jalan tanpa salinan lokal.

Struktur repo Hub sama dengan yang dipakai ``hr_intelligence_api``::

    models/<versi>/…            # turnover: v1, v2, v2_mitigated
    models/<use-case>/<versi>/… # absensi, kinerja, payroll, survival
"""

from __future__ import annotations

import json
import os
from pathlib import Path

from src.config import MODELS_DIR


HUB_ROOT = "models"


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


def _kwargs() -> dict:
    return {
        "repo_id": repo_id(),
        "repo_type": repo_type(),
        "revision": revision(),
        "token": token(),
    }


def fetch(relative_path: str) -> Path:
    """Unduh satu berkas artifact dari Hub dan kembalikan path cache-nya."""
    from huggingface_hub import hf_hub_download

    relative_path = relative_path.lstrip("/")

    return Path(
        hf_hub_download(
            filename=f"{HUB_ROOT}/{relative_path}",
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
        allow_patterns=[f"{HUB_ROOT}/{relative_dir}/*"],
        **_kwargs(),
    )

    return Path(local_root) / HUB_ROOT / relative_dir


def is_hub_enabled() -> bool:
    return source() == "hub"
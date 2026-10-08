"""Pengambilan artifact model dari Hugging Face Hub.

Runtime API tidak lagi membaca artifact dari disk lokal. Semua artifact
(model + metadata) berada di satu repo Hub dengan struktur:

    models/
    ├── active_version.json          # pin versi aktif (ditulis MLOps)
    ├── v1/turnover_model.joblib
    ├── v2_mitigated/turnover_model.joblib
    ├── absenteeism/v1/absenteeism_model.joblib
    ├── performance/v1/performance_model.joblib
    ├── payroll_anomaly/v1/payroll_anomaly_model.joblib
    └── survival_attrition/v1/cox_model.pkl

`HR_MODEL_SOURCE` memilih sumber:

* ``hub`` (default) — unduh dari Hub, lalu di-cache oleh ``huggingface_hub``
  di ``HF_HOME``. Ini mode produksi.
* ``local`` — baca dari folder ``models/`` di repo. Dipakai untuk test
  hermetic dan pengembangan tanpa jaringan.

Modul ini sengaja menjadi satu-satunya pintu baca artifact agar ``ModelService``
dan ``config`` tidak pernah menyentuh path lokal secara langsung.
"""

from __future__ import annotations

import json
import os
import tempfile
from functools import lru_cache
from pathlib import Path

from app.core.config import (
    HF_MODEL_REPO,
    HF_MODEL_REPO_TYPE,
    HF_MODEL_REVISION,
    HF_MODEL_TOKEN,
    MODELS_DIR,
)


# Folder di dalam repo Hub yang menampung seluruh artifact.
HUB_ROOT = "models"


def source() -> str:
    """Sumber artifact aktif (``hub`` atau ``local``)."""
    return os.getenv("HR_MODEL_SOURCE", "hub").strip().lower()


def _candidate_cache_dirs() -> list[Path]:
    """Kandidat direktori cache Hub, sesuai prioritas."""
    candidates: list[Path] = []

    hub_cache = os.getenv("HF_HUB_CACHE")
    if hub_cache:
        candidates.append(Path(hub_cache))

    hf_home = os.getenv("HF_HOME")
    if hf_home:
        candidates.append(Path(hf_home) / "hub")

    # Fallback yang hampir selalu writable.
    candidates.append(Path(tempfile.gettempdir()) / "hf-cache" / "hub")
    candidates.append(Path.home() / ".cache" / "huggingface" / "hub")

    return candidates


@lru_cache(maxsize=1)
def cache_dir() -> Path | None:
    """Direktori cache pertama yang benar-benar dapat ditulis.

    HF_HOME/HF_HUB_CACHE sering diarahkan ke mount read-only (mis. ``/data``),
    yang membuat unduhan gagal dengan ``OSError: [Errno 30] Read-only file
    system``. Fungsi ini memilih kandidat writable pertama sehingga runtime
    tetap dapat mengambil artifact.
    """
    for candidate in _candidate_cache_dirs():
        try:
            candidate.mkdir(parents=True, exist_ok=True)
            probe = candidate / ".write_probe"
            probe.touch()
            probe.unlink()
            return candidate
        except OSError:
            continue

    return None


def _hub_filename(relative_path: str) -> str:
    return f"{HUB_ROOT}/{relative_path.lstrip('/')}"


def _hub_kwargs() -> dict:
    if not HF_MODEL_REPO:
        raise RuntimeError(
            "HR_MODEL_REPO belum dikonfigurasi; tidak ada repo Hugging Face "
            "untuk mengambil artifact model."
        )

    kwargs = {
        "repo_id": HF_MODEL_REPO,
        "repo_type": HF_MODEL_REPO_TYPE,
        "revision": HF_MODEL_REVISION,
        "token": HF_MODEL_TOKEN,
    }

    resolved = cache_dir()
    if resolved is not None:
        kwargs["cache_dir"] = str(resolved)

    return kwargs


def fetch(relative_path: str) -> Path:
    """Kembalikan path lokal untuk satu artifact.

    Di mode ``hub`` artifact diunduh (atau diambil dari cache) dan path cache
    dikembalikan. Di mode ``local`` path relatif diselesaikan terhadap
    ``models/``. ``relative_path`` selalu relatif ke root ``models/`` di Hub,
    mis. ``"v2_mitigated/turnover_model.joblib"``.
    """
    relative_path = relative_path.lstrip("/")

    if source() != "hub":
        return MODELS_DIR / relative_path

    try:
        from huggingface_hub import hf_hub_download
    except ImportError as exc:  # pragma: no cover - tergantung environment
        raise RuntimeError(
            "Paket 'huggingface_hub' belum terpasang. "
            "Jalankan: pip install -r requirements.txt"
        ) from exc

    return Path(
        hf_hub_download(filename=_hub_filename(relative_path), **_hub_kwargs())
    )


def read_text(relative_path: str) -> str:
    return fetch(relative_path).read_text(encoding="utf-8")


def read_json(relative_path: str) -> dict:
    return json.loads(read_text(relative_path))


def fetch_directory(relative_dir: str) -> Path:
    """Unduh seluruh folder artifact (mis. ``performance/v1``) dari Hub.

    Di mode ``local`` mengembalikan ``models/<relative_dir>``. Dipakai MLOps
    yang membutuhkan seluruh isi folder sekaligus.
    """
    relative_dir = relative_dir.strip("/")

    if source() != "hub":
        return MODELS_DIR / relative_dir

    try:
        from huggingface_hub import snapshot_download
    except ImportError as exc:  # pragma: no cover - tergantung environment
        raise RuntimeError(
            "Paket 'huggingface_hub' belum terpasang. "
            "Jalankan: pip install -r requirements.txt"
        ) from exc

    local_root = snapshot_download(
        allow_patterns=[f"{_hub_filename(relative_dir)}/*"],
        **_hub_kwargs(),
    )

    return Path(local_root) / HUB_ROOT / relative_dir
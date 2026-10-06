"""Siklus hidup versi model: promosi, rollback, dan pin versi aktif.

Registry (`src/registry/model_registry.py`) hanya **membaca** artifact. Modul
ini yang **mengubah** versi mana yang aktif untuk use case yang punya banyak
versi (turnover). Sumber kebenaran tunggalnya adalah
`models/active_version.json`, sehingga API dan MLOps menunjuk versi yang sama
tanpa duplikasi konfigurasi.

Use case selain turnover hanya punya satu artifact tetap di subdirektori
(`absenteeism/v1`, dst.), jadi promosi/rollback tidak berlaku untuk mereka.
"""

from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path

from src.config import MODELS_DIR
from src.registry.model_registry import ModelRegistry


# Use case yang mendukung banyak versi sekaligus rollback.
MULTI_VERSION_USE_CASES: tuple[str, ...] = ("turnover",)

ACTIVE_VERSION_FILE = "active_version.json"

# Dipakai bila belum ada pin sama sekali; sama dengan default API.
DEFAULT_ACTIVE_VERSION = "v2_mitigated"


class LifecycleError(RuntimeError):
    """Operasi siklus hidup tidak dapat dijalankan (versi salah, dsb.)."""


def _active_version_path(models_dir: Path) -> Path:
    return models_dir / ACTIVE_VERSION_FILE


def _read_pin_file(models_dir: Path) -> dict:
    path = _active_version_path(models_dir)

    if not path.exists():
        return {}

    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return {}

    return data if isinstance(data, dict) else {}


def read_active_version(
    use_case: str = "turnover",
    models_dir: Path = MODELS_DIR,
) -> str | None:
    """Versi aktif yang di-pin, atau `None` bila belum pernah dipin."""
    return _read_pin_file(models_dir).get(use_case)


def _write_pin(
    use_case: str,
    version: str,
    models_dir: Path,
    *,
    reason: str,
    previous: str | None,
) -> dict:
    data = _read_pin_file(models_dir)

    data[use_case] = version
    data["diperbarui_pada"] = datetime.now(timezone.utc).isoformat(timespec="seconds")
    data["alasan"] = reason
    if previous is not None:
        data["sebelumnya"] = previous

    _active_version_path(models_dir).write_text(
        json.dumps(data, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    return data


def list_versions(use_case: str = "turnover", registry: ModelRegistry | None = None) -> list[str]:
    """Daftar versi yang tersedia untuk use case multi-versi."""
    if use_case not in MULTI_VERSION_USE_CASES:
        raise LifecycleError(
            f"Use case '{use_case}' tidak mendukung banyak versi."
        )

    registry = registry or ModelRegistry()

    return registry.list_versions(use_case)


def resolve_active_version(
    use_case: str = "turnover",
    registry: ModelRegistry | None = None,
    models_dir: Path = MODELS_DIR,
) -> str:
    """Versi yang sedang aktif: pin bila ada, jika tidak default."""
    if use_case not in MULTI_VERSION_USE_CASES:
        registry = registry or ModelRegistry(models_dir)
        return registry.get(use_case).versi

    return read_active_version(use_case, models_dir) or DEFAULT_ACTIVE_VERSION


def promote(
    version: str,
    use_case: str = "turnover",
    *,
    registry: ModelRegistry | None = None,
    models_dir: Path = MODELS_DIR,
    reason: str = "promosi manual",
) -> dict:
    """Pin `version` sebagai versi aktif setelah memastikan artifact ada.

    Tidak menyentuh file model; hanya menulis `active_version.json`. Deployment
    (env `MODEL_VERSION` atau pembacaan pin oleh API) yang mengaktifkannya.
    """
    if use_case not in MULTI_VERSION_USE_CASES:
        raise LifecycleError(
            f"Use case '{use_case}' tidak mendukung banyak versi."
        )

    registry = registry or ModelRegistry(models_dir)
    tersedia = registry.list_versions(use_case)

    if version not in tersedia:
        raise LifecycleError(
            f"Versi '{version}' tidak ditemukan untuk {use_case}. "
            f"Versi tersedia: {tersedia}."
        )

    # Pastikan artifact benar-benar dapat dibaca dan punya metrik sebelum dipin.
    record = registry.get(use_case, versi=version)
    if not record.metrics:
        raise LifecycleError(
            f"Versi '{version}' tidak punya metrik; tidak aman dipromosikan."
        )

    previous = read_active_version(use_case, models_dir) or DEFAULT_ACTIVE_VERSION

    payload = _write_pin(
        use_case,
        version,
        models_dir,
        reason=reason,
        previous=previous,
    )

    return {
        "use_case": use_case,
        "sebelumnya": previous,
        "aktif": version,
        "diperbarui_pada": payload.get("diperbarui_pada"),
        "alasan": reason,
    }


def rollback(
    use_case: str = "turnover",
    *,
    registry: ModelRegistry | None = None,
    models_dir: Path = MODELS_DIR,
    reason: str = "rollback ke versi sebelumnya",
) -> dict:
    """Kembalikan versi aktif ke versi yang tercatat sebelumnya."""
    if use_case not in MULTI_VERSION_USE_CASES:
        raise LifecycleError(
            f"Use case '{use_case}' tidak mendukung banyak versi."
        )

    previous = _read_pin_file(models_dir).get("sebelumnya")

    if not previous:
        raise LifecycleError(
            "Tidak ada versi sebelumnya yang tercatat; rollback tidak tersedia."
        )

    return promote(
        previous,
        use_case,
        registry=registry,
        models_dir=models_dir,
        reason=reason,
    )

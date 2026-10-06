"""Siklus hidup versi model: promosi, rollback, dan pin versi aktif.

Registry (`src/registry/model_registry.py`) hanya **membaca** artifact. Modul
ini yang **mengubah** versi mana yang aktif. Sumber kebenaran tunggalnya adalah
`models/active_version.json`, sehingga API dan MLOps menunjuk versi yang sama
tanpa duplikasi konfigurasi.

Semua use case memakai mekanisme yang sama. Nilai pin adalah **path relatif**
di bawah `models/` (mis. `v2` untuk turnover, `performance/v2` untuk kinerja),
sehingga satu fungsi `resolve_active_version` berlaku untuk semuanya.
"""

from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path

from src.config import (
    ACTIVE_VERSION_FILE,
    DEFAULT_MODEL_LOCATIONS,
    DEFAULT_TURNOVER_VERSION,
    MODELS_DIR,
    read_pinned_version,
)
from src.registry.model_registry import ModelRegistry


# Use case yang dikenal (semuanya mendukung versioning + rollback).
MULTI_VERSION_USE_CASES: tuple[str, ...] = tuple(DEFAULT_MODEL_LOCATIONS)

# Dipakai bila belum ada pin sama sekali; sama dengan default API.
DEFAULT_ACTIVE_VERSION = DEFAULT_TURNOVER_VERSION


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


def _previous_for(data: dict, use_case: str) -> str | None:
    """Versi sebelumnya untuk use case, mendukung format lama (string)."""
    previous = data.get("sebelumnya")

    if isinstance(previous, dict):
        value = previous.get(use_case)
        return value if isinstance(value, str) and value else None

    # Format lama: `sebelumnya` berupa string dan hanya untuk turnover.
    if use_case == "turnover" and isinstance(previous, str) and previous:
        return previous

    return None


def read_active_version(
    use_case: str = "turnover",
    models_dir: Path = MODELS_DIR,
) -> str | None:
    """Versi aktif yang di-pin, atau `None` bila belum pernah dipin."""
    return read_pinned_version(models_dir, use_case)


def _write_pin(
    use_case: str,
    version: str,
    models_dir: Path,
    *,
    reason: str,
    previous: str | None,
) -> dict:
    data = _read_pin_file(models_dir)

    # Migrasi format lama (string) menjadi dict per use case.
    old_previous = data.get("sebelumnya")
    riwayat: dict = old_previous if isinstance(old_previous, dict) else {}
    if isinstance(old_previous, str) and old_previous:
        riwayat.setdefault("turnover", old_previous)

    if previous is not None:
        riwayat[use_case] = previous

    data[use_case] = version
    data["sebelumnya"] = riwayat
    data["diperbarui_pada"] = datetime.now(timezone.utc).isoformat(timespec="seconds")
    data["alasan"] = reason

    _active_version_path(models_dir).write_text(
        json.dumps(data, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    return data


def list_versions(use_case: str = "turnover", registry: ModelRegistry | None = None) -> list[str]:
    """Daftar versi yang tersedia untuk sebuah use case."""
    if use_case not in MULTI_VERSION_USE_CASES:
        raise LifecycleError(f"Use case '{use_case}' tidak dikenal.")

    registry = registry or ModelRegistry()

    return registry.list_versions(use_case)


def resolve_active_version(
    use_case: str = "turnover",
    registry: ModelRegistry | None = None,
    models_dir: Path = MODELS_DIR,
) -> str:
    """Versi aktif (path relatif di bawah `models/`): pin bila ada, jika tidak default."""
    if use_case not in MULTI_VERSION_USE_CASES:
        registry = registry or ModelRegistry(models_dir)
        return registry.get(use_case).versi

    pinned = read_active_version(use_case, models_dir)
    if pinned:
        return pinned

    if use_case == "turnover":
        return DEFAULT_ACTIVE_VERSION

    return DEFAULT_MODEL_LOCATIONS.get(use_case, "")


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
        raise LifecycleError(f"Use case '{use_case}' tidak dikenal.")

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

    normalized = registry.normalize_version(use_case, version)
    previous = resolve_active_version(use_case, registry, models_dir)

    payload = _write_pin(
        use_case,
        normalized,
        models_dir,
        reason=reason,
        previous=previous,
    )

    return {
        "use_case": use_case,
        "sebelumnya": previous,
        "aktif": normalized,
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
        raise LifecycleError(f"Use case '{use_case}' tidak dikenal.")

    previous = _previous_for(_read_pin_file(models_dir), use_case)

    if not previous:
        raise LifecycleError(
            "Tidak ada versi sebelumnya yang tercatat; rollback tidak tersedia."
        )

    registry = registry or ModelRegistry(models_dir)
    nama = Path(previous).name

    return promote(
        nama,
        use_case,
        registry=registry,
        models_dir=models_dir,
        reason=reason,
    )


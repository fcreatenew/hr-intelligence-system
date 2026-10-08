from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path

from src import hf_store
from src.config import (
    DEFAULT_MODEL_LOCATIONS,
    MODELS_DIR,
    read_pinned_version,
    resolve_turnover_version,
)


# Peta use case -> direktori model default. Turnover punya beberapa versi di
# models/v1, models/v2, models/v2_mitigated; use case lain di subdirektori.
# Versi **aktif** ditentukan `models/active_version.json` (lihat
# `resolve_version`), bukan nilai di peta ini.
MODEL_LOCATIONS: dict[str, str] = dict(DEFAULT_MODEL_LOCATIONS)


def resolve_version(use_case: str, models_dir: Path = MODELS_DIR) -> str:
    """Path artifact aktif untuk sebuah use case.

    Urutan: pin `active_version.json` lokal → pin di Hub (bila sumber Hub
    aktif) → default dari `MODEL_LOCATIONS`. Semua use case memakai mekanisme
    yang sama, sehingga promote/rollback berlaku seragam.
    """
    pinned = read_pinned_version(models_dir, use_case)

    if not pinned and hf_store.is_hub_enabled():
        try:
            data = hf_store.read_json("active_version.json")
            value = data.get(use_case) if isinstance(data, dict) else None
            pinned = value if isinstance(value, str) and value else None
        except Exception:  # noqa: BLE001 - pin Hub opsional
            pinned = None

    if pinned:
        return pinned

    if use_case == "turnover":
        return resolve_turnover_version(models_dir)

    return MODEL_LOCATIONS.get(use_case, "")


@dataclass
class ModelRecord:
    use_case: str
    versi: str
    path: str
    model_type: str = ""
    target: str = ""
    threshold: float | None = None
    trained_at: str = ""
    metrics: dict = field(default_factory=dict)
    metadata: dict = field(default_factory=dict)

    def to_dict(self) -> dict:
        return {
            "use_case": self.use_case,
            "versi": self.versi,
            "path": self.path,
            "model_type": self.model_type,
            "target": self.target,
            "threshold": self.threshold,
            "trained_at": self.trained_at,
            "metrics": self.metrics,
        }


def _extract_metrics(meta: dict) -> dict:
    for key in ("metrik_holdout_test", "metrik_evaluasi", "metrik"):
        if isinstance(meta.get(key), dict):
            return meta[key]

    # Model survival menaruh metriknya di root metadata (c_index, logrank_p).
    extra = {
        key: meta[key]
        for key in ("c_index", "logrank_p", "median_survival_bulan")
        if key in meta
    }

    return extra


def _read_metadata(path: Path) -> dict:
    meta_file = path / "model_metadata.json"

    if not meta_file.exists():
        return {}

    return json.loads(meta_file.read_text(encoding="utf-8"))


class ModelRegistry:
    """Registry model berbasis metadata di folder `models/`.

    Registry membaca metadata sebagai sumber kebenaran tunggal, sehingga versi
    aktif, ambang keputusan, dan metrik evaluasi tidak diduplikasi di kode.
    """

    def __init__(self, models_dir: Path = MODELS_DIR):
        self._models_dir = models_dir

    def locations(self) -> dict[str, str]:
        return dict(MODEL_LOCATIONS)

    def list_versions(self, use_case: str) -> list[str]:
        """Versi yang tersedia (nama direktori versi) untuk sebuah use case.

        Turnover: direktori `v*` langsung di bawah `models/`. Use case lain:
        direktori `v*` di bawah folder induknya (mis. `performance/v1`,
        `performance/v2`) yang punya metadata. Nama yang dikembalikan adalah
        nama versi saja (`v1`, `v2`) agar seragam.
        """
        base = self._models_dir
        found: list[str] = []

        if use_case == "turnover":
            for candidate in sorted(base.glob("v*")):
                if (candidate / "model_metadata.json").exists():
                    found.append(candidate.name)
        else:
            prefix = MODEL_LOCATIONS.get(use_case, "")
            parent = Path(prefix).parent if prefix else Path(".")
            if parent != Path("."):
                for candidate in sorted((base / parent).glob("v*")):
                    if (candidate / "model_metadata.json").exists():
                        found.append(candidate.name)
            elif prefix and (base / prefix / "model_metadata.json").exists():
                found.append(Path(prefix).name)

        return found

    def normalize_version(self, use_case: str, versi: str) -> str:
        """Ubah nama versi menjadi path relatif di bawah `models/`.

        Menerima path relatif penuh (`performance/v2`) atau nama versi saja
        (`v2`). Nama versi saja diprefiks folder induk use case non-turnover.
        """
        if "/" in versi:
            return versi

        if use_case == "turnover":
            return versi

        prefix = MODEL_LOCATIONS.get(use_case, "")
        parent = Path(prefix).parent if prefix else Path(".")

        return str(parent / versi) if parent != Path(".") else versi

    def get(self, use_case: str, versi: str | None = None) -> ModelRecord:
        if use_case not in MODEL_LOCATIONS:
            raise KeyError(f"Use case tidak dikenal: '{use_case}'.")

        if versi:
            relative = self.normalize_version(use_case, versi)
        else:
            # Versi aktif dibaca dari pin (sumber kebenaran tunggal), lalu
            # fallback ke default per use case.
            relative = resolve_version(use_case, self._models_dir)

        path = self._models_dir / relative

        if not path.exists():
            # Fallback Hub: artifact tidak ada di lokal dan sumber Hub aktif.
            if hf_store.is_hub_enabled():
                path = hf_store.fetch_directory(relative)
            else:
                raise FileNotFoundError(f"Model tidak ditemukan: {path}")

        meta = _read_metadata(path)

        return ModelRecord(
            use_case=use_case,
            versi=meta.get("versi_model", versi or relative),
            path=str(path),
            model_type=meta.get("model", ""),
            target=meta.get("target") or meta.get("tujuan", ""),
            threshold=meta.get("threshold"),
            trained_at=meta.get("dilatih_pada") or meta.get("dibuat_pada", ""),
            metrics=_extract_metrics(meta),
            metadata=meta,
        )

    def all(self) -> list[ModelRecord]:
        return [self.get(use_case) for use_case in MODEL_LOCATIONS]

    def summary(self) -> dict:
        records = self.all()

        return {
            "jumlah_model": len(records),
            "models": [record.to_dict() for record in records],
        }

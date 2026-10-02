from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path

from src.config import MODELS_DIR


# Peta use case -> direktori model. Turnover punya beberapa versi di
# models/v1, models/v2, models/v2_mitigated; use case lain di subdirektori.
MODEL_LOCATIONS: dict[str, str] = {
    "turnover": "v2",
    "absensi": "absenteeism/v1",
    "kinerja": "performance/v1",
    "payroll": "payroll_anomaly/v1",
    "survival": "survival_attrition/v1",
}


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
        base = self._models_dir
        found: list[str] = []

        if use_case == "turnover":
            for candidate in sorted(base.glob("v*")):
                if (candidate / "model_metadata.json").exists():
                    found.append(candidate.name)
        else:
            prefix = MODEL_LOCATIONS.get(use_case, "")
            if prefix and (base / prefix / "model_metadata.json").exists():
                found.append(Path(prefix).name)

        return found

    def get(self, use_case: str, versi: str | None = None) -> ModelRecord:
        if use_case not in MODEL_LOCATIONS:
            raise KeyError(f"Use case tidak dikenal: '{use_case}'.")

        if use_case == "turnover":
            relative = versi or MODEL_LOCATIONS[use_case]
        else:
            relative = MODEL_LOCATIONS[use_case]

        path = self._models_dir / relative

        if not path.exists():
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

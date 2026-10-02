"""Pencatat eksperimen sederhana berbasis berkas JSON.

Setiap run ditulis ke `experiments/runs/<timestamp>_<nama>.json` sehingga hasil
antar konfigurasi dapat dibandingkan tanpa database. Ini versi ringan dari
experiment tracking; cukup untuk memenuhi prinsip reproducible experiment.
"""

from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path

from src.config import EXPERIMENTS_DIR


def _slug(name: str) -> str:
    return "".join(c if c.isalnum() or c in "-_" else "-" for c in name).strip("-")


class ExperimentTracker:
    def __init__(self, base_dir: Path = EXPERIMENTS_DIR):
        self._base_dir = base_dir
        self._base_dir.mkdir(parents=True, exist_ok=True)

    def log(
        self,
        name: str,
        metrics: dict,
        config: dict | None = None,
        notes: str = "",
    ) -> Path:
        stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        path = self._base_dir / f"{stamp}_{_slug(name)}.json"

        payload = {
            "nama": name,
            "dicatat_pada": datetime.now(timezone.utc).isoformat(),
            "config": config or {},
            "metrics": metrics,
            "catatan": notes,
        }

        path.write_text(json.dumps(payload, indent=2, default=str), encoding="utf-8")
        return path

    def list_runs(self) -> list[dict]:
        runs = []

        for file in sorted(self._base_dir.glob("*.json")):
            try:
                runs.append(json.loads(file.read_text(encoding="utf-8")))
            except json.JSONDecodeError:
                continue

        return runs

    def best(self, metric: str = "roc_auc") -> dict | None:
        runs = [r for r in self.list_runs() if metric in r.get("metrics", {})]

        if not runs:
            return None

        return max(runs, key=lambda r: r["metrics"][metric])

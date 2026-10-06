"""Gate fairness untuk CI: cegah regresi keadilan antar versi model.

`audit_fairness` menghitung metrik keadilan saat ini; `fairness_gate`
membandingkannya dengan **baseline** yang tersimpan
(`models/fairness_baseline.json`). Gate ini sengaja bersifat *regression-aware*
alih-alih "harus nol pelanggaran", karena:

- dataset sintetis saat ini memang punya disparitas nyata (model belum adil
  sempurna); memblokir semua pelanggaran akan mematikan CI tanpa memberi sinyal;
- yang paling berbahaya adalah **perubahan** yang memperburuk keadilan, dan itu
  dapat dideteksi dengan membandingkan ke baseline.

Gate gagal (exit 1) bila:
1. impact ratio sebuah atribut turun lebih dari `tolerance` dibanding baseline;
2. atribut yang sebelumnya lolos four-fifths kini melanggar.
"""

from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path

from src.config import MODELS_DIR

BASELINE_FILE = "fairness_baseline.json"


@dataclass
class FairnessGateConfig:
    tolerance: float = 0.02
    baseline_path: Path | None = None


def _attribute_snapshot(hasil: dict) -> dict:
    """Ringkas hasil audit menjadi atribut -> {impact_ratio, lolos}."""
    snapshot: dict = {}

    for atribut, audit in hasil.get("atribut", {}).items():
        ratio = audit.get("impact_ratio")
        snapshot[atribut] = {
            "impact_ratio": ratio,
            "lolos": not audit.get("pelanggaran"),
        }

    return snapshot


def build_baseline(hasil: dict) -> dict:
    """Bentuk payload baseline dari hasil `run_fairness_audit`."""
    return {
        "versi": hasil.get("versi"),
        "threshold": hasil.get("threshold"),
        "atribut": _attribute_snapshot(hasil),
    }


def load_baseline(path: Path) -> dict | None:
    if not path.exists():
        return None

    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return None

    return data if isinstance(data, dict) else None


def save_baseline(hasil: dict, path: Path) -> dict:
    payload = build_baseline(hasil)
    path.write_text(
        json.dumps(payload, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    return payload


def evaluate_gate(
    hasil: dict,
    baseline: dict | None,
    config: FairnessGateConfig | None = None,
) -> dict:
    """Bandingkan hasil audit dengan baseline; kembalikan keputusan gate."""
    config = config or FairnessGateConfig()
    current = _attribute_snapshot(hasil)
    pelanggaran = list(hasil.get("ringkasan", {}).get("pelanggaran", []))

    if baseline is None:
        # Tanpa baseline, gate hanya melaporkan; tidak memblokir CI.
        return {
            "lulus": True,
            "ada_baseline": False,
            "regresi": [],
            "pelanggaran_sekarang": pelanggaran,
            "alasan": (
                "Belum ada baseline fairness; gate dilewati. "
                "Jalankan `fairness-gate --update-baseline` untuk menetapkan baseline."
            ),
        }

    baseline_atribut = baseline.get("atribut", {})
    regresi: list[dict] = []

    for atribut, sekarang in current.items():
        acuan = baseline_atribut.get(atribut)

        if not acuan:
            continue

        ratio_sekarang = sekarang.get("impact_ratio")
        ratio_acuan = acuan.get("impact_ratio")

        if ratio_sekarang is not None and ratio_acuan is not None:
            if ratio_sekarang < ratio_acuan - config.tolerance:
                regresi.append(
                    {
                        "atribut": atribut,
                        "jenis": "impact_ratio_turun",
                        "baseline": ratio_acuan,
                        "sekarang": ratio_sekarang,
                    }
                )

        if acuan.get("lolos") and not sekarang.get("lolos"):
            regresi.append(
                {
                    "atribut": atribut,
                    "jenis": "pelanggaran_baru",
                    "baseline": ratio_acuan,
                    "sekarang": ratio_sekarang,
                }
            )

    lulus = not regresi

    return {
        "lulus": lulus,
        "ada_baseline": True,
        "regresi": regresi,
        "pelanggaran_sekarang": pelanggaran,
        "alasan": (
            "Tidak ada regresi fairness dibanding baseline."
            if lulus
            else f"Terdeteksi {len(regresi)} regresi fairness dibanding baseline."
        ),
    }


def run_fairness_gate(
    hasil: dict,
    config: FairnessGateConfig | None = None,
    *,
    models_dir: Path = MODELS_DIR,
) -> dict:
    """Jalankan gate terhadap baseline default (`models/fairness_baseline.json`)."""
    config = config or FairnessGateConfig()
    path = config.baseline_path or (models_dir / BASELINE_FILE)
    baseline = load_baseline(path)

    keputusan = evaluate_gate(hasil, baseline, config)
    keputusan["baseline_path"] = str(path)

    return keputusan

from __future__ import annotations

import shutil
import sys
from pathlib import Path

import numpy as np
import pandas as pd
import pytest

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from src.config import MODELS_DIR  # noqa: E402
from src.monitoring.fairness import (  # noqa: E402
    FairnessConfig,
    audit_attribute,
    audit_fairness,
    build_groups,
    run_fairness_audit,
)

TURNOVER_VERSIONS = ("v1", "v2", "v2_mitigated")


@pytest.fixture()
def sandbox(tmp_path: Path) -> Path:
    models = tmp_path / "models"
    models.mkdir()

    for versi in TURNOVER_VERSIONS:
        shutil.copytree(MODELS_DIR / versi, models / versi)

    return models


def _frame(n: int = 200) -> pd.DataFrame:
    """Data sintetis dengan dua kelompok berukuran sama."""
    rng = np.random.default_rng(0)

    return pd.DataFrame(
        {
            "usia": rng.integers(22, 55, n),
            "departemen": ["IT"] * (n // 2) + ["Sales"] * (n - n // 2),
            "status_kerja": ["tetap"] * n,
            "pendidikan": rng.choice(["S1", "S2"], n),
        }
    )


def test_build_groups_buckets_age_and_keeps_others():
    df = _frame()
    groups = build_groups(df)

    assert set(groups) == {"usia", "departemen", "status_kerja", "pendidikan"}
    assert set(groups["usia"].unique()) <= {"<=25", "26-30", "31-35", "36-40", ">40", "nan"}


def test_audit_attribute_flags_four_fifths_violation():
    # IT ditandai keluar 100%, Sales 0% → impact ratio 0 (pelanggaran four-fifths).
    df = _frame()
    groups = df["departemen"]
    y_true = np.zeros(len(df), dtype=int)
    y_pred = np.array([1] * (len(df) // 2) + [0] * (len(df) - len(df) // 2))

    audit = audit_attribute(groups, y_true, y_pred, attribute="departemen")

    assert audit.impact_ratio is not None
    assert audit.impact_ratio == 0.0
    assert audit.demographic_parity_diff == pytest.approx(1.0, abs=0.01)
    assert audit.pelanggaran


def test_audit_attribute_clean_when_rates_equal():
    df = _frame()
    groups = df["departemen"]
    y_true = np.zeros(len(df), dtype=int)
    y_pred = np.ones(len(df), dtype=int)  # semua ditandai → seleksi seragam

    audit = audit_attribute(groups, y_true, y_pred, attribute="departemen")

    assert audit.impact_ratio == 1.0
    assert audit.demographic_parity_diff == 0.0
    assert audit.pelanggaran == []


def test_audit_attribute_ignores_tiny_groups():
    groups = pd.Series(["A"] * 100 + ["B"] * 100 + ["C"] * 3)
    y_true = np.zeros(203, dtype=int)
    y_pred = np.array([1] * 100 + [0] * 100 + [1] * 3)

    audit = audit_attribute(groups, y_true, y_pred, attribute="grup", min_group_size=20)

    # C (3 sampel) tidak boleh mendikte disparitas.
    assert audit.impact_ratio == 0.0
    assert audit.demographic_parity_diff == 1.0
    assert "C" in audit.kelompok
    assert audit.kelompok["C"].n == 3


def test_audit_attribute_reports_none_when_one_group():
    groups = pd.Series(["A"] * 50)
    audit = audit_attribute(groups, np.zeros(50, dtype=int), np.zeros(50, dtype=int))

    assert audit.demographic_parity_diff is None
    assert audit.impact_ratio is None


def test_audit_fairness_summarizes_violations():
    df = _frame()
    y_true = np.zeros(len(df), dtype=int)
    y_pred = np.array([1] * (len(df) // 2) + [0] * (len(df) - len(df) // 2))

    hasil = audit_fairness(df, y_true, y_pred)

    assert hasil["samples"] == len(df)
    assert "departemen" in hasil["atribut"]
    assert hasil["ringkasan"]["lolos"] is False
    assert any("departemen" in p for p in hasil["ringkasan"]["pelanggaran"])


def test_audit_fairness_passes_when_uniform():
    df = _frame()
    y_true = np.zeros(len(df), dtype=int)
    y_pred = np.zeros(len(df), dtype=int)

    hasil = audit_fairness(df, y_true, y_pred)

    assert hasil["ringkasan"]["lolos"] is True
    assert hasil["ringkasan"]["jumlah_pelanggaran"] == 0


def test_run_fairness_audit_loads_active_model(sandbox: Path):
    hasil = run_fairness_audit(
        FairnessConfig(min_group_size=5),
        models_dir=sandbox,
        df=_turnover_frame(),
    )

    assert hasil["versi"] in TURNOVER_VERSIONS
    assert hasil["threshold"] > 0
    assert hasil["samples"] > 0
    assert "ringkasan" in hasil
    # Setiap atribut punya metrik yang dapat diserialisasi.
    for audit in hasil["atribut"].values():
        assert "demographic_parity_diff" in audit
        assert "impact_ratio" in audit


def _turnover_frame() -> pd.DataFrame:
    """Bingkai mentah yang lolos `buat_fitur` (kolom kontrak turnover)."""
    rng = np.random.default_rng(1)
    n = 300

    return pd.DataFrame(
        {
            "usia": rng.integers(22, 55, n),
            "masa_kerja_bulan": rng.integers(1, 120, n),
            "jam_lembur_bulanan": rng.integers(0, 30, n),
            "gaji_bulanan": rng.integers(3_000_000, 20_000_000, n),
            "tanggal_masuk": pd.Timestamp("2020-01-01"),
            "tanggal_promosi": pd.Timestamp("2023-01-01"),
            "departemen": rng.choice(["IT", "Sales", "HR"], n),
            "status_kerja": rng.choice(["tetap", "kontrak"], n),
            "pendidikan": rng.choice(["S1", "S2"], n),
            "target_turnover": rng.integers(0, 2, n),
        }
    )


def test_api_fairness_endpoint():
    from fastapi.testclient import TestClient

    from src.api import app

    client = TestClient(app)
    resp = client.post("/monitoring/fairness", json={"min_group_size": 20})

    assert resp.status_code == 200
    body = resp.json()
    assert body["samples"] > 0
    assert "ringkasan" in body
    assert "usia" in body["atribut"]


# ---------------------------------------------------------------------------
# Gate regresi fairness
# ---------------------------------------------------------------------------


def _hasil_dengan_ratio(atribut: str, ratio: float, lolos: bool) -> dict:
    return {
        "versi": "v2_mitigated",
        "atribut": {atribut: {"impact_ratio": ratio, "pelanggaran": [] if lolos else ["x"]}},
        "ringkasan": {"pelanggaran": [] if lolos else [f"{atribut}: x"]},
    }


def test_gate_skips_without_baseline():
    from src.monitoring.fairness_gate import evaluate_gate

    keputusan = evaluate_gate(_hasil_dengan_ratio("usia", 0.5, lolos=False), None)

    assert keputusan["lulus"] is True
    assert keputusan["ada_baseline"] is False


def test_gate_detects_impact_ratio_regression():
    from src.monitoring.fairness_gate import evaluate_gate, build_baseline

    baseline = build_baseline(_hasil_dengan_ratio("usia", 0.80, lolos=True))
    sekarang = _hasil_dengan_ratio("usia", 0.70, lolos=True)

    keputusan = evaluate_gate(sekarang, baseline)

    assert keputusan["lulus"] is False
    assert keputusan["regresi"][0]["jenis"] == "impact_ratio_turun"


def test_gate_allows_within_tolerance():
    from src.monitoring.fairness_gate import evaluate_gate, build_baseline

    baseline = build_baseline(_hasil_dengan_ratio("usia", 0.80, lolos=True))
    sekarang = _hasil_dengan_ratio("usia", 0.79, lolos=True)

    keputusan = evaluate_gate(sekarang, baseline, None)

    assert keputusan["lulus"] is True


def test_gate_flags_new_violation():
    from src.monitoring.fairness_gate import evaluate_gate, build_baseline

    baseline = build_baseline(_hasil_dengan_ratio("departemen", 0.85, lolos=True))
    sekarang = _hasil_dengan_ratio("departemen", 0.82, lolos=False)

    keputusan = evaluate_gate(sekarang, baseline)

    assert keputusan["lulus"] is False
    assert any(r["jenis"] == "pelanggaran_baru" for r in keputusan["regresi"])


def test_gate_passes_when_improved():
    from src.monitoring.fairness_gate import evaluate_gate, build_baseline

    baseline = build_baseline(_hasil_dengan_ratio("usia", 0.60, lolos=False))
    sekarang = _hasil_dengan_ratio("usia", 0.85, lolos=True)

    keputusan = evaluate_gate(sekarang, baseline)

    assert keputusan["lulus"] is True
    assert keputusan["regresi"] == []


def test_save_and_load_baseline_round_trip(tmp_path: Path):
    from src.monitoring.fairness_gate import load_baseline, save_baseline

    path = tmp_path / "fairness_baseline.json"
    hasil = run_fairness_audit(FairnessConfig(min_group_size=5), df=_turnover_frame())

    save_baseline(hasil, path)
    baseline = load_baseline(path)

    assert baseline is not None
    assert "usia" in baseline["atribut"]
    assert "impact_ratio" in baseline["atribut"]["usia"]

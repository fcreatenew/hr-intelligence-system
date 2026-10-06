from __future__ import annotations

import shutil
import sys
from pathlib import Path

import pandas as pd
import pytest

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from src.config import DATA_DIR, MODELS_DIR  # noqa: E402
from src.lifecycle import (  # noqa: E402
    PerformanceMonitorConfig,
    evaluate_performance,
    promote,
    resolve_active_version,
    run_performance_monitor,
)

TURNOVER_VERSIONS = ("v1", "v2", "v2_mitigated")


@pytest.fixture()
def sandbox(tmp_path: Path) -> Path:
    models = tmp_path / "models"
    models.mkdir()

    for versi in TURNOVER_VERSIONS:
        shutil.copytree(MODELS_DIR / versi, models / versi)

    return models


def _feedback(n: int, kind: str) -> pd.DataFrame:
    labels = [i % 2 for i in range(n)]

    if kind == "bagus":
        skor = [0.9 if label else 0.1 for label in labels]
    else:
        # Skor konstan -> ROC AUC 0.5, jauh di bawah referensi.
        skor = [0.5 for _ in labels]

    return pd.DataFrame({"skor": skor, "label_aktual": labels})


def test_evaluate_performance_menandai_penurunan():
    report = evaluate_performance(_feedback(60, "buruk"), reference_auc=0.70)

    assert report.status == "turun"
    assert report.roc_auc_produksi == 0.5
    assert report.selisih == pytest.approx(-0.2, abs=1e-3)
    assert report.samples == 60


def test_evaluate_performance_ok_bila_setara():
    report = evaluate_performance(_feedback(60, "bagus"), reference_auc=0.70)

    assert report.status == "ok"
    assert report.roc_auc_produksi == 1.0


def test_evaluate_performance_tanpa_kolom():
    report = evaluate_performance(pd.DataFrame({"foo": [1, 2]}), reference_auc=0.7)

    assert report.status == "tanpa_data"
    assert report.roc_auc_produksi is None


def test_evaluate_performance_sampel_kurang():
    report = evaluate_performance(_feedback(10, "bagus"), reference_auc=0.7)

    assert report.status == "tanpa_data"
    assert report.samples == 10


def test_evaluate_performance_label_sejenis():
    current = pd.DataFrame({"skor": [0.1, 0.2, 0.3] * 20, "label_aktual": [0] * 60})

    report = evaluate_performance(current, reference_auc=0.7)

    assert report.status == "tanpa_data"


def test_monitor_tanpa_data_tidak_mengubah_versi(sandbox):
    hasil = run_performance_monitor(None, models_dir=sandbox)

    assert hasil["status"] == "tanpa_data"
    assert hasil["rollback"] is None


def test_monitor_rollback_saat_performa_turun(sandbox):
    promote("v2", models_dir=sandbox, reason="uji")

    hasil = run_performance_monitor(_feedback(60, "buruk"), models_dir=sandbox)

    assert hasil["status"] == "rollback"
    assert hasil["rollback"]["aktif"] == "v2_mitigated"
    assert resolve_active_version("turnover", models_dir=sandbox) == "v2_mitigated"


def test_monitor_turun_tanpa_pin_tidak_rollback(sandbox):
    hasil = run_performance_monitor(_feedback(60, "buruk"), models_dir=sandbox)

    assert hasil["status"] == "turun_tanpa_pin"
    assert hasil["rollback"] is None


def test_monitor_toleransi_besar_tidak_rollback(sandbox):
    promote("v2", models_dir=sandbox, reason="uji")

    config = PerformanceMonitorConfig(tolerance=0.9)
    hasil = run_performance_monitor(_feedback(60, "buruk"), config=config, models_dir=sandbox)

    assert hasil["status"] == "ok"
    assert hasil["rollback"] is None


def test_api_performance_endpoint(tmp_path):
    from fastapi.testclient import TestClient

    from src.api import app

    csv = tmp_path / "feedback.csv"
    _feedback(60, "buruk").to_csv(csv, index=False)

    client = TestClient(app)
    resp = client.post("/monitoring/performance", json={"current": str(csv)})

    assert resp.status_code == 200
    body = resp.json()
    assert body["performa"]["status"] in {"turun", "turun_tanpa_pin"}
    # Tanpa pin di repo, tidak ada versi sebelumnya untuk rollback.
    assert body["rollback"] is None

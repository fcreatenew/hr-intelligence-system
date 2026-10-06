from __future__ import annotations

from pathlib import Path

import pandas as pd

from src.monitoring.feedback import FeedbackStore


def _prediksi(employee_id: str = "EMP00001", prob: float = 0.7) -> dict:
    return {
        "employee_id": employee_id,
        "usia": 30,
        "masa_kerja_bulan": 24,
        "probabilitas_turnover": prob,
    }


def test_record_and_read_round_trip(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")

    store.record(_prediksi(), {"target_turnover": 1}, version="v2_mitigated")
    store.record(_prediksi("EMP00002", 0.2), {"target_turnover": 0})

    entries = store.entries("turnover")
    assert len(entries) == 2
    assert entries[0]["versi"] == "v2_mitigated"
    assert entries[0]["aktual"]["target_turnover"] == 1


def test_record_rejects_empty_actual(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")

    try:
        store.record(_prediksi(), {})
    except ValueError:
        return

    raise AssertionError("record harus menolak label kosong")


def test_to_dataframe_merges_features_and_label(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")
    store.record(_prediksi(), {"target_turnover": 1})

    df = store.to_dataframe("turnover")

    assert "target_turnover" in df.columns
    assert "usia" in df.columns
    assert df.iloc[0]["target_turnover"] == 1


def test_export_csv_is_readable(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")
    store.record(_prediksi(), {"target_turnover": 1})
    store.record(_prediksi("EMP00002", 0.1), {"target_turnover": 0})

    out = store.export_csv(tmp_path / "out.csv", "turnover")
    df = pd.read_csv(out)

    assert len(df) == 2
    assert "target_turnover" in df.columns


def test_entries_filter_by_use_case(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")
    store.record(_prediksi(), {"target_turnover": 1}, use_case="turnover")
    store.record({"usia": 40}, {"target_absen_tinggi": 1}, use_case="absensi")

    assert len(store.entries("turnover")) == 1
    assert len(store.entries("absensi")) == 1
    assert len(store.entries()) == 2


def test_clear_specific_use_case(tmp_path: Path):
    store = FeedbackStore(tmp_path / "feedback.jsonl")
    store.record(_prediksi(), {"target_turnover": 1}, use_case="turnover")
    store.record({"usia": 40}, {"target_absen_tinggi": 1}, use_case="absensi")

    dihapus = store.clear("turnover")

    assert dihapus == 1
    assert store.entries("turnover") == []
    assert len(store.entries("absensi")) == 1


def test_missing_file_returns_empty(tmp_path: Path):
    store = FeedbackStore(tmp_path / "tidak_ada.jsonl")

    assert store.entries() == []
    assert store.to_dataframe("turnover").empty


def test_export_feeds_performance_monitor(tmp_path: Path):
    """CSV hasil ekspor punya kolom kanonik `skor`/`label_aktual`."""
    from src.lifecycle.performance import PerformanceMonitorConfig, evaluate_performance

    store = FeedbackStore(tmp_path / "feedback.jsonl")
    store.record(_prediksi("EMP1", 0.9), {"target_turnover": 1})
    store.record(_prediksi("EMP2", 0.1), {"target_turnover": 0})

    out = store.export_csv(tmp_path / "produksi.csv", "turnover")
    df = pd.read_csv(out)

    assert "skor" in df.columns
    assert "label_aktual" in df.columns

    laporan = evaluate_performance(df, reference_auc=0.9, config=PerformanceMonitorConfig(min_samples=2))
    assert laporan.status in {"ok", "turun"}
    assert laporan.samples == 2


def test_api_feedback_endpoints(tmp_path: Path, monkeypatch):
    from fastapi.testclient import TestClient

    import src.api as api_module
    from src.monitoring.feedback import FeedbackStore

    monkeypatch.setattr(api_module, "feedback_store", FeedbackStore(tmp_path / "api.jsonl"))
    client = TestClient(api_module.app)

    simpan = client.post(
        "/feedback",
        json={
            "prediction": {"employee_id": "EMP1", "usia": 30},
            "actual": {"target_turnover": 1},
        },
    )
    assert simpan.status_code == 200
    assert simpan.json()["total"] == 1

    ringkas = client.get("/feedback?use_case=turnover")
    assert ringkas.status_code == 200
    assert ringkas.json()["total"] == 1

    ekspor = client.post(
        "/feedback/export",
        json={"destination": str(tmp_path / "api_out.csv")},
    )
    assert ekspor.status_code == 200
    assert ekspor.json()["baris"] == 1

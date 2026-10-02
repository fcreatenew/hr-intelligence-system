from __future__ import annotations

import json

import numpy as np
import pandas as pd
import pytest

from src.monitoring.drift import detect_feature_drift, drift_level, psi
from src.monitoring.metrics import MetricsCollector
from src.registry.model_registry import MODEL_LOCATIONS, ModelRegistry
from src.validation.model_validator import validate_all, validate_record


@pytest.fixture(scope="module")
def registry() -> ModelRegistry:
    return ModelRegistry()


def test_registry_loads_all_use_cases(registry):
    records = registry.all()

    assert len(records) == len(MODEL_LOCATIONS) == 5
    assert {r.use_case for r in records} == set(MODEL_LOCATIONS)


def test_registry_reads_metadata(registry):
    record = registry.get("turnover")

    assert record.model_type
    assert record.metrics.get("roc_auc") is not None
    assert record.threshold is not None


def test_registry_unknown_use_case(registry):
    with pytest.raises(KeyError):
        registry.get("tidak_ada")


def test_validation_passes_for_real_models(registry):
    hasil = validate_all(registry)

    assert hasil["jumlah"] == 5
    assert hasil["lulus_semua"], hasil


def test_validation_detects_bad_model(registry):
    from dataclasses import replace

    record = registry.get("turnover")
    buruk = replace(record, versi="", model_type="", metrics={})

    hasil = validate_record(buruk)

    assert not hasil["lulus"]


def test_psi_identical_is_zero():
    data = np.random.default_rng(0).normal(size=500)

    assert psi(data, data) == pytest.approx(0.0, abs=1e-9)


def test_psi_shifted_is_positive():
    rng = np.random.default_rng(1)
    ref = rng.normal(0, 1, size=1000)
    cur = rng.normal(3, 1, size=1000)

    assert psi(ref, cur) > 0.2


def test_drift_level_thresholds():
    assert drift_level(0.05) == "stabil"
    assert drift_level(0.15) == "peringatan"
    assert drift_level(0.5) == "kritis"


def test_detect_feature_drift_flags_shifted_column():
    rng = np.random.default_rng(2)
    ref = pd.DataFrame({"a": rng.normal(0, 1, 800), "b": rng.normal(0, 1, 800)})
    cur = pd.DataFrame({"a": rng.normal(0, 1, 800), "b": rng.normal(5, 1, 800)})

    hasil = detect_feature_drift(ref, cur)

    assert hasil["drift_terdeteksi"]
    assert hasil["fitur"][0]["fitur"] == "b"


def test_metrics_collector():
    collector = MetricsCollector()
    collector.inc_counter("req_total")
    collector.inc_counter("req_total")
    collector.set_gauge("up", 1)
    collector.observe("latency_ms", 10)
    collector.observe("latency_ms", 20)

    summary = collector.summary()

    assert summary["counters"]["req_total"] == 2
    assert summary["gauges"]["up"] == 1
    assert summary["histograms"]["latency_ms"]["count"] == 2
    assert "req_total 2" in collector.to_prometheus()


def test_metrics_labels():
    collector = MetricsCollector()
    collector.inc_counter("pred_total", use_case="turnover")

    assert 'pred_total{use_case="turnover"}' in collector.to_prometheus()


# ---------------------------------------------------------------------------
# Pipeline training reproducible
# ---------------------------------------------------------------------------
def test_training_pipeline_is_reproducible(tmp_path):
    from src.train.pipeline import TrainingConfig, train

    config = TrainingConfig(seed=42, version="test_repro")
    hasil_a = train(config, models_dir=tmp_path)
    hasil_b = train(config, models_dir=tmp_path)

    assert hasil_a["champion"] == hasil_b["champion"]
    assert hasil_a["threshold"] == hasil_b["threshold"]
    assert hasil_a["metrics"]["roc_auc"] == pytest.approx(hasil_b["metrics"]["roc_auc"])
    assert hasil_a["metrics"]["pr_auc"] == pytest.approx(hasil_b["metrics"]["pr_auc"])


def test_training_pipeline_writes_verifiable_metadata(tmp_path):
    from src.train.pipeline import TrainingConfig, train

    hasil = train(TrainingConfig(seed=42, version="test_meta"), models_dir=tmp_path)

    artifact_dir = tmp_path / "test_meta"
    model_path = artifact_dir / "turnover_model.joblib"
    meta_path = artifact_dir / "model_metadata.json"

    assert model_path.exists()
    meta = json.loads(meta_path.read_text(encoding="utf-8"))

    # Metadata harus bisa langsung divalidasi gate CI.
    assert meta["versi_model"] == "test_meta"
    assert meta["model"] == hasil["champion"]
    assert meta["metrik_holdout_test"]["roc_auc"] >= 0.6
    assert meta["fitur_numerik"] and meta["fitur_kategorik"]
    assert meta["random_state"] == 42


def test_training_metadata_passes_validation_gate(tmp_path):
    from src.train.pipeline import TrainingConfig, train

    hasil = train(TrainingConfig(seed=42, version="test_gate"), models_dir=tmp_path)

    record = ModelRegistry(tmp_path).get("turnover", "test_gate")
    validasi = validate_record(record)

    assert validasi["lulus"], validasi
    assert record.model_type == hasil["champion"]


def test_training_rejects_missing_columns(tmp_path):
    from src.train.pipeline import TrainingConfig, load_data

    bad = tmp_path / "bad.csv"
    bad.write_text("usia,target_turnover\n30,1\n", encoding="utf-8")

    with pytest.raises(ValueError):
        load_data(bad, TrainingConfig().ref_timestamp)


# ---------------------------------------------------------------------------
# Monitoring drift + alert otomatis
# ---------------------------------------------------------------------------
def test_drift_monitor_records_history_and_metrics():
    from src.monitoring.drift_monitor import DriftMonitor

    collector = MetricsCollector()
    monitor = DriftMonitor(collector=collector)

    rng = np.random.default_rng(3)
    ref = pd.DataFrame({"a": rng.normal(0, 1, 800)})
    cur = pd.DataFrame({"a": rng.normal(5, 1, 800)})

    report = monitor.evaluate("turnover", ref, cur)

    assert report.use_case == "turnover"
    assert report.drift_terdeteksi
    assert report.level == "kritis"
    assert report.max_psi > 0.2
    assert len(monitor.history("turnover")) == 1

    prometheus = collector.to_prometheus()
    assert 'drift_max_psi{use_case="turnover"}' in prometheus
    assert 'drift_alerts_total{level="kritis",use_case="turnover"}' in prometheus


def test_drift_monitor_stable_data_produces_no_alert():
    from src.monitoring.drift_monitor import DriftMonitor

    collector = MetricsCollector()
    monitor = DriftMonitor(collector=collector)

    rng = np.random.default_rng(4)
    ref = pd.DataFrame({"a": rng.normal(0, 1, 800)})
    cur = pd.DataFrame({"a": rng.normal(0, 1, 800)})

    report = monitor.evaluate("attrition", ref, cur)

    assert not report.drift_terdeteksi
    assert report.level == "stabil"
    assert monitor.alerts() == []
    assert "drift_alerts_total" not in collector.to_prometheus()


def test_drift_monitor_alerts_use_latest_evaluation_per_use_case():
    from src.monitoring.drift_monitor import DriftMonitor

    monitor = DriftMonitor()
    rng = np.random.default_rng(5)
    ref = pd.DataFrame({"a": rng.normal(0, 1, 600)})

    # Pertama drift, lalu stabil pada evaluasi berikutnya.
    monitor.evaluate("turnover", ref, pd.DataFrame({"a": rng.normal(5, 1, 600)}))
    monitor.evaluate("turnover", ref, pd.DataFrame({"a": rng.normal(0, 1, 600)}))
    monitor.evaluate("performance", ref, pd.DataFrame({"a": rng.normal(6, 1, 600)}))

    alerts = monitor.alerts()

    assert {report.use_case for report in alerts} == {"performance"}
    assert monitor.latest("turnover").drift_terdeteksi is False

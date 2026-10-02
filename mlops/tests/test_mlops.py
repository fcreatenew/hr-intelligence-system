from __future__ import annotations

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

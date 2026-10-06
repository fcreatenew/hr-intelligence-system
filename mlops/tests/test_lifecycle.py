from __future__ import annotations

import json
import shutil
from pathlib import Path

import pandas as pd
import pytest

from src.config import DATA_DIR, MODELS_DIR
from src.lifecycle import (
    LifecycleError,
    active_version_info,
    list_versions,
    next_version,
    promote,
    read_active_version,
    reference_features,
    resolve_active_version,
    rollback,
    run_retraining,
)
from src.lifecycle.retraining import RetrainingConfig
from src.registry.model_registry import ModelRegistry


TURNOVER_VERSIONS = ("v1", "v2", "v2_mitigated")


@pytest.fixture()
def sandbox(tmp_path: Path) -> tuple[Path, Path]:
    """Salinan terisolasi dari artifact turnover + dataset training."""
    models = tmp_path / "models"
    data = tmp_path / "data"
    models.mkdir()
    data.mkdir()

    for versi in TURNOVER_VERSIONS:
        shutil.copytree(MODELS_DIR / versi, models / versi)

    shutil.copy(DATA_DIR / "hr_turnover.csv", data / "hr_turnover.csv")

    return models, data


def test_list_versions_returns_numbered_versions(sandbox):
    models, _ = sandbox

    assert list_versions("turnover") == list(TURNOVER_VERSIONS)


def test_list_versions_works_for_all_use_cases(sandbox):
    # Setelah generalisasi, use case non-turnover juga melaporkan versinya.
    assert list_versions("absensi") == ["v1"]


def test_list_versions_rejects_unknown_use_case():
    with pytest.raises(LifecycleError):
        list_versions("tidak_ada")


def test_resolve_active_version_defaults_without_pin(sandbox):
    models, _ = sandbox

    assert read_active_version("turnover", models) is None
    assert resolve_active_version("turnover", models_dir=models) == "v2_mitigated"


def test_promote_and_rollback_round_trip(sandbox):
    models, _ = sandbox

    hasil = promote("v2", models_dir=models, reason="uji")
    assert hasil["sebelumnya"] == "v2_mitigated"
    assert hasil["aktif"] == "v2"
    assert resolve_active_version("turnover", models_dir=models) == "v2"

    pin = json.loads((models / "active_version.json").read_text())
    assert pin["turnover"] == "v2"

    kembali = rollback(models_dir=models, reason="uji rollback")
    assert kembali["aktif"] == "v2_mitigated"
    assert resolve_active_version("turnover", models_dir=models) == "v2_mitigated"


def test_promote_unknown_version_is_rejected(sandbox):
    models, _ = sandbox

    with pytest.raises(LifecycleError):
        promote("v99", models_dir=models)


def test_rollback_without_previous_pin_is_rejected(sandbox):
    models, _ = sandbox

    with pytest.raises(LifecycleError):
        rollback(models_dir=models)


def test_active_version_info_reports_metric(sandbox):
    models, _ = sandbox
    promote("v1", models_dir=models)

    info = active_version_info("turnover", models_dir=models)

    assert info["aktif"] == "v1"
    assert info["dipin"] is True
    assert info["metrik"][0] == "roc_auc"


def test_retraining_skips_when_no_drift(sandbox):
    models, data = sandbox
    referensi = reference_features(RetrainingConfig(), data_dir=data)

    hasil = run_retraining(
        RetrainingConfig(),
        current=referensi,
        reference=referensi,
        data_dir=data,
        models_dir=models,
    )

    assert hasil["status"] == "tidak_perlu"
    assert not (models / "v3").exists()
    assert resolve_active_version("turnover", models_dir=models) == "v2_mitigated"


def test_retraining_promotes_new_version_when_forced(sandbox):
    models, data = sandbox
    referensi = reference_features(RetrainingConfig(), data_dir=data)

    hasil = run_retraining(
        RetrainingConfig(force=True),
        current=referensi,
        reference=referensi,
        data_dir=data,
        models_dir=models,
    )

    assert hasil["status"] == "dipromosikan"
    assert hasil["versi_baru"] == "v3"
    assert hasil["gate"]["lulus"] is True
    assert (models / "v3" / "turnover_model.joblib").exists()
    assert resolve_active_version("turnover", models_dir=models) == "v3"


def test_retraining_rejects_candidate_below_incumbent(sandbox):
    models, data = sandbox
    referensi = reference_features(RetrainingConfig(), data_dir=data)

    hasil = run_retraining(
        RetrainingConfig(force=True, min_metric_delta=1.0),
        current=referensi,
        reference=referensi,
        data_dir=data,
        models_dir=models,
    )

    assert hasil["status"] == "ditolak"
    assert hasil["versi_aktif"] == "v2_mitigated"
    assert resolve_active_version("turnover", models_dir=models) == "v2_mitigated"


def test_retraining_stops_when_gate_fails(sandbox):
    models, data = sandbox
    referensi = reference_features(RetrainingConfig(), data_dir=data)

    hasil = run_retraining(
        RetrainingConfig(force=True, min_roc_auc=0.999),
        current=referensi,
        reference=referensi,
        data_dir=data,
        models_dir=models,
    )

    assert hasil["status"] == "gagal_gate"
    assert hasil["versi_aktif"] == "v2_mitigated"


def test_retraining_detects_drift_and_trains(sandbox):
    models, data = sandbox
    referensi = reference_features(RetrainingConfig(), data_dir=data)

    # Geser distribusi jam lembur agar PSI melewati ambang peringatan.
    bergeser = referensi.copy()
    bergeser["jam_lembur_bulanan"] = bergeser["jam_lembur_bulanan"] + 50

    hasil = run_retraining(
        RetrainingConfig(),
        current=bergeser,
        reference=referensi,
        data_dir=data,
        models_dir=models,
    )

    assert hasil["drift"]["drift_terdeteksi"] is True
    assert hasil["status"] in {"dipromosikan", "ditolak", "gagal_gate"}


def test_next_version_ignores_non_numbered_versions(sandbox):
    models, _ = sandbox

    assert next_version(models, "turnover") == "v3"


def test_registry_resolves_pinned_turnover_version(sandbox):
    models, _ = sandbox

    assert ModelRegistry(models).get("turnover").versi == "v2_mitigated"

    promote("v1", models_dir=models)

    assert ModelRegistry(models).get("turnover").versi == "v1"


def test_registry_explicit_version_overrides_pin(sandbox):
    models, _ = sandbox
    promote("v1", models_dir=models)

    assert ModelRegistry(models).get("turnover", versi="v2").versi == "v2"


# ---------------------------------------------------------------------------
# Versioning untuk use case non-turnover (generalisasi)
# ---------------------------------------------------------------------------


@pytest.fixture()
def sandbox_kinerja(tmp_path: Path) -> Path:
    """Salinan artifact kinerja (satu versi) untuk uji versioning generik."""
    models = tmp_path / "models"
    models.mkdir()

    shutil.copytree(MODELS_DIR / "performance" / "v1", models / "performance" / "v1")

    return models


def test_list_versions_for_non_turnover(sandbox_kinerja):
    assert list_versions("kinerja", registry=ModelRegistry(sandbox_kinerja)) == ["v1"]


def test_promote_non_turnover_use_case(sandbox_kinerja):
    models = sandbox_kinerja

    hasil = promote("v1", "kinerja", models_dir=models, reason="uji kinerja")

    assert hasil["aktif"] == "performance/v1"
    assert resolve_active_version("kinerja", models_dir=models) == "performance/v1"
    assert ModelRegistry(models).get("kinerja").versi == "v1"


def test_rollback_non_turnover_use_case(sandbox_kinerja):
    models = sandbox_kinerja

    promote("v1", "kinerja", models_dir=models, reason="uji")
    kembali = rollback("kinerja", models_dir=models, reason="uji rollback")

    assert kembali["aktif"] == "performance/v1"


def test_next_version_for_non_turnover(sandbox_kinerja):
    assert next_version(sandbox_kinerja, "kinerja") == "v2"


def test_unknown_use_case_promote_is_rejected(sandbox_kinerja):
    with pytest.raises(LifecycleError):
        promote("v1", "tidak_ada", models_dir=sandbox_kinerja)


def test_legacy_string_pin_still_rolls_back(sandbox):
    """Pin format lama (`sebelumnya` string) tetap bisa di-rollback."""
    models, _ = sandbox
    (models / "active_version.json").write_text(
        json.dumps({"turnover": "v2", "sebelumnya": "v1"}),
        encoding="utf-8",
    )

    kembali = rollback("turnover", models_dir=models)

    assert kembali["aktif"] == "v1"


def test_api_lifecycle_status_and_guardrails():
    from fastapi.testclient import TestClient

    from src.api import app

    client = TestClient(app)

    status = client.get("/lifecycle/turnover")
    assert status.status_code == 200
    assert status.json()["use_case"] == "turnover"
    assert status.json()["aktif"]

    # Use case non-turnover kini juga punya endpoint lifecycle.
    absensi = client.get("/lifecycle/absensi")
    assert absensi.status_code == 200
    assert absensi.json()["aktif"] == "absenteeism/v1"

    tidak_ada = client.get("/lifecycle/tidak_ada")
    assert tidak_ada.status_code == 400

    tidak_perlu = client.post("/retraining/run", json={})
    assert tidak_perlu.status_code == 200
    assert tidak_perlu.json()["status"] == "tidak_perlu"

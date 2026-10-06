from __future__ import annotations

import json
import os
from pathlib import Path
from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[2]

load_dotenv(BASE_DIR / ".env")

# Artifact model berada di root repository (models/), bukan di dalam folder API.
REPO_ROOT = BASE_DIR.parent

MODELS_DIR = REPO_ROOT / "models"
DATA_DIR = REPO_ROOT / "data"

# Pin versi aktif yang ditulis MLOps (`models/active_version.json`).
ACTIVE_VERSION_FILE = "active_version.json"
DEFAULT_MODEL_VERSION = "v2_mitigated"


def _read_pinned_version(use_case: str = "turnover") -> str | None:
    """Versi aktif dari pin MLOps, atau `None` bila belum ada pin."""
    path = MODELS_DIR / ACTIVE_VERSION_FILE

    if not path.exists():
        return None

    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return None

    if not isinstance(data, dict):
        return None

    value = data.get(use_case)

    return value if isinstance(value, str) and value else None


# Versi model turnover aktif. Urutan prioritas:
#   1. env MODEL_VERSION (override manual, mis. saat rollback darurat);
#   2. pin `models/active_version.json` yang ditulis MLOps (promote/retraining);
#   3. default `v2_mitigated` (versi paling adil).
# Artifact disimpan per versi di models/<MODEL_VERSION>/.
MODEL_VERSION = (
    os.getenv("MODEL_VERSION")
    or _read_pinned_version("turnover")
    or DEFAULT_MODEL_VERSION
)


APP_TITLE = "HR Intelligence API"
APP_VERSION = "1.1.0"
APP_DESCRIPTION = (
    "Platform analitik dan prediktif berbasis Machine Learning "
    "untuk manajemen sumber daya manusia."
)


# ---------------------------------------------------------------------------
# Registry use case model.
#
# Setiap use case punya satu artifact di models/<dir>/<versi>/ dan endpoint
# sendiri di /predict/<key>. Turnover memakai MODEL_VERSION (env) agar
# rollback v1/v2 tetap bisa; use case lain punya versi tetap di registry.
# ---------------------------------------------------------------------------
USE_CASE_REGISTRY: dict[str, dict] = {
    "turnover": {
        "label": "Turnover karyawan",
        "model_dir": MODEL_VERSION,
        "model_file": "turnover_model.joblib",
        "metadata_file": "model_metadata.json",
        "predict_path": "/predict",
    },
    "absensi": {
        "label": "Absensi tinggi",
        "model_dir": "absenteeism/v1",
        "model_file": "absenteeism_model.joblib",
        "metadata_file": "model_metadata.json",
        "predict_path": "/predict/absensi",
    },
    "kinerja": {
        "label": "Kategori kinerja",
        "model_dir": "performance/v1",
        "model_file": "performance_model.joblib",
        "metadata_file": "model_metadata.json",
        "predict_path": "/predict/kinerja",
    },
    "payroll": {
        "label": "Anomali payroll",
        "model_dir": "payroll_anomaly/v1",
        "model_file": "payroll_anomaly_model.joblib",
        "metadata_file": "model_metadata.json",
        "predict_path": "/predict/payroll",
    },
    "survival": {
        "label": "Survival attrition",
        "model_dir": "survival_attrition/v1",
        "model_file": "cox_model.pkl",
        "metadata_file": "model_metadata.json",
        "predict_path": "/predict/survival",
    },
}


def use_case_dir(use_case: str) -> Path:
    """Folder artifact untuk sebuah use case."""
    return MODELS_DIR / USE_CASE_REGISTRY[use_case]["model_dir"]


def use_case_paths(use_case: str) -> tuple[Path, Path]:
    """(model_path, metadata_path) untuk sebuah use case."""
    cfg = USE_CASE_REGISTRY[use_case]
    directory = use_case_dir(use_case)
    return directory / cfg["model_file"], directory / cfg["metadata_file"]


API_KEY = os.getenv(
    "HR_API_KEY",
)


RATE_LIMIT_PER_MINUTE = int(
    os.getenv(
        "RATE_LIMIT_PER_MINUTE",
        "120",
    )
)


MAX_BATCH_SIZE = int(
    os.getenv(
        "MAX_BATCH_SIZE",
        "5000",
    )
)


LOG_LEVEL = os.getenv(
    "LOG_LEVEL",
    "INFO",
)

# Docs interaktif (Swagger/ReDoc/OpenAPI) nonaktif secara default agar skema
# tidak terbuka tanpa kunci. Aktifkan eksplisit untuk lokal: ENABLE_DOCS=true.
ENABLE_DOCS = os.getenv(
    "ENABLE_DOCS",
    "false",
).strip().lower() in {"1", "true", "yes", "on"}

# Origin browser yang boleh mengakses API. Kosong = CORS nonaktif (default),
# karena dashboard di dev memakai proxy Vite dan tidak butuh CORS.
CORS_ALLOW_ORIGINS = [
    origin.strip()
    for origin in os.getenv("CORS_ALLOW_ORIGINS", "").split(",")
    if origin.strip()
]
from __future__ import annotations

import os
from pathlib import Path
from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[2]

load_dotenv(BASE_DIR / ".env")

# Artifact model berada di root repository (models/), bukan di dalam folder API.
REPO_ROOT = BASE_DIR.parent

MODELS_DIR = REPO_ROOT / "models"
DATA_DIR = REPO_ROOT / "data"

# Versi model aktif. Artifact disimpan per versi di models/<MODEL_VERSION>/.
# Ganti lewat env MODEL_VERSION untuk rollback/promosi model (mis. "v1").
MODEL_VERSION = os.getenv(
    "MODEL_VERSION",
    "v2",
)


APP_TITLE = "HR Intelligence API"
APP_VERSION = "1.0.0"
APP_DESCRIPTION = (
    "Platform analitik dan prediktif berbasis Machine Learning "
    "untuk manajemen sumber daya manusia."
)


DEFAULT_MODEL_PATH = MODELS_DIR / MODEL_VERSION / "turnover_model.joblib"
DEFAULT_METADATA_PATH = MODELS_DIR / MODEL_VERSION / "model_metadata.json"


MODEL_PATH = Path(
    os.getenv(
        "MODEL_PATH",
        DEFAULT_MODEL_PATH,
    )
)

METADATA_PATH = Path(
    os.getenv(
        "METADATA_PATH",
        DEFAULT_METADATA_PATH,
    )
)


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
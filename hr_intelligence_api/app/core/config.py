from __future__ import annotations

import os
from pathlib import Path
from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[2]

load_dotenv(BASE_DIR / ".env")

MODELS_DIR = BASE_DIR / "models"
DATA_DIR = BASE_DIR / "data"


APP_TITLE = "HR Intelligence API"
APP_VERSION = "1.0.0"
APP_DESCRIPTION = (
    "Platform analitik dan prediktif berbasis Machine Learning "
    "untuk manajemen sumber daya manusia."
)


MODEL_PATH = Path(
    os.getenv(
        "MODEL_PATH",
        MODELS_DIR / "turnover_model.joblib",
    )
)

METADATA_PATH = Path(
    os.getenv(
        "METADATA_PATH",
        MODELS_DIR / "model_metadata.json",
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
from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = BASE_DIR.parent

load_dotenv(BASE_DIR / ".env")

DATA_DIR = REPO_ROOT / "data"
EXPERIMENTS_DIR = BASE_DIR / "experiments" / "runs"
ARTIFACTS_DIR = BASE_DIR / "artifacts"

TURNOVER_DATA_PATH = Path(
    os.getenv("DEEP_LEARNING_TURNOVER_DATA", str(DATA_DIR / "hr_turnover.csv"))
)

# Tanggal acuan harus sama dengan kontrak fitur turnover di
# `hr_intelligence_api` dan notebook training (lihat AGENTS.md).
REF_DATE = os.getenv("DEEP_LEARNING_REF_DATE", "2025-01-01")

SEED = int(os.getenv("DEEP_LEARNING_SEED", "42"))

# Arsitektur default MLP. Backend NumPy, tanpa dependensi deep learning berat.
HIDDEN_SIZES = tuple(
    int(x)
    for x in os.getenv("DEEP_LEARNING_HIDDEN", "32,16").split(",")
    if x.strip()
)
LEARNING_RATE = float(os.getenv("DEEP_LEARNING_LR", "0.01"))
EPOCHS = int(os.getenv("DEEP_LEARNING_EPOCHS", "200"))
BATCH_SIZE = int(os.getenv("DEEP_LEARNING_BATCH_SIZE", "64"))
PATIENCE = int(os.getenv("DEEP_LEARNING_PATIENCE", "20"))
VAL_FRACTION = float(os.getenv("DEEP_LEARNING_VAL_FRACTION", "0.2"))

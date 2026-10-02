from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = BASE_DIR.parent

load_dotenv(BASE_DIR / ".env")

MODELS_DIR = REPO_ROOT / "models"
DATA_DIR = REPO_ROOT / "data"
MONITORING_DIR = BASE_DIR / "monitoring"

HR_INTELLIGENCE_BASE_URL = os.getenv(
    "HR_INTELLIGENCE_BASE_URL", "http://127.0.0.1:8000"
)
HR_INTELLIGENCE_API_KEY = os.getenv("HR_INTELLIGENCE_API_KEY", "")
HR_INTELLIGENCE_TIMEOUT = float(os.getenv("HR_INTELLIGENCE_TIMEOUT", "10"))

# Ambang drift (PSI). PSI > 0.2 umumnya dianggap pergeseran signifikan.
DRIFT_WARNING = float(os.getenv("DRIFT_WARNING", "0.1"))
DRIFT_CRITICAL = float(os.getenv("DRIFT_CRITICAL", "0.2"))

# Batas minimal ROC AUC sebelum model dinilai tidak layak deploy.
MIN_ROC_AUC = float(os.getenv("MIN_ROC_AUC", "0.6"))

from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = BASE_DIR.parent

load_dotenv(BASE_DIR / ".env")


def _float(name: str, default: float) -> float:
    try:
        return float(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


def _int(name: str, default: int) -> int:
    try:
        return int(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


DATA_PATH = Path(os.getenv("PAYROLL_DATA_PATH", "data/hr_payroll.csv"))
if not DATA_PATH.is_absolute():
    DATA_PATH = REPO_ROOT / DATA_PATH

HR_INTELLIGENCE_BASE_URL = os.getenv(
    "HR_INTELLIGENCE_BASE_URL", "http://127.0.0.1:8000"
)
HR_INTELLIGENCE_API_KEY = os.getenv("HR_INTELLIGENCE_API_KEY", "")
HR_INTELLIGENCE_TIMEOUT = _float("HR_INTELLIGENCE_TIMEOUT", 15.0)

ANOMALY_THRESHOLD = _float("ANOMALY_THRESHOLD", 0.5)

FORECAST_HORIZON = _int("FORECAST_HORIZON", 6)
FORECAST_SEASONAL_PERIOD = _int("FORECAST_SEASONAL_PERIOD", 12)

REPORTS_DIR = BASE_DIR / "reports" / "output"

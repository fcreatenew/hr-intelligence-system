from __future__ import annotations

import json
import os
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[1]
REPO_ROOT = BASE_DIR.parent

load_dotenv(BASE_DIR / ".env")

MODELS_DIR = REPO_ROOT / "models"
DATA_DIR = REPO_ROOT / "data"
MONITORING_DIR = BASE_DIR / "monitoring"

# Nama berkas pin versi aktif. Ditulis oleh siklus hidup (promote/retraining)
# dan dibaca oleh registry + API, sehingga keduanya menunjuk versi yang sama.
ACTIVE_VERSION_FILE = "active_version.json"

# Versi turnover yang dipakai bila belum ada pin. Sama dengan default API.
DEFAULT_TURNOVER_VERSION = "v2_mitigated"


def read_pinned_version(
    models_dir: Path = MODELS_DIR,
    use_case: str = "turnover",
) -> str | None:
    """Versi aktif yang dipin di `models/active_version.json`, atau `None`.

    Fungsi ini sengaja diletakkan di `config` (lapisan paling bawah) agar
    registry dan lifecycle dapat memakainya tanpa impor melingkar.
    """
    path = models_dir / ACTIVE_VERSION_FILE

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


def resolve_turnover_version(models_dir: Path = MODELS_DIR) -> str:
    """Sumber kebenaran tunggal versi turnover: pin → default."""
    return read_pinned_version(models_dir, "turnover") or DEFAULT_TURNOVER_VERSION


HR_INTELLIGENCE_BASE_URL = os.getenv(
    "HR_INTELLIGENCE_BASE_URL", "http://127.0.0.1:8000"
)
HR_INTELLIGENCE_API_KEY = os.getenv("HR_INTELLIGENCE_API_KEY", "")
HR_INTELLIGENCE_TIMEOUT = float(os.getenv("HR_INTELLIGENCE_TIMEOUT", "10"))

# Ambang drift (PSI). PSI > 0.2 umumnya dianggap pergeseran signifikan.
DRIFT_WARNING = float(os.getenv("DRIFT_WARNING", "0.1"))
DRIFT_CRITICAL = float(os.getenv("DRIFT_CRITICAL", "0.2"))

# Pengiriman alert drift ke webhook (Slack/Teams/Alertmanager generic).
# Kosong = alert tidak dikirim, hanya dicatat sebagai metrik.
ALERT_WEBHOOK_URL = os.getenv("ALERT_WEBHOOK_URL", "")
ALERT_TIMEOUT = float(os.getenv("ALERT_TIMEOUT", "5"))

# Batas minimal ROC AUC sebelum model dinilai tidak layak deploy.
MIN_ROC_AUC = float(os.getenv("MIN_ROC_AUC", "0.6"))

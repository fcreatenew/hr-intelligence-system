from __future__ import annotations

import re
from dataclasses import dataclass, field

from src.schemas.chat import Intent


# Kata kunci per use case. Dipakai untuk memilih tool prediksi yang tepat.
USE_CASE_KEYWORDS: dict[str, tuple[str, ...]] = {
    "turnover": (
        "turnover", "resign", "keluar", "berhenti", "attrition", "retensi",
        "risiko keluar", "mengundurkan diri",
    ),
    "absensi": (
        "absensi", "absen", "kehadiran", "tidak hadir", "alpha", "sakit",
        "absen tinggi",
    ),
    "kinerja": (
        "kinerja", "performance", "rating", "evaluasi kinerja", "kategori kinerja",
    ),
    "payroll": (
        "payroll", "gaji", "anomali", "penggajian", "tunjangan", "potongan",
        "slip gaji",
    ),
    "survival": (
        "survival", "median survival", "bertahan", "masa kerja", "kapan keluar",
    ),
}

# Kata kunci yang menandakan permintaan prediksi (bukan sekadar tanya kebijakan).
PREDICTION_CUES = (
    "prediksi", "prediksikan", "hitung", "skor", "score", "risiko", "risk",
    "berapa probabilitas", "probabilitas", "kemungkinan", "level risiko",
    "cek", "analisis", "analisa",
)

# Kata kunci metadata model.
MODEL_INFO_CUES = (
    "model apa", "daftar model", "info model", "versi model", "metrik model",
    "model tersedia", "algoritma",
)

EMPLOYEE_ID_RE = re.compile(r"\b([A-Z]{2,6}\d{2,8})\b")


@dataclass
class RouteDecision:
    intent: Intent
    use_cases: list[str] = field(default_factory=list)
    employee_id: str | None = None
    wants_model_info: bool = False
    reason: str = ""


def detect_use_cases(message: str) -> list[str]:
    lowered = message.lower()
    matched: list[str] = []

    for use_case, keywords in USE_CASE_KEYWORDS.items():
        if any(keyword in lowered for keyword in keywords):
            matched.append(use_case)

    return matched


def detect_employee_id(message: str) -> str | None:
    match = EMPLOYEE_ID_RE.search(message)

    return match.group(1) if match else None


def route(
    message: str,
    employee_id: str | None = None,
    forced_use_case: str | None = None,
) -> RouteDecision:
    """Tentukan intent dan tool yang perlu dipanggil dari pesan pengguna."""
    lowered = message.lower()

    if not lowered.strip():
        return RouteDecision(intent="out_of_scope", reason="Pesan kosong.")

    use_cases = [forced_use_case] if forced_use_case else detect_use_cases(message)
    resolved_employee = employee_id or detect_employee_id(message)

    wants_model_info = any(cue in lowered for cue in MODEL_INFO_CUES)
    has_prediction_cue = any(cue in lowered for cue in PREDICTION_CUES)

    # Prediksi butuh use case yang jelas dan sinyal permintaan prediksi.
    if use_cases and (has_prediction_cue or resolved_employee):
        return RouteDecision(
            intent="employee_insight",
            use_cases=use_cases,
            employee_id=resolved_employee,
            reason="Permintaan prediksi model HR.",
        )

    if wants_model_info:
        return RouteDecision(
            intent="hybrid",
            use_cases=use_cases or [],
            employee_id=resolved_employee,
            wants_model_info=True,
            reason="Pertanyaan metadata model.",
        )

    return RouteDecision(
        intent="knowledge",
        use_cases=use_cases,
        employee_id=resolved_employee,
        reason="Pertanyaan knowledge HR.",
    )

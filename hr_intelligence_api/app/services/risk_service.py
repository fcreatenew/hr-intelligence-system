from typing import Literal


RiskLevel = Literal[
    "Rendah",
    "Sedang",
    "Tinggi",
]


def get_risk_level(
    probability: float,
) -> RiskLevel:
    if probability < 0.25:
        return "Rendah"

    if probability < 0.50:
        return "Sedang"

    return "Tinggi"
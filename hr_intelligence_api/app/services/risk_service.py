from typing import Literal


RiskLevel = Literal[
    "Rendah",
    "Sedang",
    "Tinggi",
]


# Dipakai bila pemanggil tidak punya threshold model.
DEFAULT_HIGH_THRESHOLD = 0.50

# Batas Rendah/Sedang relatif terhadap threshold.
RENDAH_FRACTION = 0.5


def get_risk_level(
    probability: float,
    threshold: float | None = None,
) -> RiskLevel:
    """Petakan probabilitas ke level risiko.

    Ambang `Tinggi` memakai threshold model (batas `prediksi_keluar`), bukan
    angka tetap 0.5. Tanpa ini, karyawan dengan probabilitas di antara
    threshold dan 0.5 akan dilabeli `prediksi_keluar=1` tetapi `level_risiko`
    Rendah/Sedang — dua angka yang saling bertentangan bagi pengguna.

    Rentang di bawah threshold dibagi dua agar gradasi Rendah/Sedang tetap
    ada: `Rendah` < threshold/2, `Sedang` < threshold, `Tinggi` >= threshold.
    """
    if threshold is None:
        threshold = DEFAULT_HIGH_THRESHOLD

    if probability < threshold * RENDAH_FRACTION:
        return "Rendah"

    if probability < threshold:
        return "Sedang"

    return "Tinggi"

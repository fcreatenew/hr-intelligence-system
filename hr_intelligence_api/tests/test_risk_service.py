from app.services.risk_service import get_risk_level


def test_risk_level_aligns_with_model_threshold():
    """`Tinggi` harus mulai tepat di threshold, bukan di 0.5 tetap."""
    threshold = 0.31

    assert get_risk_level(0.30, threshold) == "Sedang"
    assert get_risk_level(0.31, threshold) == "Tinggi"
    assert get_risk_level(0.45, threshold) == "Tinggi"


def test_risk_level_gradient_below_threshold():
    threshold = 0.4

    assert get_risk_level(0.0, threshold) == "Rendah"
    assert get_risk_level(0.19, threshold) == "Rendah"
    assert get_risk_level(0.20, threshold) == "Sedang"
    assert get_risk_level(0.39, threshold) == "Sedang"
    assert get_risk_level(0.4, threshold) == "Tinggi"


def test_risk_level_fallback_without_threshold():
    assert get_risk_level(0.24) == "Rendah"
    assert get_risk_level(0.25) == "Sedang"
    assert get_risk_level(0.50) == "Tinggi"

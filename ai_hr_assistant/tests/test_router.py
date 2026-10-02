from src.agents.router import detect_employee_id, detect_use_cases, route


def test_detect_use_cases_multi():
    message = "Prediksi turnover dan absensi karyawan EMP0001"

    assert set(detect_use_cases(message)) == {"turnover", "absensi"}


def test_detect_employee_id():
    assert detect_employee_id("cek EMP00123 dong") == "EMP00123"
    assert detect_employee_id("tidak ada id") is None


def test_route_prediction_intent():
    decision = route("Berapa risiko turnover karyawan EMP0001?")

    assert decision.intent == "employee_insight"
    assert decision.use_cases == ["turnover"]
    assert decision.employee_id == "EMP0001"


def test_route_knowledge_intent():
    decision = route("Bagaimana prosedur pengajuan cuti tahunan?")

    assert decision.intent == "knowledge"
    assert decision.use_cases == []


def test_route_model_info_intent():
    decision = route("Model apa saja yang tersedia dan metrik modelnya?")

    assert decision.wants_model_info is True
    assert decision.intent == "hybrid"


def test_route_forced_use_case_with_employee():
    decision = route("Bagaimana kondisinya?", employee_id="EMP0009", forced_use_case="kinerja")

    assert decision.intent == "employee_insight"
    assert decision.use_cases == ["kinerja"]
    assert decision.employee_id == "EMP0009"


def test_route_empty_message():
    assert route("   ").intent == "out_of_scope"

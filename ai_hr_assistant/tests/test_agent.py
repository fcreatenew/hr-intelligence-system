from src.schemas.chat import AssistantResponse


def test_knowledge_answer_has_citations(agent):
    response = agent.ask("Bagaimana prosedur pengajuan cuti tahunan?")

    assert isinstance(response, AssistantResponse)
    assert response.intent == "knowledge"
    assert response.citations
    assert any(c.doc_id == "pengajuan-cuti" for c in response.citations)
    assert "prosedur" in response.answer.lower() or "cuti" in response.answer.lower()


def test_prediction_answer_uses_tool(agent):
    response = agent.ask(
        "Berapa risiko turnover karyawan EMP0001?",
        employee_id="EMP0001",
    )

    assert response.intent == "employee_insight"
    assert response.tool_calls
    call = response.tool_calls[0]
    assert call.tool == "predict_turnover"
    assert call.ok
    assert "0.68" in response.answer
    assert "bukan dasar keputusan otomatis" in response.answer


def test_prediction_for_each_model(agent):
    cases = {
        "turnover": "risiko turnover",
        "absensi": "prediksi absensi tinggi",
        "kinerja": "kategori kinerja",
        "payroll": "anomali payroll",
        "survival": "risiko survival",
    }

    for use_case, phrase in cases.items():
        response = agent.ask(f"{phrase} karyawan EMP0001", employee_id="EMP0001")

        assert response.intent == "employee_insight", use_case
        assert any(call.use_case == use_case and call.ok for call in response.tool_calls), use_case


def test_unknown_question_is_handled_gracefully(agent):
    response = agent.ask("Apa resep nasi goreng paling enak?")

    assert response.answer
    assert response.intent in {"knowledge", "out_of_scope"}


def test_session_memory_persists_employee(agent):
    first = agent.ask("Risiko turnover karyawan EMP0042?", employee_id="EMP0042")
    second = agent.ask("Lalu bagaimana dengan absensinya?", session_id=first.session_id)

    assert second.session_id == first.session_id
    assert any(call.use_case == "absensi" for call in second.tool_calls)


def test_suggestions_are_returned(agent):
    response = agent.ask("Berapa risiko turnover karyawan EMP0001?")

    assert 1 <= len(response.suggestions) <= 3


def test_model_info_question_lists_models(agent):
    response = agent.ask("Model apa saja yang tersedia?")

    assert any(call.tool == "list_models" and call.ok for call in response.tool_calls)

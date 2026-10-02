from src.tools.hr_intelligence_client import (
    HrIntelligenceAuthError,
    HrIntelligenceError,
)
from src.tools.prediction_tools import build_payload, build_tool_registry


def test_all_five_models_have_tools(tools):
    for use_case in ("turnover", "absensi", "kinerja", "payroll", "survival"):
        assert f"predict_{use_case}" in tools

    assert "model_info" in tools
    assert "list_models" in tools
    assert "health" in tools


def test_tool_call_returns_structured_result(tools):
    result = tools.call("predict_turnover", employee_id="EMP0001")

    assert result.ok
    assert result.use_case == "turnover"
    assert result.data["probabilitas_keluar"] == 0.68
    assert "0.68" in result.summary


def test_each_prediction_tool_returns_summary(tools):
    for use_case in ("absensi", "kinerja", "payroll", "survival"):
        result = tools.call(f"predict_{use_case}", employee_id="EMP0001")

        assert result.ok, result.error
        assert result.summary


def test_payroll_payload_keeps_total_consistent():
    payload = build_payload("payroll", "EMP0001")

    expected = (
        payload["gaji_pokok"]
        + payload["tunjangan"]
        + payload["lembur_jam"] * payload["tarif_lembur"]
        - payload["potongan"]
    )

    assert payload["total_gaji"] == expected


def test_unknown_tool_raises(tools):
    import pytest

    from src.tools.registry import ToolError

    with pytest.raises(ToolError):
        tools.call("predict_tidak_ada", employee_id="EMP0001")


def test_auth_error_is_reported_as_failed_tool(fake_client):
    fake_client._api_key = "salah"  # noqa: SLF001 - sengaja menguji jalur auth
    registry = build_tool_registry(fake_client)

    result = registry.call("predict_turnover", employee_id="EMP0001")

    assert result.ok is False
    assert result.error


def test_model_info_and_list(tools):
    info = tools.call("model_info", use_case="turnover")
    listing = tools.call("list_models")
    health = tools.call("health")

    assert info.ok and info.data["versi_model"] == "v2"
    assert listing.ok and len(listing.data["use_cases"]) == 5
    assert health.ok and health.data["models_loaded"] == 5


def test_client_rejects_unknown_use_case(fake_client):
    import pytest

    with pytest.raises(HrIntelligenceError):
        fake_client.predict("tidak-ada", {})


def test_client_requires_api_key_for_predict():
    import httpx
    import pytest

    from src.tools.hr_intelligence_client import HrIntelligenceClient

    client = HrIntelligenceClient(
        base_url="http://testserver",
        api_key="",
        client=httpx.Client(transport=httpx.MockTransport(_handler_unused)),
    )

    with pytest.raises(HrIntelligenceAuthError):
        client.predict("turnover", {"employee_id": "EMP0001"})


def _handler_unused(request):  # pragma: no cover - tidak pernah dipanggil
    import httpx

    return httpx.Response(500)

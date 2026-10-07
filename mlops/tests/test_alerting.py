from __future__ import annotations

import sys
from pathlib import Path

import httpx

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from src.monitoring.alerting import (  # noqa: E402
    AlertDispatcher,
    WebhookAlertSender,
    build_alert_sender,
)
from src.monitoring.drift_monitor import DriftReport  # noqa: E402
from src.monitoring.metrics import MetricsCollector  # noqa: E402


class FakeSender:
    def __init__(self):
        self.payloads: list[dict] = []

    def send(self, payload: dict) -> None:
        self.payloads.append(payload)


def _report(drift: bool, level: str = "critical", psi: float = 0.42) -> DriftReport:
    return DriftReport(
        use_case="turnover",
        level=level,
        max_psi=psi,
        drift_terdeteksi=drift,
        samples=500,
        fitur=[{"fitur": "gaji_bulanan", "psi": psi}],
    )


def test_payload_memuat_konteks_drift():
    payload = AlertDispatcher().build_payload(_report(True))

    assert payload["use_case"] == "turnover"
    assert payload["level"] == "critical"
    assert payload["psi_maksimum"] == 0.42
    assert payload["fitur_teratas"] == "gaji_bulanan"
    assert "drift" in payload["pesan"].lower()


def test_dispatch_mengirim_saat_drift():
    sender = FakeSender()
    collector = MetricsCollector()

    hasil = AlertDispatcher(sender=sender, collector=collector).dispatch(_report(True))

    assert hasil["terkirim"] is True
    assert len(sender.payloads) == 1
    assert collector.summary()["counters"]["drift_alerts_dispatched_total{level=\"critical\",use_case=\"turnover\"}"] == 1.0


def test_dispatch_tidak_mengirim_saat_stabil():
    sender = FakeSender()

    hasil = AlertDispatcher(sender=sender).dispatch(_report(False, level="stable", psi=0.0))

    assert hasil["terkirim"] is False
    assert sender.payloads == []


def test_dispatch_tanpa_sender_aman():
    collector = MetricsCollector()

    hasil = AlertDispatcher(collector=collector).dispatch(_report(True))

    assert hasil["terkirim"] is False
    assert "tidak ada sender" in hasil["alasan"]
    assert collector.summary()["counters"]


def test_dispatch_gagal_kirim_tidak_melempar():
    class Rusak:
        def send(self, payload: dict) -> None:
            raise RuntimeError("jaringan mati")

    collector = MetricsCollector()

    hasil = AlertDispatcher(sender=Rusak(), collector=collector).dispatch(_report(True))

    assert hasil["terkirim"] is False
    assert "pengiriman gagal" in hasil["alasan"]


def test_build_alert_sender_kosong_mengembalikan_none():
    assert build_alert_sender(None) is None
    assert build_alert_sender("") is None
    assert isinstance(build_alert_sender("https://contoh.test/hook"), WebhookAlertSender)


def test_webhook_sender_memakai_client():
    dipanggil: list[dict] = []

    def handler(request: httpx.Request) -> httpx.Response:
        dipanggil.append({"url": str(request.url)})
        return httpx.Response(200, json={"ok": True})

    client = httpx.Client(transport=httpx.MockTransport(handler))
    sender = WebhookAlertSender(url="https://contoh.test/hook", client=client)

    sender.send({"jenis": "drift"})

    assert dipanggil == [{"url": "https://contoh.test/hook"}]


def test_cli_monitor_endpoint_api(monkeypatch):
    from fastapi.testclient import TestClient

    from src.api import app

    client = TestClient(app)

    resp = client.post("/monitoring/check-drift", json={})

    assert resp.status_code == 200
    body = resp.json()
    assert body["drift"]["use_case"] == "turnover"
    assert body["alert"]["terkirim"] is False

"""Pengiriman alert drift ke saluran luar (webhook) secara otomatis.

`DriftMonitor` mendeteksi drift dan mengekspor metrik Prometheus; aturan alert
di `monitoring/alerts/alerts.yml` membaca metrik itu, tetapi **pengirimannya**
ke manusia belum ada. Modul ini menutup celah tersebut: begitu drift
terdeteksi, sebuah payload dikirim ke webhook (Slack/Teams/Alertmanager
generic webhook, dsb.).

Kirim bersifat best-effort: kegagalan pengiriman tidak boleh menggagalkan
evaluasi drift (monitoring harus tetap jalan saat jaringan bermasalah).
"""

from __future__ import annotations

import logging
from dataclasses import dataclass
from typing import Protocol

import httpx

from src.monitoring.drift_monitor import DriftReport
from src.monitoring.metrics import MetricsCollector


logger = logging.getLogger("mlops.alerting")


class AlertSender(Protocol):
    """Kontrak pengirim alert. Implementasi cukup menyediakan `send`."""

    def send(self, payload: dict) -> None:  # pragma: no cover - protokol
        ...


@dataclass
class WebhookAlertSender:
    """Kirim payload JSON ke sebuah URL webhook."""

    url: str
    timeout: float = 5.0
    client: httpx.Client | None = None

    def send(self, payload: dict) -> None:
        if self.client is not None:
            response = self.client.post(self.url, json=payload)
            response.raise_for_status()
            return

        with httpx.Client(timeout=self.timeout) as client:
            response = client.post(self.url, json=payload)
            response.raise_for_status()


class AlertDispatcher:
    """Ubah `DriftReport` menjadi payload dan teruskan ke `AlertSender`."""

    def __init__(
        self,
        sender: AlertSender | None = None,
        collector: MetricsCollector | None = None,
    ) -> None:
        self._sender = sender
        self._collector = collector

    def build_payload(self, report: DriftReport) -> dict:
        fitur = report.fitur[0] if report.fitur else {}

        return {
            "sumber": "hr-intelligence-mlops",
            "jenis": "drift",
            "use_case": report.use_case,
            "level": report.level,
            "psi_maksimum": report.max_psi,
            "drift_terdeteksi": report.drift_terdeteksi,
            "fitur_teratas": fitur.get("fitur"),
            "samples": report.samples,
            "dievaluasi_pada": report.evaluated_at,
            "pesan": (
                f"Drift {report.level} pada {report.use_case}: "
                f"PSI maksimum {report.max_psi}."
            ),
        }

    def dispatch(self, report: DriftReport) -> dict:
        """Kirim alert bila drift terdeteksi. Kembalikan ringkasan hasil."""
        if not report.drift_terdeteksi:
            return {"terkirim": False, "alasan": "drift tidak terdeteksi"}

        payload = self.build_payload(report)

        if self._sender is None:
            if self._collector is not None:
                self._collector.inc_counter(
                    "drift_alerts_suppressed_total",
                    level=report.level,
                    use_case=report.use_case,
                )

            return {"terkirim": False, "alasan": "tidak ada sender dikonfigurasi", "payload": payload}

        try:
            self._sender.send(payload)
        except Exception as exc:  # noqa: BLE001 - alert tidak boleh menggagalkan evaluasi
            logger.warning("Gagal mengirim alert drift: %s", exc)

            if self._collector is not None:
                self._collector.inc_counter(
                    "drift_alerts_failed_total",
                    level=report.level,
                    use_case=report.use_case,
                )

            return {"terkirim": False, "alasan": f"pengiriman gagal: {exc}", "payload": payload}

        if self._collector is not None:
            self._collector.inc_counter(
                "drift_alerts_dispatched_total",
                level=report.level,
                use_case=report.use_case,
            )

        return {"terkirim": True, "payload": payload}


def build_alert_sender(
    webhook_url: str | None,
    timeout: float = 5.0,
) -> WebhookAlertSender | None:
    """Sender webhook bila URL diisi; `None` bila kosong (alert dimatikan)."""
    if not webhook_url:
        return None

    return WebhookAlertSender(url=webhook_url, timeout=timeout)

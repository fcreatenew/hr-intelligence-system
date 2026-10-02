from __future__ import annotations

from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone

import pandas as pd

from src.monitoring.drift import detect_feature_drift
from src.monitoring.metrics import MetricsCollector


@dataclass
class DriftReport:
    """Hasil satu evaluasi drift untuk satu use case."""

    use_case: str
    max_psi: float
    level: str
    drift_terdeteksi: bool
    fitur: list[dict] = field(default_factory=list)
    samples: int = 0
    evaluated_at: str = ""

    def __post_init__(self) -> None:
        if not self.evaluated_at:
            self.evaluated_at = datetime.now(timezone.utc).isoformat()

    def to_dict(self) -> dict:
        return asdict(self)


class DriftMonitor:
    """Menjalankan deteksi drift dan mengubahnya menjadi metrik + alert.

    Membungkus `detect_feature_drift` dengan dua hal yang sebelumnya tidak ada:
    metrik yang diekspor ke sistem monitoring (gauge `drift_max_psi` dan counter
    `drift_alerts_total`) serta riwayat evaluasi in-memory agar tren dapat
    ditelusuri. Aturan alert Prometheus di `monitoring/alerts/alerts.yml`
    membaca `drift_max_psi` ini.
    """

    def __init__(
        self,
        collector: MetricsCollector | None = None,
        warning: float = 0.1,
        critical: float = 0.2,
    ) -> None:
        self._collector = collector
        self._warning = warning
        self._critical = critical
        self._history: list[DriftReport] = []

    def evaluate(
        self,
        use_case: str,
        reference: pd.DataFrame,
        current: pd.DataFrame,
        features: list[str] | None = None,
    ) -> DriftReport:
        hasil = detect_feature_drift(
            reference,
            current,
            features=features,
            warning=self._warning,
            critical=self._critical,
        )

        fitur = hasil["fitur"]
        max_psi = fitur[0]["psi"] if fitur else 0.0

        report = DriftReport(
            use_case=use_case,
            max_psi=max_psi,
            level=hasil["level_tertinggi"],
            drift_terdeteksi=hasil["drift_terdeteksi"],
            fitur=fitur,
            samples=int(len(current)),
        )

        self._history.append(report)
        self._record_metrics(report)

        return report

    def _record_metrics(self, report: DriftReport) -> None:
        if self._collector is None:
            return

        self._collector.set_gauge("drift_max_psi", report.max_psi, use_case=report.use_case)
        self._collector.set_gauge(
            "drift_features_total",
            len(report.fitur),
            use_case=report.use_case,
        )

        if report.drift_terdeteksi:
            self._collector.inc_counter(
                "drift_alerts_total",
                level=report.level,
                use_case=report.use_case,
            )

    def history(self, use_case: str | None = None) -> list[DriftReport]:
        if use_case is None:
            return list(self._history)

        return [report for report in self._history if report.use_case == use_case]

    def latest(self, use_case: str) -> DriftReport | None:
        for report in reversed(self._history):
            if report.use_case == use_case:
                return report

        return None

    def alerts(self) -> list[DriftReport]:
        """Evaluasi terbaru per use case yang berada di atas ambang peringatan."""
        terbaru: dict[str, DriftReport] = {}

        for report in self._history:
            terbaru[report.use_case] = report

        return [report for report in terbaru.values() if report.drift_terdeteksi]

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime, timezone


@dataclass
class MetricEvent:
    name: str
    value: float
    labels: dict = field(default_factory=dict)
    timestamp: str = ""

    def __post_init__(self) -> None:
        if not self.timestamp:
            self.timestamp = datetime.now(timezone.utc).isoformat()


class MetricsCollector:
    """Kolektor metrik in-memory.

    Menyimpan titik metrik (counter/gauge/histogram ringkas) agar dapat
    diekspor sebagai format Prometheus maupun JSON. In-memory disengaja untuk
    kesederhanaan; implementasi produksi dapat menulis ke sistem monitoring.
    """

    def __init__(self):
        self._counters: dict[str, float] = {}
        self._gauges: dict[str, float] = {}
        self._histograms: dict[str, list[float]] = {}
        self._events: list[MetricEvent] = []

    def inc_counter(self, name: str, value: float = 1.0, **labels) -> None:
        key = self._key(name, labels)
        self._counters[key] = self._counters.get(key, 0.0) + value
        self._events.append(MetricEvent(name, value, labels))

    def set_gauge(self, name: str, value: float, **labels) -> None:
        key = self._key(name, labels)
        self._gauges[key] = float(value)
        self._events.append(MetricEvent(name, float(value), labels))

    def observe(self, name: str, value: float, **labels) -> None:
        key = self._key(name, labels)
        self._histograms.setdefault(key, []).append(float(value))
        self._events.append(MetricEvent(name, float(value), labels))

    @staticmethod
    def _key(name: str, labels: dict) -> str:
        if not labels:
            return name

        bagian = ",".join(f'{k}="{v}"' for k, v in sorted(labels.items()))

        return f"{name}{{{bagian}}}"

    def summary(self) -> dict:
        return {
            "counters": dict(self._counters),
            "gauges": dict(self._gauges),
            "histograms": {
                name: {
                    "count": len(values),
                    "avg": round(sum(values) / len(values), 4),
                    "min": round(min(values), 4),
                    "max": round(max(values), 4),
                }
                for name, values in self._histograms.items()
                if values
            },
        }

    def events(self) -> list[dict]:
        return [
            {"name": e.name, "value": e.value, "labels": e.labels, "ts": e.timestamp}
            for e in self._events
        ]

    def to_prometheus(self) -> str:
        lines: list[str] = []

        for key, value in self._counters.items():
            lines.append(f"{key} {value}")

        for key, value in self._gauges.items():
            lines.append(f"{key} {value}")

        for name, values in self._histograms.items():
            if values:
                lines.append(f"{name}_count {len(values)}")
                lines.append(f"{name}_sum {sum(values)}")

        return "\n".join(lines) + "\n"

    def reset(self) -> None:
        self._counters.clear()
        self._gauges.clear()
        self._histograms.clear()
        self._events.clear()

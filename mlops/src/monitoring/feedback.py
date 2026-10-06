"""Aliran umpan balik produksi: simpan prediksi + label sebenarnya.

`run_performance_monitor` (rollback berbasis performa) butuh pasangan
prediksi–label nyata, tetapi API HR Intelligence saat ini tidak mencatat label.
Modul ini menutup celah itu: menyimpan setiap prediksi yang diberi label
(dari sistem HR, verifikasi manual, atau job ETL) ke satu berkas JSONL
append-only, lalu mengekspornya sebagai CSV ber-kolom `target_turnover` agar
dapat dipakai langsung oleh monitoring performa dan deteksi drift.

Alur:

    prediksi + label sebenarnya
        │  FeedbackStore.record
        ▼
    monitoring/feedback.jsonl  (append-only)
        │  FeedbackStore.to_dataframe / export_csv
        ▼
    run_performance_monitor --current <csv>

Karena berkas bersifat append-only, riwayat tidak pernah hilang walau prediksi
sama datang berkali-kali.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path

import pandas as pd

from src.config import MONITORING_DIR

FEEDBACK_FILE = "feedback.jsonl"

# Nama kolom kanonik yang dipakai `performance.py`/`retraining.py`.
CANONICAL_SCORE = "skor"
CANONICAL_LABEL = "label_aktual"

# Alias yang lazim agar ekspor langsung dapat dikonsumsi monitor performa.
_SCORE_ALIASES: tuple[str, ...] = (
    "skor",
    "probabilitas_turnover",
    "probabilitas",
    "proba",
    "score",
)
_LABEL_ALIASES: tuple[str, ...] = ("label_aktual", "target_turnover")


def _canonical_columns(df: pd.DataFrame) -> pd.DataFrame:
    """Tambahkan kolom `skor`/`label_aktual` bila dapat diturunkan dari alias."""
    if df.empty:
        return df

    if CANONICAL_SCORE not in df.columns:
        for alias in _SCORE_ALIASES:
            if alias in df.columns:
                df[CANONICAL_SCORE] = df[alias]
                break

    if CANONICAL_LABEL not in df.columns:
        for alias in _LABEL_ALIASES:
            if alias in df.columns:
                df[CANONICAL_LABEL] = df[alias]
                break

    return df


@dataclass
class FeedbackStore:
    """Penyimpanan umpan balik sederhana berbasis JSONL append-only."""

    path: Path = field(default_factory=lambda: MONITORING_DIR / FEEDBACK_FILE)

    def __post_init__(self) -> None:
        self.path = Path(self.path)

    def record(
        self,
        prediction: dict,
        actual: dict,
        *,
        use_case: str = "turnover",
        version: str | None = None,
        recorded_at: str | None = None,
    ) -> dict:
        """Simpan satu pasangan prediksi–label sebenarnya.

        `prediction` memuat fitur mentah + skor/probabilitas; `actual` memuat
        label sebenarnya (mis. `{"target_turnover": 1}`).
        """
        if not actual:
            raise ValueError("Label sebenarnya (`actual`) tidak boleh kosong.")

        entry = {
            "use_case": use_case,
            "versi": version,
            "dicatat_pada": recorded_at or datetime.now(timezone.utc).isoformat(timespec="seconds"),
            "prediksi": dict(prediction),
            "aktual": dict(actual),
        }

        self.path.parent.mkdir(parents=True, exist_ok=True)
        with self.path.open("a", encoding="utf-8") as handle:
            handle.write(json.dumps(entry, ensure_ascii=False) + "\n")

        return entry

    def entries(self, use_case: str | None = None) -> list[dict]:
        if not self.path.exists():
            return []

        hasil: list[dict] = []
        for line in self.path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line:
                continue
            try:
                data = json.loads(line)
            except json.JSONDecodeError:
                continue
            if use_case and data.get("use_case") != use_case:
                continue
            hasil.append(data)

        return hasil

    def to_dataframe(self, use_case: str | None = "turnover") -> pd.DataFrame:
        """Gabungkan fitur prediksi + label aktual menjadi satu DataFrame.

        Kolom label (`target_turnover`) ditambahkan dari `aktual`, sehingga
        hasilnya cocok dengan dataset training dan bisa langsung dipakai
        monitoring performa/drift.
        """
        rows: list[dict] = []

        for entry in self.entries(use_case):
            row = dict(entry.get("prediksi", {}))
            row.update(entry.get("aktual", {}))
            rows.append(row)

        return _canonical_columns(pd.DataFrame(rows))

    def export_csv(
        self,
        destination: Path,
        use_case: str | None = "turnover",
    ) -> Path:
        df = self.to_dataframe(use_case)
        destination = Path(destination)
        destination.parent.mkdir(parents=True, exist_ok=True)
        df.to_csv(destination, index=False)

        return destination

    def clear(self, use_case: str | None = None) -> int:
        """Hapus umpan balik (semua atau satu use case). Kembalikan jumlah entri."""
        if use_case is None:
            jumlah = len(self.entries())
            if self.path.exists():
                self.path.unlink()
            return jumlah

        tersisa = [e for e in self.entries() if e.get("use_case") != use_case]
        jumlah = len(self.entries(use_case))

        self.path.parent.mkdir(parents=True, exist_ok=True)
        with self.path.open("w", encoding="utf-8") as handle:
            for entry in tersisa:
                handle.write(json.dumps(entry, ensure_ascii=False) + "\n")

        return jumlah

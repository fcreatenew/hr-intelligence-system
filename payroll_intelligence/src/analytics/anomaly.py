from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.ensemble import IsolationForest

from src.processing.transform import enrich, model_features


DEFAULT_CONTAMINATION = 0.04


class AnomalyDetector:
    """Detektor anomali payroll berbasis IsolationForest + aturan bisnis.

    Skor mentah `decision_function` dipetakan ke rentang 0-1 dengan fungsi
    logistik, meniru pendekatan model di HR Intelligence API, sehingga skor
    dapat dibandingkan antar batch (tidak dinormalisasi per-batch).
    """

    def __init__(
        self,
        contamination: float = DEFAULT_CONTAMINATION,
        threshold: float = 0.5,
        random_state: int = 42,
    ):
        self.contamination = contamination
        self.threshold = threshold
        self.random_state = random_state
        self._model: IsolationForest | None = None

    def fit(self, df: pd.DataFrame) -> "AnomalyDetector":
        features = model_features(df)

        self._model = IsolationForest(
            contamination=self.contamination,
            random_state=self.random_state,
            n_estimators=200,
        )
        self._model.fit(features)

        return self

    def score(self, df: pd.DataFrame) -> pd.Series:
        if self._model is None:
            raise RuntimeError("Detector belum dilatih; panggil fit() dulu.")

        raw = self._model.decision_function(model_features(df))
        skor = 1.0 / (1.0 + np.exp(8.0 * raw))

        return pd.Series(skor, index=df.index)

    @staticmethod
    def level(skor: float, threshold: float) -> str:
        if skor >= max(0.75, threshold + 0.25):
            return "Anomali"
        if skor >= threshold:
            return "Perlu Ditinjau"
        return "Normal"


def rule_based_flags(df: pd.DataFrame) -> pd.DataFrame:
    """Aturan bisnis sederhana yang menandai pola payroll tidak wajar."""
    d = enrich(df)
    flags = pd.DataFrame(index=d.index)

    hitung_ulang = (
        d["gaji_pokok"]
        + d["tunjangan"]
        + d["lembur_jam"] * d["tarif_lembur"]
        - d["potongan"]
    )
    flags["total_tidak_konsisten"] = (d["total_gaji"] - hitung_ulang).abs() > 1000

    flags["rasio_tunjangan_ekstrem"] = d["rasio_tunjangan"].abs() > 1.0
    flags["rasio_potongan_ekstrem"] = d["rasio_potongan"].abs() > 0.6
    flags["lembur_berlebihan"] = d["lembur_jam"] > 40
    flags["potongan_negatif"] = d["potongan"] < 0

    return flags


def reason_list(row_flags: pd.Series) -> list[str]:
    labels = {
        "total_tidak_konsisten": "total gaji tidak konsisten dengan komponen",
        "rasio_tunjangan_ekstrem": "rasio tunjangan terhadap gaji pokok tidak wajar",
        "rasio_potongan_ekstrem": "rasio potongan terhadap gaji pokok tidak wajar",
        "lembur_berlebihan": "jam lembur jauh di atas batas wajar",
        "potongan_negatif": "potongan bernilai negatif",
    }

    return [labels[name] for name, aktif in row_flags.items() if aktif]


def detect(
    df: pd.DataFrame,
    threshold: float = 0.5,
    detector: AnomalyDetector | None = None,
    contamination: float = DEFAULT_CONTAMINATION,
) -> pd.DataFrame:
    """Deteksi anomali per baris dan sertakan alasan yang dapat dijelaskan."""
    detector = detector or AnomalyDetector(
        contamination=contamination, threshold=threshold
    ).fit(df)

    return score_with(detector, df, threshold=threshold)


def score_with(
    detector: AnomalyDetector,
    df: pd.DataFrame,
    threshold: float = 0.5,
) -> pd.DataFrame:
    """Skor baris baru memakai detektor yang sudah dilatih pada data acuan.

    Dipakai agar payroll yang baru dihitung dapat dinilai terhadap distribusi
    data historis — bukan dinormalisasi terhadap dirinya sendiri, yang akan
    membuat setiap batch selalu tampak "normal".
    """
    skor = detector.score(df)
    flags = rule_based_flags(df)

    hasil = pd.DataFrame(
        {
            "employee_id": df["employee_id"].values,
            "periode": df["periode"].values,
            "skor_anomali": skor.round(4).values,
        },
        index=df.index,
    )

    hasil["is_anomali"] = hasil["skor_anomali"] >= threshold
    hasil["level"] = [
        AnomalyDetector.level(s, threshold) for s in hasil["skor_anomali"]
    ]
    hasil["alasan"] = [
        "; ".join(reason_list(flags.loc[idx])) or "-" for idx in df.index
    ]

    return hasil.reset_index(drop=True)

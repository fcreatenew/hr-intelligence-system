from __future__ import annotations

import logging
from typing import Callable

import numpy as np
import pandas as pd

from app.features.features import (
    ABSENSI_NUM_COLS,
    KINERJA_NUM_COLS,
    PAYROLL_NUM_COLS,
    SURVIVAL_NUM_COLS,
    buat_fitur,
    buat_fitur_absensi,
    buat_fitur_kinerja,
    buat_fitur_payroll,
    buat_fitur_survival,
)
from app.schemas.absenteeism import AbsensiInput, AbsensiResult
from app.schemas.employee import EmployeeInput
from app.schemas.performance import KinerjaInput, KinerjaResult
from app.schemas.payroll import PayrollInput, PayrollResult
from app.schemas.prediction import PredictionResult
from app.schemas.survival import SurvivalInput, SurvivalResult
from app.services.model_service import ModelService, get_model_service, model_service
from app.services.risk_service import get_risk_level


logger = logging.getLogger(
    "hr_intelligence.prediction_service"
)


class PredictionService:
    def __init__(
        self,
        model_service: ModelService,
    ):
        self._model_service = model_service

    # -- Turnover ---------------------------------------------------------
    def predict_batch(
        self,
        employees: list[EmployeeInput],
    ) -> list[PredictionResult]:
        model = self._model_service.require_loaded()

        metadata = self._model_service.metadata

        df_raw = pd.DataFrame(
            [
                employee.model_dump()
                for employee in employees
            ]
        )

        ref_date = pd.Timestamp(
            metadata["tanggal_acuan"]
        )

        X = buat_fitur(
            df_raw,
            ref_date,
        )

        probabilities = model.predict_proba(X)[:, 1]

        threshold = self._model_service.threshold

        hasil: list[PredictionResult] = []

        for employee, probability in zip(
            employees,
            probabilities,
        ):
            probability = float(probability)

            hasil.append(
                PredictionResult(
                    employee_id=employee.employee_id,
                    probabilitas_keluar=round(
                        probability,
                        4,
                    ),
                    prediksi_keluar=int(
                        probability >= threshold
                    ),
                    level_risiko=get_risk_level(
                        probability
                    ),
                )
            )

        return hasil

    def predict_one(
        self,
        employee: EmployeeInput,
    ) -> PredictionResult:
        return self.predict_batch([employee])[0]


prediction_service = PredictionService(
    model_service=model_service,
)


def _frame(employees: list) -> pd.DataFrame:
    return pd.DataFrame([e.model_dump() for e in employees])


class _UseCasePredictionService:
    """Base service prediksi untuk satu use case (selain turnover)."""

    use_case: str = ""

    def __init__(self, service: ModelService | None = None):
        self._service = service or get_model_service(self.use_case)

    @property
    def model_service(self) -> ModelService:
        return self._service


class AbsensiPredictionService(_UseCasePredictionService):
    use_case = "absensi"

    def predict_batch(self, employees: list[AbsensiInput]) -> list[AbsensiResult]:
        model = self._service.require_loaded()
        threshold = self._service.threshold

        X = buat_fitur_absensi(_frame(employees))
        probabilities = model.predict_proba(X)[:, 1]

        return [
            AbsensiResult(
                employee_id=employee.employee_id,
                probabilitas_absen_tinggi=round(float(probability), 4),
                prediksi_absen_tinggi=int(probability >= threshold),
                level_risiko=get_risk_level(float(probability)),
                threshold=round(threshold, 4),
            )
            for employee, probability in zip(employees, probabilities)
        ]

    def predict_one(self, employee: AbsensiInput) -> AbsensiResult:
        return self.predict_batch([employee])[0]


class KinerjaPredictionService(_UseCasePredictionService):
    use_case = "kinerja"

    def predict_batch(self, employees: list[KinerjaInput]) -> list[KinerjaResult]:
        model = self._service.require_loaded()
        classes = list(model.named_steps["clf"].classes_)

        X = buat_fitur_kinerja(_frame(employees))
        probabilities = model.predict_proba(X)
        predictions = model.predict(X)

        hasil: list[KinerjaResult] = []

        for employee, row, prediction in zip(employees, probabilities, predictions):
            proba = {str(c): round(float(p), 4) for c, p in zip(classes, row)}
            # Skor ordinal: 1=Rendah, 2=Sedang, 3=Tinggi.
            ordinal = float(sum(
                (i + 1) * proba[str(c)] for i, c in enumerate(classes)
            ))

            hasil.append(
                KinerjaResult(
                    employee_id=employee.employee_id,
                    kategori_prediksi=str(prediction),
                    probabilitas=proba,
                    skor_ordinal=round(ordinal, 4),
                    level=str(prediction),
                )
            )

        return hasil

    def predict_one(self, employee: KinerjaInput) -> KinerjaResult:
        return self.predict_batch([employee])[0]


class PayrollAnomalyPredictionService(_UseCasePredictionService):
    use_case = "payroll"

    def predict_batch(self, employees: list[PayrollInput]) -> list[PayrollResult]:
        artifact = self._service.require_loaded()
        threshold = self._service.threshold

        scaler = artifact["scaler"]
        model = artifact["model"]

        X = buat_fitur_payroll(_frame(employees))
        X = X[PAYROLL_NUM_COLS].fillna(0.0)

        scaled = scaler.transform(X)
        # decision_function IsolationForest: >0 inlier, <0 outlier. Petakan ke
        # skor kontinu 0-1 lewat sigmoid (0.5 = batas keputusan model, jadi
        # skor>=0.5 setara decision>=0), tanpa normalisasi per-batch.
        decision = model.decision_function(scaled)
        scores = 1.0 / (1.0 + np.exp(decision / 0.15))

        return [
            PayrollResult(
                employee_id=employee.employee_id,
                periode=employee.periode,
                skor_anomali=round(float(score), 4),
                is_anomali=bool(score >= threshold),
                level=(
                    "Anomali"
                    if score >= threshold
                    else "Perlu Ditinjau"
                    if score >= 0.5
                    else "Normal"
                ),
                threshold=round(threshold, 4),
            )
            for employee, score in zip(employees, scores)
        ]

    def predict_one(self, employee: PayrollInput) -> PayrollResult:
        return self.predict_batch([employee])[0]


class SurvivalPredictionService(_UseCasePredictionService):
    use_case = "survival"

    def predict_batch(self, employees: list[SurvivalInput]) -> list[SurvivalResult]:
        model = self._service.require_loaded()
        metadata = self._service.metadata

        X = buat_fitur_survival(_frame(employees))
        partial = model.predict_partial_hazard(X)
        risk = np.asarray(partial, dtype=float).ravel()

        baseline_median = float(metadata.get("median_survival_bulan") or 0.0)
        medians = self._median_survival(model, X)

        hasil: list[SurvivalResult] = []

        for employee, r, median in zip(employees, risk, medians):
            if r < 1.0:
                level = "Rendah"
            elif r < 2.0:
                level = "Sedang"
            else:
                level = "Tinggi"

            hasil.append(
                SurvivalResult(
                    employee_id=employee.employee_id,
                    risiko_relatif=round(float(r), 4),
                    level_risiko=level,
                    median_survival_bulan=(
                        round(float(median), 1) if median is not None else None
                    ),
                )
            )

        return hasil

    def predict_one(self, employee: SurvivalInput) -> SurvivalResult:
        return self.predict_batch([employee])[0]

    @staticmethod
    def _median_survival(model, X: pd.DataFrame) -> list[float | None]:
        try:
            table = model.predict_survival_function(X)
            medians: list[float | None] = []

            for column in table.columns:
                curve = table[column]
                below = curve[curve <= 0.5]
                medians.append(
                    float(below.index[0]) if len(below) else None
                )

            return medians
        except Exception:  # noqa: BLE001 - median bersifat opsional
            logger.warning(
                "Gagal menghitung median survival; dikembalikan None.",
                exc_info=True,
            )
            return [None] * len(X)


SERVICE_FACTORIES: dict[str, Callable[[], object]] = {
    "absensi": AbsensiPredictionService,
    "kinerja": KinerjaPredictionService,
    "payroll": PayrollAnomalyPredictionService,
    "survival": SurvivalPredictionService,
}


_SERVICE_CACHE: dict[str, object] = {}


def get_prediction_service(use_case: str):
    if use_case == "turnover":
        return prediction_service

    if use_case not in SERVICE_FACTORIES:
        raise KeyError(f"Use case tidak dikenal: {use_case}")

    if use_case not in _SERVICE_CACHE:
        _SERVICE_CACHE[use_case] = SERVICE_FACTORIES[use_case]()

    return _SERVICE_CACHE[use_case]

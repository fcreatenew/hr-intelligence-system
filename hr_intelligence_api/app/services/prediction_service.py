from __future__ import annotations

import logging

import pandas as pd

from app.features.features import buat_fitur
from app.schemas.employee import EmployeeInput
from app.schemas.prediction import PredictionResult
from app.services.model_service import ModelService, model_service
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
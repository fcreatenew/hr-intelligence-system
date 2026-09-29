from fastapi import APIRouter, Depends, status
from fastapi.responses import JSONResponse

from app.core.auth import rate_limiter
from app.core.config import MAX_BATCH_SIZE
from app.schemas.prediction import (
    BatchPredictRequest,
    BatchPredictResponse,
    EmployeeInput,
    PredictionResult,
)
from app.services.model_service import model_service
from app.services.prediction_service import prediction_service


router = APIRouter(
    prefix="",
    tags=["prediction"],
)


@router.post(
    "/predict",
    response_model=PredictionResult,
    dependencies=[
        Depends(rate_limiter),
    ],
)
async def predict(employee: EmployeeInput):
    return prediction_service.predict_one(employee)


@router.post(
    "/predict/batch",
    response_model=BatchPredictResponse,
    dependencies=[
        Depends(rate_limiter),
    ],
)
async def predict_batch(
    payload: BatchPredictRequest,
):
    if len(payload.employees) > MAX_BATCH_SIZE:
        return JSONResponse(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            content={
                "detail": (
                    f"Maksimum {MAX_BATCH_SIZE} "
                    "karyawan per request."
                )
            },
        )

    hasil = prediction_service.predict_batch(
        payload.employees
    )

    return BatchPredictResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=model_service.metadata.get(
            "model",
            "unknown",
        ),
        threshold=model_service.threshold,
    )
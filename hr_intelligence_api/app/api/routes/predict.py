from fastapi import APIRouter, Depends, status
from fastapi.responses import JSONResponse

from app.core.auth import rate_limiter
from app.core.config import MAX_BATCH_SIZE
from app.schemas.absenteeism import (
    AbsensiBatchRequest,
    AbsensiBatchResponse,
    AbsensiInput,
    AbsensiResult,
)
from app.schemas.performance import (
    KinerjaBatchRequest,
    KinerjaBatchResponse,
    KinerjaInput,
    KinerjaResult,
)
from app.schemas.payroll import (
    PayrollBatchRequest,
    PayrollBatchResponse,
    PayrollInput,
    PayrollResult,
)
from app.schemas.prediction import (
    BatchPredictRequest,
    BatchPredictResponse,
    EmployeeInput,
    PredictionResult,
)
from app.schemas.survival import (
    SurvivalBatchRequest,
    SurvivalBatchResponse,
    SurvivalInput,
    SurvivalResult,
)
from app.services.model_service import model_service
from app.services.prediction_service import (
    prediction_service,
    get_prediction_service,
)


router = APIRouter(
    prefix="",
    tags=["prediction"],
)


def _too_many(count: int) -> JSONResponse | None:
    if count > MAX_BATCH_SIZE:
        return JSONResponse(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            content={
                "detail": (
                    f"Maksimum {MAX_BATCH_SIZE} "
                    "karyawan per request."
                )
            },
        )

    return None


# ---------------------------------------------------------------------------
# Turnover (default)
# ---------------------------------------------------------------------------
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
    if (error := _too_many(len(payload.employees))) is not None:
        return error

    hasil = prediction_service.predict_batch(
        payload.employees
    )

    return BatchPredictResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=model_service.model_version,
        threshold=model_service.threshold,
    )


# ---------------------------------------------------------------------------
# Absensi
# ---------------------------------------------------------------------------
@router.post(
    "/predict/absensi",
    response_model=AbsensiResult,
    dependencies=[Depends(rate_limiter)],
)
async def predict_absensi(employee: AbsensiInput):
    return get_prediction_service("absensi").predict_one(employee)


@router.post(
    "/predict/absensi/batch",
    response_model=AbsensiBatchResponse,
    dependencies=[Depends(rate_limiter)],
)
async def predict_absensi_batch(payload: AbsensiBatchRequest):
    if (error := _too_many(len(payload.employees))) is not None:
        return error

    service = get_prediction_service("absensi")
    hasil = service.predict_batch(payload.employees)

    return AbsensiBatchResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=service.model_service.model_version,
        threshold=service.model_service.threshold,
    )


# ---------------------------------------------------------------------------
# Kinerja
# ---------------------------------------------------------------------------
@router.post(
    "/predict/kinerja",
    response_model=KinerjaResult,
    dependencies=[Depends(rate_limiter)],
)
async def predict_kinerja(employee: KinerjaInput):
    return get_prediction_service("kinerja").predict_one(employee)


@router.post(
    "/predict/kinerja/batch",
    response_model=KinerjaBatchResponse,
    dependencies=[Depends(rate_limiter)],
)
async def predict_kinerja_batch(payload: KinerjaBatchRequest):
    if (error := _too_many(len(payload.employees))) is not None:
        return error

    service = get_prediction_service("kinerja")
    hasil = service.predict_batch(payload.employees)

    return KinerjaBatchResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=service.model_service.model_version,
    )


# ---------------------------------------------------------------------------
# Anomali payroll
# ---------------------------------------------------------------------------
@router.post(
    "/predict/payroll",
    response_model=PayrollResult,
    dependencies=[Depends(rate_limiter)],
)
async def predict_payroll(employee: PayrollInput):
    return get_prediction_service("payroll").predict_one(employee)


@router.post(
    "/predict/payroll/batch",
    response_model=PayrollBatchResponse,
    dependencies=[Depends(rate_limiter)],
)
async def predict_payroll_batch(payload: PayrollBatchRequest):
    if (error := _too_many(len(payload.employees))) is not None:
        return error

    service = get_prediction_service("payroll")
    hasil = service.predict_batch(payload.employees)

    return PayrollBatchResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=service.model_service.model_version,
        threshold=service.model_service.threshold,
    )


# ---------------------------------------------------------------------------
# Survival attrition
# ---------------------------------------------------------------------------
@router.post(
    "/predict/survival",
    response_model=SurvivalResult,
    dependencies=[Depends(rate_limiter)],
)
async def predict_survival(employee: SurvivalInput):
    return get_prediction_service("survival").predict_one(employee)


@router.post(
    "/predict/survival/batch",
    response_model=SurvivalBatchResponse,
    dependencies=[Depends(rate_limiter)],
)
async def predict_survival_batch(payload: SurvivalBatchRequest):
    if (error := _too_many(len(payload.employees))) is not None:
        return error

    service = get_prediction_service("survival")
    hasil = service.predict_batch(payload.employees)

    return SurvivalBatchResponse(
        hasil=hasil,
        jumlah_diproses=len(hasil),
        model_version=service.model_service.model_version,
        baseline_median_survival_bulan=float(
            service.model_service.metadata.get("median_survival_bulan") or 0.0
        ),
    )

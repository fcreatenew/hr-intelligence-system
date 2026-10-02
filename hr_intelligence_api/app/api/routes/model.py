from fastapi import APIRouter, Depends

from app.core.auth import verify_api_key
from app.core.config import USE_CASE_REGISTRY
from app.schemas.model import (
    ModelInfoResponse,
    UseCaseInfo,
    UseCaseListResponse,
)
from app.services.model_service import (
    get_model_service,
    model_services,
)


router = APIRouter(
    prefix="/model",
    tags=["monitoring"],
)


def _info(use_case: str) -> ModelInfoResponse:
    service = get_model_service(use_case)
    meta = service.metadata

    return ModelInfoResponse(
        use_case=use_case,
        versi_model=service.model_version,
        label=service.label,
        model=meta.get("model", "unknown"),
        dibuat_pada=service.trained_at,
        threshold=meta.get("threshold"),
        fitur_numerik=meta.get("fitur_numerik", []),
        fitur_kategorik=(
            meta.get("fitur_kategorik")
            or meta.get("fitur_kategorikal", [])
        ),
        metrik=(
            meta.get("metrik_holdout_test")
            or meta.get("metrik_evaluasi")
            or {}
        ),
        catatan=meta.get("catatan", ""),
    )


@router.get(
    "",
    response_model=UseCaseListResponse,
    dependencies=[Depends(verify_api_key)],
)
@router.get(
    "/",
    response_model=UseCaseListResponse,
    dependencies=[Depends(verify_api_key)],
    include_in_schema=False,
)
async def list_use_cases():
    services = model_services()

    return UseCaseListResponse(
        use_cases=[
            UseCaseInfo(
                use_case=use_case,
                label=service.label,
                versi_model=service.model_version,
                model=service.metadata.get("model", "unknown"),
                threshold=service.metadata.get("threshold"),
                predict_path=USE_CASE_REGISTRY[use_case]["predict_path"],
                loaded=service.is_loaded,
            )
            for use_case, service in services.items()
        ]
    )


@router.get(
    "/info",
    response_model=ModelInfoResponse,
    dependencies=[Depends(verify_api_key)],
)
async def model_info():
    """Info model turnover (kompatibel dengan endpoint lama)."""
    return _info("turnover")


@router.get(
    "/{use_case}/info",
    response_model=ModelInfoResponse,
    dependencies=[Depends(verify_api_key)],
)
async def model_info_use_case(use_case: str):
    return _info(use_case)

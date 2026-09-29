from fastapi import APIRouter, Depends

from app.core.auth import verify_api_key
from app.schemas.model import ModelInfoResponse
from app.services.model_service import model_service


router = APIRouter(
    prefix="/model",
    tags=["monitoring"],
)


@router.get(
    "/info",
    response_model=ModelInfoResponse,
    dependencies=[Depends(verify_api_key)],
)
async def model_info():
    meta = model_service.metadata

    return ModelInfoResponse(
        model=meta.get("model", "unknown"),
        dibuat_pada=meta.get("dibuat_pada", "unknown"),
        threshold=meta.get("threshold", 0.5),
        fitur_numerik=meta.get("fitur_numerik", []),
        fitur_kategorik=meta.get("fitur_kategorik", []),
        metrik_holdout_test=meta.get(
            "metrik_holdout_test",
            {},
        ),
        catatan=meta.get("catatan", ""),
    )
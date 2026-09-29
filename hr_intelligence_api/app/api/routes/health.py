from fastapi import APIRouter

from app.schemas.health import HealthResponse
from app.services.model_service import model_service


router = APIRouter(
    tags=["monitoring"],
)


@router.get(
    "/health",
    response_model=HealthResponse,
)
async def health():
    if model_service.is_loaded:
        return HealthResponse(
            status="ok",
            model_loaded=True,
            model_name=model_service.metadata.get("model"),
            model_trained_at=model_service.metadata.get(
                "dibuat_pada"
            ),
        )

    return HealthResponse(
        status="degraded",
        model_loaded=False,
    )
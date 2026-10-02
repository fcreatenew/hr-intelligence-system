from fastapi import APIRouter

from app.schemas.health import HealthResponse
from app.services.model_service import model_services


router = APIRouter(
    tags=["monitoring"],
)


@router.get(
    "/health",
    response_model=HealthResponse,
)
async def health():
    services = model_services()

    loaded = {
        use_case: service.is_loaded
        for use_case, service in services.items()
    }

    jumlah_siap = sum(1 for ok in loaded.values() if ok)
    semua_siap = jumlah_siap == len(loaded)

    turnover = services.get("turnover")

    return HealthResponse(
        status="ok" if semua_siap else "degraded",
        models_loaded=jumlah_siap,
        models_total=len(loaded),
        model_loaded=bool(turnover and turnover.is_loaded),
        model_name=(
            turnover.metadata.get("model")
            if turnover and turnover.is_loaded
            else None
        ),
        model_trained_at=(
            turnover.trained_at if turnover and turnover.is_loaded else None
        ),
        models=loaded,
    )

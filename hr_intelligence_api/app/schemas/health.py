from typing import Optional

from pydantic import BaseModel


class HealthResponse(BaseModel):
    status: str
    models_loaded: int
    models_total: int
    model_loaded: bool
    model_name: Optional[str] = None
    model_trained_at: Optional[str] = None
    models: dict[str, bool] = {}

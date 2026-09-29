from typing import Literal, Optional

from pydantic import BaseModel


class HealthResponse(BaseModel):
    status: Literal[
        "ok",
        "degraded",
    ]

    model_loaded: bool

    model_name: Optional[str] = None

    model_trained_at: Optional[str] = None
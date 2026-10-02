from __future__ import annotations

from fastapi import FastAPI

from src.api.routes import router
from src.config import APP_DESCRIPTION, APP_TITLE, APP_VERSION


app = FastAPI(
    title=APP_TITLE,
    description=APP_DESCRIPTION,
    version=APP_VERSION,
)

app.include_router(router)

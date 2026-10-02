from __future__ import annotations

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI

from app.api.router import api_router
from app.core.config import APP_DESCRIPTION, APP_TITLE, APP_VERSION
from app.core.exceptions import register_exception_handlers
from app.core.logging import setup_logging
from app.core.middleware import register_middlewares
from app.services.model_service import load_all_models

logger = logging.getLogger("hr_intelligence.api")


@asynccontextmanager
async def lifespan(app: FastAPI):
    status = load_all_models()

    for use_case, result in status.items():
        if result == "ok":
            logger.info("Model '%s' siap.", use_case)
        else:
            logger.warning("Model '%s' tidak siap: %s", use_case, result)

    yield

    logger.info("Aplikasi dimatikan.")


setup_logging()

app = FastAPI(
    title=APP_TITLE,
    description=APP_DESCRIPTION,
    version=APP_VERSION,
    lifespan=lifespan,
)

register_middlewares(app)
register_exception_handlers(app)
app.include_router(api_router)
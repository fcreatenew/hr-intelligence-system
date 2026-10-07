import logging
import time
import uuid

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware

from app.core.config import CORS_ALLOW_ORIGINS


logger = logging.getLogger("hr_intelligence.api")


async def log_requests(request: Request, call_next):
    request_id = str(uuid.uuid4())[:8]
    start = time.perf_counter()

    response = await call_next(request)

    duration_ms = (time.perf_counter() - start) * 1000

    logger.info(
        "[%s] %s %s -> %s (%.1f ms)",
        request_id,
        request.method,
        request.url.path,
        response.status_code,
        duration_ms,
    )

    response.headers["X-Request-ID"] = request_id

    return response


def register_middlewares(app: FastAPI):
    app.middleware("http")(log_requests)

    # CORS hanya dipasang bila origin diizinkan secara eksplisit.
    if CORS_ALLOW_ORIGINS:
        app.add_middleware(
            CORSMiddleware,
            allow_origins=CORS_ALLOW_ORIGINS,
            allow_methods=["GET", "POST"],
            allow_headers=["X-API-Key", "Content-Type"],
        )
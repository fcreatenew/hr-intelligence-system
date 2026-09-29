from fastapi import FastAPI, Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from app.services.model_service import ModelNotLoadedError


async def validation_exception_handler(
    request: Request,
    exc: RequestValidationError,
):
    return JSONResponse(
        status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
        content={
            "detail": "Input tidak valid.",
            "errors": exc.errors(),
        },
    )


async def model_not_loaded_handler(
    request: Request,
    exc: ModelNotLoadedError,
):
    return JSONResponse(
        status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
        content={
            "detail": str(exc),
        },
    )


def register_exception_handlers(app: FastAPI):
    app.add_exception_handler(
        RequestValidationError,
        validation_exception_handler,
    )

    app.add_exception_handler(
        ModelNotLoadedError,
        model_not_loaded_handler,
    )
from fastapi import FastAPI, Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from app.services.model_service import ModelNotLoadedError


def _jsonable_errors(exc: RequestValidationError) -> list[dict]:
    """Bersihkan errors() agar selalu JSON-serializable.

    Pydantic menyertakan objek asli (mis. ValueError dari field_validator) di
    dalam kunci "ctx"; objek itu tidak bisa di-serialize dan akan membuat
    handler ini sendiri gagal (500). Kita ubah nilai non-primitif jadi string.
    """
    hasil = []
    for err in exc.errors():
        err = dict(err)
        ctx = err.get("ctx")
        if isinstance(ctx, dict):
            err["ctx"] = {k: str(v) for k, v in ctx.items()}
        if "input" in err and not isinstance(
            err["input"], (str, int, float, bool, type(None))
        ):
            err["input"] = str(err["input"])
        hasil.append(err)
    return hasil


async def validation_exception_handler(
    request: Request,
    exc: RequestValidationError,
):
    return JSONResponse(
        status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
        content={
            "detail": "Input tidak valid.",
            "errors": _jsonable_errors(exc),
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
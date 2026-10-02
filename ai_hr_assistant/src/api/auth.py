"""Autentikasi & rate limit untuk AI HR Assistant API.

Pola ini mengikuti `hr_intelligence_api/app/core/auth.py` agar perilaku kedua
API konsisten: API key statis lewat header `X-API-Key`, plus rate limit per
key per menit.
"""

from __future__ import annotations

import time
from collections import defaultdict, deque

from fastapi import Depends, Header, HTTPException, status

from src.config import API_KEY, RATE_LIMIT_PER_MINUTE


_request_log: dict[str, deque] = defaultdict(deque)


def verify_api_key(
    x_api_key: str = Header(
        ...,
        alias="X-API-Key",
        description="API key untuk autentikasi",
    ),
) -> str:
    if not API_KEY:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="API key server belum dikonfigurasi.",
        )

    if x_api_key != API_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="API key tidak valid.",
        )

    return x_api_key


def rate_limiter(
    api_key: str = Depends(verify_api_key),
) -> None:
    now = time.monotonic()
    window = 60.0

    log = _request_log[api_key]

    while log and now - log[0] > window:
        log.popleft()

    if len(log) >= RATE_LIMIT_PER_MINUTE:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail=(
                f"Rate limit terlampaui "
                f"({RATE_LIMIT_PER_MINUTE} request/menit). "
                "Coba lagi sebentar lagi."
            ),
        )

    log.append(now)

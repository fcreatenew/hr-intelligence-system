from __future__ import annotations

import logging
import time
from typing import Any

import httpx

from src.config import (
    HR_INTELLIGENCE_API_KEY,
    HR_INTELLIGENCE_BASE_URL,
    HR_INTELLIGENCE_RETRY_TIMES,
    HR_INTELLIGENCE_TIMEOUT,
)


logger = logging.getLogger("ai_hr_assistant.hr_intelligence")


# Peta use case -> endpoint. Turnover memakai /predict (tanpa prefix) demi
# kompatibilitas; sisanya /predict/<use_case>.
PREDICT_PATHS: dict[str, str] = {
    "turnover": "/predict",
    "absensi": "/predict/absensi",
    "kinerja": "/predict/kinerja",
    "payroll": "/predict/payroll",
    "survival": "/predict/survival",
}


class HrIntelligenceError(RuntimeError):
    """Kesalahan umum saat memanggil HR Intelligence API."""


class HrIntelligenceAuthError(HrIntelligenceError):
    ...


class HrIntelligenceUnavailableError(HrIntelligenceError):
    ...


class HrIntelligenceValidationError(HrIntelligenceError):
    def __init__(self, message: str, errors: list | None = None):
        super().__init__(message)
        self.errors = errors or []


class HrIntelligenceClient:
    """Klien tipis untuk HR Intelligence API (FastAPI).

    Dipakai oleh AI tools agar assistant dapat memanggil seluruh model ML yang
    sudah ada: turnover, absensi, kinerja, payroll, dan survival.
    """

    def __init__(
        self,
        base_url: str = HR_INTELLIGENCE_BASE_URL,
        api_key: str = HR_INTELLIGENCE_API_KEY,
        timeout: float = HR_INTELLIGENCE_TIMEOUT,
        retry_times: int = HR_INTELLIGENCE_RETRY_TIMES,
        client: httpx.Client | None = None,
    ):
        self._base_url = base_url.rstrip("/")
        self._api_key = api_key
        self._timeout = timeout
        self._retry_times = max(1, retry_times)
        self._client = client

    # -- HTTP ---------------------------------------------------------------
    def _request(
        self,
        method: str,
        path: str,
        payload: dict | None = None,
        requires_auth: bool = True,
    ) -> dict[str, Any]:
        headers = {"Accept": "application/json"}

        if requires_auth:
            if not self._api_key:
                raise HrIntelligenceAuthError(
                    "HR_INTELLIGENCE_API_KEY belum diset."
                )
            headers["X-API-Key"] = self._api_key

        client = self._client or httpx.Client(
            base_url=self._base_url,
            timeout=self._timeout,
        )
        own_client = self._client is None
        last_error: Exception | None = None

        try:
            for attempt in range(1, self._retry_times + 1):
                try:
                    response = client.request(
                        method,
                        path,
                        json=payload,
                        headers=headers,
                    )
                except httpx.RequestError as exc:
                    last_error = exc
                    logger.warning(
                        "Koneksi ke HR Intelligence API gagal (percobaan %s/%s): %s",
                        attempt,
                        self._retry_times,
                        exc,
                    )
                    if attempt < self._retry_times:
                        time.sleep(0.2 * attempt)
                    continue

                if response.status_code >= 500 and attempt < self._retry_times:
                    logger.warning(
                        "HR Intelligence API mengembalikan %s, coba lagi.",
                        response.status_code,
                    )
                    time.sleep(0.2 * attempt)
                    continue

                return self._handle(response, path)

            raise HrIntelligenceUnavailableError(
                f"HR Intelligence API tidak bisa dihubungi setelah "
                f"{self._retry_times}x percobaan ({path})."
            ) from last_error
        finally:
            if own_client:
                client.close()

    @staticmethod
    def _handle(response: httpx.Response, path: str) -> dict[str, Any]:
        if response.status_code < 400:
            try:
                return response.json()
            except ValueError:
                return {}

        try:
            body = response.json()
        except ValueError:
            body = {}

        detail = body.get("detail")
        message = (
            detail
            if isinstance(detail, str)
            else f"HR Intelligence API mengembalikan status {response.status_code} untuk {path}."
        )

        if response.status_code == 401:
            raise HrIntelligenceAuthError(message)
        if response.status_code == 422:
            raise HrIntelligenceValidationError(message, body.get("errors"))
        if response.status_code in (404, 413):
            raise HrIntelligenceError(message)

        raise HrIntelligenceUnavailableError(message)

    # -- Endpoint publik ----------------------------------------------------
    def health(self) -> dict[str, Any]:
        return self._request("GET", "/health", requires_auth=False)

    def list_use_cases(self) -> dict[str, Any]:
        return self._request("GET", "/model")

    def model_info(self, use_case: str = "turnover") -> dict[str, Any]:
        path = (
            "/model/info"
            if use_case == "turnover"
            else f"/model/{use_case}/info"
        )
        return self._request("GET", path)

    def predict(self, use_case: str, payload: dict) -> dict[str, Any]:
        if use_case not in PREDICT_PATHS:
            raise HrIntelligenceError(f"Use case tidak dikenal: '{use_case}'.")

        return self._request("POST", PREDICT_PATHS[use_case], payload)

    def predict_batch(self, use_case: str, payloads: list[dict]) -> dict[str, Any]:
        if use_case not in PREDICT_PATHS:
            raise HrIntelligenceError(f"Use case tidak dikenal: '{use_case}'.")

        return self._request(
            "POST",
            f"{PREDICT_PATHS[use_case]}/batch",
            {"employees": payloads},
        )

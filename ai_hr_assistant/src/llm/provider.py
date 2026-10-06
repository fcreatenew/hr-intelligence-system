from __future__ import annotations

from abc import ABC, abstractmethod

from src.config import (
    HF_TOKEN,  # Pastikan variabel ini sudah ada di src/config.py Anda
    LLM_MODEL,
    LLM_PROVIDER,
    LLM_TEMPERATURE,
    OPENAI_API_KEY,
    OPENAI_BASE_URL,
)


class LLMProvider(ABC):
    """Antarmuka provider LLM.

    Agent bergantung pada antarmuka ini, bukan pada SDK tertentu, sehingga
    provider (mock/OpenAI/hf/self-hosted) bisa ditukar tanpa mengubah alur.
    """

    name: str = "base"

    @abstractmethod
    def complete(self, system_prompt: str, user_prompt: str) -> str:
        ...


class MockLLM(LLMProvider):
    """Provider tanpa jaringan."""

    name = "mock"

    def complete(self, system_prompt: str, user_prompt: str) -> str:
        return ""


class OpenAILLM(LLMProvider):
    name = "openai"

    def __init__(self, model: str = LLM_MODEL, temperature: float = LLM_TEMPERATURE):
        try:
            from openai import OpenAI  # type: ignore
        except ImportError as exc:
            raise RuntimeError(
                "LLM_PROVIDER=openai membutuhkan paket 'openai'. "
                "Jalankan: pip install openai"
            ) from exc

        if not OPENAI_API_KEY:
            raise RuntimeError("OPENAI_API_KEY belum diset.")

        kwargs = {"api_key": OPENAI_API_KEY}
        if OPENAI_BASE_URL:
            kwargs["base_url"] = OPENAI_BASE_URL

        self._client = OpenAI(**kwargs)
        self._model = model
        self._temperature = temperature

    def complete(self, system_prompt: str, user_prompt: str) -> str:
        response = self._client.chat.completions.create(
            model=self._model,
            temperature=self._temperature,
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt},
            ],
        )

        return (response.choices[0].message.content or "").strip()


class HuggingFaceLLM(LLMProvider):
    name = "hf"

    def __init__(self, model: str = LLM_MODEL, temperature: float = LLM_TEMPERATURE):
        try:
            from huggingface_hub import InferenceClient  # type: ignore
        except ImportError as exc:
            raise RuntimeError(
                "LLM_PROVIDER=hf membutuhkan paket 'huggingface_hub'. "
                "Jalankan: pip install huggingface_hub"
            ) from exc

        if not HF_TOKEN:
            raise RuntimeError("API key server belum dikonfigurasi (HF_TOKEN kosong).")

        self._client = InferenceClient(api_key=HF_TOKEN)
        self._model = model
        self._temperature = temperature

    def complete(self, system_prompt: str, user_prompt: str) -> str:
        response = self._client.chat.completions.create(
            model=self._model,
            temperature=self._temperature,
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt},
            ],
        )

        return (response.choices[0].message.content or "").strip()


def get_llm(provider: str | None = None) -> LLMProvider:
    provider = (provider or LLM_PROVIDER).lower()

    if provider in {"mock", "none", "offline"}:
        return MockLLM()

    if provider == "openai":
        return OpenAILLM()

    if provider in {"hf", "huggingface"}:
        return HuggingFaceLLM()

    raise ValueError(
        f"Provider LLM tidak dikenal: '{provider}'. Gunakan 'mock', 'openai', atau 'hf'."
    )
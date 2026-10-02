from __future__ import annotations

from pathlib import Path

from src.config import PROMPTS_DIR


_CACHE: dict[str, str] = {}


def load_prompt(relative_path: str) -> str:
    """Muat prompt dari prompts/ berdasarkan path relatif (mis. 'system/base.md')."""
    if relative_path in _CACHE:
        return _CACHE[relative_path]

    path = PROMPTS_DIR / relative_path

    if not path.exists():
        raise FileNotFoundError(f"Prompt tidak ditemukan: {path}")

    text = path.read_text(encoding="utf-8")
    _CACHE[relative_path] = text

    return text


def clear_cache() -> None:
    _CACHE.clear()


def list_prompts() -> list[str]:
    if not PROMPTS_DIR.exists():
        return []

    return sorted(
        str(path.relative_to(PROMPTS_DIR))
        for path in PROMPTS_DIR.rglob("*.md")
    )

from __future__ import annotations

import os
from pathlib import Path

from dotenv import load_dotenv


BASE_DIR = Path(__file__).resolve().parents[1]

load_dotenv(BASE_DIR / ".env")


def _int(name: str, default: int) -> int:
    try:
        return int(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


def _float(name: str, default: float) -> float:
    try:
        return float(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


# --- Knowledge base ---
KNOWLEDGE_DIR = BASE_DIR / "knowledge"
PROMPTS_DIR = BASE_DIR / "prompts"

# --- HR Intelligence API (sumber prediksi model ML) ---
HR_INTELLIGENCE_BASE_URL = os.getenv(
    "HR_INTELLIGENCE_BASE_URL",
    "http://127.0.0.1:8000",
)
HR_INTELLIGENCE_API_KEY = os.getenv("HR_INTELLIGENCE_API_KEY", "")
HR_INTELLIGENCE_TIMEOUT = _float("HR_INTELLIGENCE_TIMEOUT", 10.0)
HR_INTELLIGENCE_RETRY_TIMES = _int("HR_INTELLIGENCE_RETRY_TIMES", 2)

# --- Auth API assistant ---
# Kosong berarti API menolak semua request (fail closed). Set
# ASSISTANT_API_KEY di .env sebelum menjalankan server.
API_KEY = os.getenv("ASSISTANT_API_KEY", "")
RATE_LIMIT_PER_MINUTE = _int("ASSISTANT_RATE_LIMIT_PER_MINUTE", 60)

# --- LLM ---
LLM_PROVIDER = os.getenv("LLM_PROVIDER", "mock").strip().lower()
LLM_MODEL = os.getenv("LLM_MODEL", "gpt-4o-mini")
LLM_TEMPERATURE = _float("LLM_TEMPERATURE", 0.2)
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
OPENAI_BASE_URL = os.getenv("OPENAI_BASE_URL", "")

# --- Embeddings ---
# lsa     : TF-IDF + SVD (semantik, tanpa model eksternal, deterministik) — default.
# hashing : feature hashing leksikal, tanpa fit, paling ringan.
# neural  : sentence-transformers (butuh paket & unduhan model).
EMBEDDING_BACKEND = os.getenv("EMBEDDING_BACKEND", "lsa").strip().lower()
EMBEDDING_DIM = _int("EMBEDDING_DIM", 256)
EMBEDDING_MODEL = os.getenv(
    "EMBEDDING_MODEL", "paraphrase-multilingual-MiniLM-L12-v2"
)

# --- Chunking ---
CHUNK_MAX_CHARS = _int("CHUNK_MAX_CHARS", 900)
CHUNK_OVERLAP_CHARS = _int("CHUNK_OVERLAP_CHARS", 150)
CHUNK_MIN_CHARS = _int("CHUNK_MIN_CHARS", 40)

# --- Retrieval ---
# fusion: rrf (default) | linear | lexical | semantic
RAG_FUSION = os.getenv("RAG_FUSION", "rrf").strip().lower()
RAG_LEXICAL_WEIGHT = _float("RAG_LEXICAL_WEIGHT", 0.7)
RAG_MMR = os.getenv("RAG_MMR", "true").strip().lower() in {"1", "true", "yes", "on"}
RAG_MMR_LAMBDA = _float("RAG_MMR_LAMBDA", 0.7)
RAG_TOP_K = _int("RAG_TOP_K", 4)
RAG_MIN_SCORE = _float("RAG_MIN_SCORE", 0.05)


APP_TITLE = "AI HR Assistant API"
APP_VERSION = "0.1.0"
APP_DESCRIPTION = (
    "Asisten HR berbasis LLM + RAG yang terhubung ke model Machine Learning "
    "HR Intelligence System (turnover, absensi, kinerja, payroll, survival)."
)

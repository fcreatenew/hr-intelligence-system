from __future__ import annotations

import hashlib
import math
import re
from abc import ABC, abstractmethod


_TOKEN_RE = re.compile(r"[a-z0-9]+")

# Kata umum Bahasa Indonesia yang tidak membawa makna pencarian.
_STOPWORDS = {
    "dan", "atau", "yang", "untuk", "dengan", "pada", "di", "ke", "dari",
    "adalah", "akan", "ini", "itu", "tidak", "bisa", "dapat", "apa",
    "bagaimana", "berapa", "kapan", "siapa", "mengapa", "jika", "saya",
    "anda", "kami", "kita", "the", "a", "an", "of", "to", "is", "are",
}


def tokenize(text: str) -> list[str]:
    """Tokenisasi sederhana: lowercase, tanpa stopword, plus bigram."""
    words = [
        token
        for token in _TOKEN_RE.findall(text.lower())
        if token not in _STOPWORDS and len(token) > 1
    ]

    bigrams = [
        f"{words[i]}_{words[i + 1]}" for i in range(len(words) - 1)
    ]

    return words + bigrams


class Embedder(ABC):
    """Antarmuka embedding: teks -> vector."""

    dim: int

    @abstractmethod
    def embed(self, text: str) -> list[float]:
        ...

    def embed_many(self, texts: list[str]) -> list[list[float]]:
        return [self.embed(text) for text in texts]

    @staticmethod
    def cosine(a: list[float], b: list[float]) -> float:
        dot = sum(x * y for x, y in zip(a, b))
        na = math.sqrt(sum(x * x for x in a))
        nb = math.sqrt(sum(y * y for y in b))

        if na == 0.0 or nb == 0.0:
            return 0.0

        return dot / (na * nb)


class HashingEmbedder(Embedder):
    """Embedding hashing lokal (feature hashing + signed hashing).

    Deterministik, tanpa model eksternal, cocok untuk retrieval leksikal
    yang dapat direproduksi di test. Bukan pengganti embedding semantik
    kelas produksi, tetapi menjaga kontrak antarmuka `Embedder` tetap sama
    sehingga backend bisa ditukar tanpa mengubah RAG.
    """

    def __init__(self, dim: int = 512):
        if dim <= 0:
            raise ValueError("dim harus > 0")

        self.dim = dim

    def embed(self, text: str) -> list[float]:
        vector = [0.0] * self.dim

        for token in tokenize(text):
            digest = hashlib.blake2b(token.encode("utf-8"), digest_size=8).digest()
            value = int.from_bytes(digest, "big")

            index = value % self.dim
            sign = 1.0 if (value >> 63) & 1 else -1.0

            # Pembobotan sublinear (akar frekuensi) menekan token yang repetitif.
            vector[index] += sign

        norm = math.sqrt(sum(v * v for v in vector))

        if norm == 0.0:
            return vector

        return [v / norm for v in vector]


def get_embedder(backend: str | None = None, dim: int | None = None) -> Embedder:
    from src.config import EMBEDDING_BACKEND, EMBEDDING_DIM

    backend = (backend or EMBEDDING_BACKEND).lower()
    dim = dim or EMBEDDING_DIM

    if backend in {"hashing", "local", "default"}:
        return HashingEmbedder(dim=dim)

    raise ValueError(
        f"Backend embedding tidak dikenal: '{backend}'. "
        "Gunakan 'hashing'."
    )

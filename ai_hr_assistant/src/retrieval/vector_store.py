from __future__ import annotations

from dataclasses import dataclass

from src.embeddings.embedder import Embedder
from src.rag.loader import Chunk


@dataclass
class ScoredChunk:
    chunk: Chunk
    score: float
    lexical: float = 0.0
    semantic: float = 0.0
    matched_terms: list[str] | None = None


class VectorStore:
    """Vector store in-memory untuk chunk knowledge base.

    Menyimpan embedding chunk dan menghitung cosine similarity terhadap query.
    Cukup untuk knowledge base berukuran kecil–sedang; antarmuka ini bisa
    diganti vector database (pgvector, FAISS) tanpa mengubah retriever.
    """

    def __init__(self, embedder: Embedder):
        self._embedder = embedder
        self._chunks: list[Chunk] = []
        self._vectors: list[list[float]] = []

    def add(self, chunks: list[Chunk]) -> None:
        for chunk in chunks:
            self._chunks.append(chunk)
            self._vectors.append(
                self._embedder.embed(f"{chunk.title} {chunk.heading} {chunk.text}")
            )

    def __len__(self) -> int:
        return len(self._chunks)

    @property
    def chunks(self) -> list[Chunk]:
        return list(self._chunks)

    @property
    def vectors(self) -> list[list[float]]:
        """Vektor chunk sejajar dengan `chunks` (dipakai MMR untuk diversitas)."""
        return self._vectors

    def semantic_scores(self, query: str) -> list[float]:
        query_vector = self._embedder.embed(query)

        return [
            max(0.0, self._embedder.cosine(query_vector, vector))
            for vector in self._vectors
        ]

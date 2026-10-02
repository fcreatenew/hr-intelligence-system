from __future__ import annotations

from src.config import KNOWLEDGE_DIR, RAG_MIN_SCORE, RAG_TOP_K
from src.embeddings.embedder import Embedder, get_embedder
from src.rag.loader import Chunk, Document, load_knowledge_base
from src.retrieval.retriever import HybridRetriever
from src.retrieval.vector_store import ScoredChunk


class RagService:
    """Retrieval-Augmented Generation: knowledge base + retriever hybrid."""

    def __init__(self, embedder: Embedder | None = None, knowledge_dir=None):
        self._embedder = embedder or get_embedder()
        self._knowledge_dir = knowledge_dir or KNOWLEDGE_DIR
        self._documents: list[Document] = []
        self._chunks: list[Chunk] = []
        self._retriever = HybridRetriever(self._embedder, [])
        self.reload()

    def reload(self) -> None:
        self._documents, self._chunks = load_knowledge_base(self._knowledge_dir)
        self._retriever = HybridRetriever(self._embedder, self._chunks)

    @property
    def documents(self) -> list[Document]:
        return list(self._documents)

    @property
    def chunks(self) -> list[Chunk]:
        return list(self._chunks)

    def search(
        self,
        query: str,
        top_k: int = RAG_TOP_K,
        min_score: float = RAG_MIN_SCORE,
    ) -> list[ScoredChunk]:
        return self._retriever.search(query, top_k=top_k, min_score=min_score)

    def categories(self) -> dict[str, int]:
        counts: dict[str, int] = {}
        for document in self._documents:
            counts[document.category] = counts.get(document.category, 0) + 1

        return counts

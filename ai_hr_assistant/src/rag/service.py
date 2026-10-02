from __future__ import annotations

from src.config import (
    CHUNK_MAX_CHARS,
    CHUNK_OVERLAP_CHARS,
    KNOWLEDGE_DIR,
    RAG_FUSION,
    RAG_LEXICAL_WEIGHT,
    RAG_MIN_SCORE,
    RAG_MMR,
    RAG_MMR_LAMBDA,
    RAG_TOP_K,
)
from src.embeddings.embedder import Embedder, get_embedder
from src.rag.loader import Chunk, Document, load_knowledge_base
from src.retrieval.retriever import HybridRetriever
from src.retrieval.vector_store import ScoredChunk


class RagService:
    """Retrieval-Augmented Generation: knowledge base + retriever hybrid.

    Alur: dokumen markdown → chunk (ukuran + overlap) → embedding → retriever
    hybrid (leksikal + semantik, digabung via RRF, opsional MMR).
    """

    def __init__(
        self,
        embedder: Embedder | None = None,
        knowledge_dir=None,
        fusion: str | None = None,
        mmr: bool | None = None,
    ):
        self._embedder = embedder or get_embedder()
        self._knowledge_dir = knowledge_dir or KNOWLEDGE_DIR
        self._fusion = fusion or RAG_FUSION
        self._mmr = RAG_MMR if mmr is None else mmr
        self._documents: list[Document] = []
        self._chunks: list[Chunk] = []
        self._retriever = None
        self.reload()

    def reload(self) -> None:
        self._documents, self._chunks = load_knowledge_base(
            self._knowledge_dir,
            max_chars=CHUNK_MAX_CHARS,
            overlap_chars=CHUNK_OVERLAP_CHARS,
        )
        self._retriever = HybridRetriever(
            self._embedder,
            self._chunks,
            fusion=self._fusion,
            mmr=self._mmr,
            mmr_lambda=RAG_MMR_LAMBDA,
        )

    @property
    def documents(self) -> list[Document]:
        return list(self._documents)

    @property
    def chunks(self) -> list[Chunk]:
        return list(self._chunks)

    @property
    def embedder(self) -> Embedder:
        return self._embedder

    @property
    def fusion(self) -> str:
        return self._fusion

    def search(
        self,
        query: str,
        top_k: int = RAG_TOP_K,
        min_score: float = RAG_MIN_SCORE,
        lexical_weight: float = RAG_LEXICAL_WEIGHT,
    ) -> list[ScoredChunk]:
        return self._retriever.search(
            query,
            top_k=top_k,
            min_score=min_score,
            lexical_weight=lexical_weight,
        )

    def categories(self) -> dict[str, int]:
        counts: dict[str, int] = {}
        for document in self._documents:
            counts[document.category] = counts.get(document.category, 0) + 1

        return counts

from __future__ import annotations

from src.embeddings.embedder import Embedder, tokenize
from src.rag.loader import Chunk
from src.retrieval.vector_store import ScoredChunk, VectorStore


class HybridRetriever:
    """Retriever hybrid: skor leksikal (BM25 ringan) + skor semantik.

    Skor leksikal mengandalkan token & bigram yang benar-benar muncul sehingga
    pencocokan istilah (mis. "cuti tahunan") presisi, sementara skor semantik
    membantu saat pengguna memakai kata yang berbeda. Keduanya digabung dan
    diberi sedikit bonus bila istilah muncul di judul/heading.
    """

    def __init__(
        self,
        embedder: Embedder,
        chunks: list[Chunk],
        k1: float = 1.5,
        b: float = 0.75,
    ):
        self._embedder = embedder
        self._store = VectorStore(embedder)
        self._store.add(chunks)

        self._k1 = k1
        self._b = b
        self._tokenized: list[list[str]] = [
            tokenize(f"{chunk.title} {chunk.heading} {chunk.text}")
            for chunk in chunks
        ]
        self._doc_len = [len(tokens) for tokens in self._tokenized]
        self._avg_len = (
            sum(self._doc_len) / len(self._doc_len) if self._doc_len else 0.0
        )
        self._df: dict[str, int] = {}

        for tokens in self._tokenized:
            for token in set(tokens):
                self._df[token] = self._df.get(token, 0) + 1

        self._n = len(chunks)

    def __len__(self) -> int:
        return self._n

    def _idf(self, token: str) -> float:
        import math

        df = self._df.get(token, 0)

        return math.log(1 + (self._n - df + 0.5) / (df + 0.5))

    def _lexical_scores(self, query: str) -> tuple[list[float], list[list[str]]]:
        query_tokens = tokenize(query)
        scores = [0.0] * self._n
        matched: list[list[str]] = [[] for _ in range(self._n)]

        if not query_tokens:
            return scores, matched

        for index, tokens in enumerate(self._tokenized):
            counts: dict[str, int] = {}
            for token in tokens:
                counts[token] = counts.get(token, 0) + 1

            length = self._doc_len[index] or 1
            total = 0.0

            for token in set(query_tokens):
                freq = counts.get(token, 0)
                if freq == 0:
                    continue

                idf = self._idf(token)
                denom = freq + self._k1 * (
                    1 - self._b + self._b * length / (self._avg_len or 1)
                )
                total += idf * (freq * (self._k1 + 1)) / denom

                matched[index].append(token)

            scores[index] = total

        # Satukan skala BM25 ke rentang [0, 1) tanpa normalisasi per-query,
        # agar skor tetap dapat dibandingkan antar kueri dan `min_score`
        # benar-benar menyaring kecocokan lemah.
        scores = [score / (score + 1.0) for score in scores]

        return scores, matched

    def search(
        self,
        query: str,
        top_k: int = 4,
        min_score: float = 0.0,
        lexical_weight: float = 0.7,
    ) -> list[ScoredChunk]:
        chunks = self._store.chunks
        lexical, matched = self._lexical_scores(query)
        semantic = self._store.semantic_scores(query)

        results: list[ScoredChunk] = []

        for index, chunk in enumerate(chunks):
            score = lexical_weight * lexical[index] + (1 - lexical_weight) * semantic[index]

            # Bonus bila istilah kueri muncul di judul atau heading chunk.
            haystack = f"{chunk.title} {chunk.heading}".lower()
            if any(token in haystack for token in tokenize(query)):
                score += 0.1

            if score < min_score:
                continue

            results.append(
                ScoredChunk(
                    chunk=chunk,
                    score=round(score, 4),
                    lexical=round(lexical[index], 4),
                    semantic=round(semantic[index], 4),
                    matched_terms=sorted(set(matched[index]))[:8],
                )
            )

        results.sort(key=lambda item: item.score, reverse=True)

        return results[:top_k]

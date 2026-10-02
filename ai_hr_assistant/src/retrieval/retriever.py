from __future__ import annotations

import math

from src.embeddings.embedder import Embedder, tokenize
from src.rag.loader import Chunk
from src.retrieval.vector_store import ScoredChunk, VectorStore


# Konstanta RRF: 60 adalah nilai standar dari literatur (Cormack et al.).
_RRF_K = 60.0

FUSION_MODES = ("rrf", "linear", "lexical", "semantic")


class HybridRetriever:
    """Retriever hybrid: leksikal (BM25 ringan) + semantik (embedding).

    Dua jalur memberi sifat saling melengkapi:

    - **Leksikal (BM25)** presisi saat istilah kueri benar-benar muncul
      (mis. "cuti tahunan"), tetapi buta terhadap sinonim.
    - **Semantik** menangkap kedekatan makna sehingga "izin tidak masuk"
      masih dapat menemukan dokumen tentang "cuti".

    Hasil kedua jalur digabung. `fusion` mengatur caranya:

    - `rrf` (default): Reciprocal Rank Fusion. Menggabungkan **peringkat**,
      bukan skor, sehingga tidak perlu menyeimbangkan skala skor yang berbeda.
      Paling tahan terhadap satu jalur yang skornya ekstrem.
    - `linear`: rata-rata berbobot skor ternormalisasi (`lexical_weight`).
    - `lexical` / `semantic`: hanya satu jalur (berguna untuk perbandingan).

    `mmr` mengaktifkan Maximum Marginal Relevance untuk membuang hasil yang
    redundan, sehingga `top_k` chunk saling melengkapi alih-alih mengulang
    isi yang sama.
    """

    def __init__(
        self,
        embedder: Embedder,
        chunks: list[Chunk],
        k1: float = 1.5,
        b: float = 0.75,
        fusion: str = "rrf",
        mmr: bool = False,
        mmr_lambda: float = 0.7,
        candidate_k: int = 20,
    ):
        if fusion not in FUSION_MODES:
            raise ValueError(
                f"fusion tidak dikenal: '{fusion}'. Pilih salah satu {FUSION_MODES}."
            )

        self._embedder = embedder
        self._fusion = fusion
        self._mmr = mmr
        self._mmr_lambda = mmr_lambda
        self._candidate_k = candidate_k

        self._k1 = k1
        self._b = b

        texts = [self._index_text(chunk) for chunk in chunks]

        # Backend seperti LSA perlu mempelajari korpus sebelum bisa embed.
        # Korpus kosong dilewati agar tidak memicu "empty vocabulary".
        if texts and getattr(embedder, "requires_fit", False):
            embedder.fit(texts)

        self._store = VectorStore(embedder)
        self._store.add(chunks)

        self._tokenized: list[list[str]] = [tokenize(text) for text in texts]
        self._doc_len = [len(tokens) for tokens in self._tokenized]
        self._avg_len = (
            sum(self._doc_len) / len(self._doc_len) if self._doc_len else 0.0
        )
        self._df: dict[str, int] = {}

        for tokens in self._tokenized:
            for token in set(tokens):
                self._df[token] = self._df.get(token, 0) + 1

        self._n = len(chunks)

    @staticmethod
    def _index_text(chunk: Chunk) -> str:
        return f"{chunk.title} {chunk.heading} {chunk.text}"

    def __len__(self) -> int:
        return self._n

    def _idf(self, token: str) -> float:
        df = self._df.get(token, 0)

        return math.log(1 + (self._n - df + 0.5) / (df + 0.5))

    # ------------------------------------------------------------------ jalur

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

    # ------------------------------------------------------------------ fusion

    def _fuse(
        self,
        lexical: list[float],
        semantic: list[float],
        lexical_weight: float,
    ) -> list[float]:
        mode = self._fusion

        if mode == "lexical":
            return list(lexical)

        if mode == "semantic":
            return list(semantic)

        if mode == "linear":
            return [
                lexical_weight * lexical[i] + (1 - lexical_weight) * semantic[i]
                for i in range(self._n)
            ]

        # RRF: gabungkan peringkat tiap jalur.
        scores = [0.0] * self._n

        for ranking in (lexical, semantic):
            order = sorted(range(self._n), key=lambda i: ranking[i], reverse=True)

            for rank, index in enumerate(order):
                if ranking[index] <= 0.0:
                    continue
                scores[index] += 1.0 / (_RRF_K + rank + 1)

        # Normalisasi: maksimum teoretis adalah muncul di peringkat 1 pada
        # kedua jalur, sehingga skor tertinggi bernilai 1.0.
        max_possible = 2.0 / (_RRF_K + 1)

        return [score / max_possible for score in scores]

    # --------------------------------------------------------------------- mmr

    def _apply_mmr(
        self,
        candidates: list[ScoredChunk],
        top_k: int,
        lambda_: float,
    ) -> list[ScoredChunk]:
        if len(candidates) <= 1:
            return candidates[:top_k]

        vectors = self._store.vectors
        index_of = {id(chunk): i for i, chunk in enumerate(self._store.chunks)}

        selected: list[ScoredChunk] = []
        pool = list(candidates)

        while pool and len(selected) < top_k:
            best = None
            best_value = -math.inf

            for candidate in pool:
                relevance = candidate.score
                redundancy = 0.0

                if selected:
                    candidate_vector = vectors[index_of[id(candidate.chunk)]]
                    redundancy = max(
                        self._embedder.cosine(
                            candidate_vector, vectors[index_of[id(other.chunk)]]
                        )
                        for other in selected
                    )

                value = lambda_ * relevance - (1 - lambda_) * redundancy

                if value > best_value:
                    best_value = value
                    best = candidate

            selected.append(best)
            pool.remove(best)

        return selected

    # ------------------------------------------------------------------ publik

    def search(
        self,
        query: str,
        top_k: int = 4,
        min_score: float = 0.0,
        lexical_weight: float = 0.7,
        mmr: bool | None = None,
    ) -> list[ScoredChunk]:
        chunks = self._store.chunks
        lexical, matched = self._lexical_scores(query)
        semantic = self._store.semantic_scores(query)

        fused = self._fuse(lexical, semantic, lexical_weight)

        use_mmr = self._mmr if mmr is None else mmr
        limit = max(top_k, self._candidate_k) if use_mmr else top_k

        results: list[ScoredChunk] = []
        query_tokens = tokenize(query)

        for index, chunk in enumerate(chunks):
            # `min_score` disaring memakai relevansi absolut (max kedua jalur),
            # bukan skor fused: skor RRF berbasis peringkat sehingga chunk
            # terlemah pun bisa bernilai tinggi bila seluruh kandidat lemah.
            relevance = max(lexical[index], semantic[index])

            if relevance < min_score:
                continue

            score = fused[index]

            # Bonus bila istilah kueri muncul di judul atau heading chunk.
            haystack = f"{chunk.title} {chunk.heading}".lower()
            if any(token in haystack for token in query_tokens):
                score += 0.1

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

        if use_mmr:
            return self._apply_mmr(results, top_k, self._mmr_lambda)

        return results[:limit]

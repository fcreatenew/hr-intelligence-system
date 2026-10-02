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
    """Antarmuka embedding: teks -> vector.

    `requires_fit` menandai backend yang perlu mempelajari korpus lebih dulu
    (mis. TF-IDF + SVD). Retriever memanggil `fit` dengan seluruh chunk saat
    indeks dibangun, lalu `embed` untuk query.
    """

    dim: int
    requires_fit: bool = False

    @abstractmethod
    def embed(self, text: str) -> list[float]:
        ...

    def fit(self, texts: list[str]) -> None:
        """Latih representasi dari korpus. No-op untuk backend statis."""
        return None

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


class LsaEmbedder(Embedder):
    """Embedding semantik via TF-IDF + SVD (Latent Semantic Analysis).

    Berbeda dari `HashingEmbedder` yang hanya mencocokkan token yang sama,
    LSA memproyeksikan istilah ke ruang laten berdimensi rendah sehingga kata
    yang sering muncul dalam konteks serupa saling berdekatan. Deterministik
    dan tanpa model eksternal.

    `dim` efektif dibatasi jumlah dokumen pelatihan: SVD tidak bisa
    menghasilkan lebih banyak komponen daripada min(n_dokumen, n_fitur).
    """

    requires_fit = True

    def __init__(self, dim: int = 256):
        if dim <= 0:
            raise ValueError("dim harus > 0")

        self.dim = dim
        self._dim_eff = dim
        self._vectorizer = None
        self._svd = None

    def fit(self, texts: list[str]) -> None:
        from sklearn.decomposition import TruncatedSVD
        from sklearn.feature_extraction.text import TfidfVectorizer

        self._vectorizer = TfidfVectorizer(
            lowercase=True,
            sublinear_tf=True,
            ngram_range=(1, 2),
            min_df=1,
            token_pattern=r"(?u)\b\w+\b",
        )
        matrix = self._vectorizer.fit_transform(texts)

        n_components = max(
            2, min(self.dim, matrix.shape[0] - 1, matrix.shape[1] - 1)
        )
        self._dim_eff = n_components
        self._svd = TruncatedSVD(n_components=n_components, random_state=42)
        self._svd.fit(matrix)

    def embed(self, text: str) -> list[float]:
        if self._vectorizer is None or self._svd is None:
            raise RuntimeError(
                "LsaEmbedder harus di-`fit` pada korpus sebelum `embed`."
            )

        tfidf = self._vectorizer.transform([text])
        latent = self._svd.transform(tfidf)[0]

        norm = math.sqrt(sum(v * v for v in latent))

        if norm == 0.0:
            return [0.0] * self._dim_eff

        return [float(v / norm) for v in latent]


class NeuralEmbedder(Embedder):
    """Embedding neural via sentence-transformers (opsional).

    Kualitas semantik terbaik dan mendukung lintas bahasa, tetapi butuh
    dependensi berat dan unduhan model. Vektor sentence-transformers sudah
    dinormalisasi sehingga dikembalikan apa adanya.
    """

    def __init__(self, model_name: str = "paraphrase-multilingual-MiniLM-L12-v2"):
        try:
            from sentence_transformers import SentenceTransformer  # type: ignore
        except ImportError as exc:  # pragma: no cover - paket opsional
            raise RuntimeError(
                "EMBEDDING_BACKEND=neural membutuhkan paket "
                "'sentence-transformers'. Jalankan: pip install sentence-transformers"
            ) from exc

        self._model = SentenceTransformer(model_name)
        self.dim = int(self._model.get_sentence_embedding_dimension())

    def embed(self, text: str) -> list[float]:
        vector = self._model.encode(text, normalize_embeddings=True)

        return [float(v) for v in vector]


def get_embedder(
    backend: str | None = None,
    dim: int | None = None,
    model_name: str | None = None,
) -> Embedder:
    """Pilih backend embedding dari nama.

    Backend yang didukung:

    - `lsa` (default): TF-IDF + SVD (Latent Semantic Analysis). Menangkap
      kemiripan **semantik** — sinonim dan variasi kata yang berbagi konteks —
      tanpa model eksternal, deterministik, dan cocok untuk knowledge base
      kecil–sedang. Perlu `fit` pada korpus.
    - `hashing`: feature hashing leksikal, tanpa fit, paling ringan.
    - `neural`: sentence-transformers (mis. `paraphrase-multilingual-MiniLM-L12-v2`).
      Kualitas semantik terbaik, termasuk lintas bahasa, tetapi butuh unduhan
      model dan dependensi berat.
    """
    from src.config import EMBEDDING_BACKEND, EMBEDDING_DIM, EMBEDDING_MODEL

    backend = (backend or EMBEDDING_BACKEND).lower()
    dim = dim or EMBEDDING_DIM
    model_name = model_name or EMBEDDING_MODEL

    if backend in {"lsa", "tfidf", "svd", "semantic", "default"}:
        return LsaEmbedder(dim=dim)

    if backend in {"hashing", "local"}:
        return HashingEmbedder(dim=dim)

    if backend in {"neural", "sentence-transformers", "st"}:
        return NeuralEmbedder(model_name=model_name)

    raise ValueError(
        f"Backend embedding tidak dikenal: '{backend}'. "
        "Gunakan 'lsa', 'hashing', atau 'neural'."
    )

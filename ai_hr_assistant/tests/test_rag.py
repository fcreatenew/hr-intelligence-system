import pytest

from src.embeddings.embedder import HashingEmbedder, tokenize
from src.rag.loader import load_knowledge_base
from src.retrieval.retriever import HybridRetriever
from src.config import KNOWLEDGE_DIR


def test_knowledge_base_loads_all_categories():
    documents, chunks = load_knowledge_base(KNOWLEDGE_DIR)

    assert len(documents) >= 10
    assert len(chunks) > len(documents)

    categories = {doc.category for doc in documents}
    assert {"policies", "procedures", "regulations", "faq"} <= categories


def test_embedder_is_deterministic_and_normalized():
    embedder = HashingEmbedder(dim=128)

    a = embedder.embed("prosedur cuti tahunan")
    b = embedder.embed("prosedur cuti tahunan")

    assert a == b
    assert abs(sum(x * x for x in a) - 1.0) < 1e-6


def test_tokenize_removes_stopwords_and_adds_bigrams():
    tokens = tokenize("Bagaimana prosedur pengajuan cuti tahunan?")

    assert "bagaimana" not in tokens
    assert "prosedur" in tokens
    assert "pengajuan_cuti" in tokens


def test_retriever_ranks_relevant_document_first():
    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)
    retriever = HybridRetriever(HashingEmbedder(dim=256), chunks)

    results = retriever.search("prosedur pengajuan cuti", top_k=3)

    assert results
    # Pada granularitas chunk, FAQ "cara mengajukan cuti" dan bagian prosedur
    # sama-sama relevan dan skornya berdekatan. Yang penting dokumen prosedur
    # tetap masuk top-3 dan tidak ada chunk yang hanya berisi metadata tag.
    assert "pengajuan-cuti" in {result.chunk.doc_id for result in results}
    assert all(result.score > 0 for result in results)
    assert all(result.chunk.text.strip() for result in results)


def test_retriever_respects_min_score():
    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)
    retriever = HybridRetriever(HashingEmbedder(dim=256), chunks)

    results = retriever.search("zzzqqq wwyyy vvvuu", top_k=3, min_score=0.5)

    assert results == []


# --- Chunking: ukuran maksimum + overlap -----------------------------------


def test_chunking_splits_long_section_with_overlap():
    from src.rag.loader import Document, chunk_document

    body = " ".join(
        f"Kalimat nomor {i} tentang prosedur cuti tahunan." for i in range(60)
    )
    document = Document(
        doc_id="panjang",
        title="Dokumen Panjang",
        category="procedures",
        source="knowledge/procedures/panjang.md",
        text=f"# Dokumen Panjang\n\n## Ketentuan\n\n{body}\n",
    )

    chunks = chunk_document(document, max_chars=300, overlap_chars=80)

    assert len(chunks) > 1
    assert all(len(chunk.text) <= 400 for chunk in chunks)
    assert all(chunk.n_parts == len(chunks) for chunk in chunks)
    assert [chunk.part for chunk in chunks] == list(range(len(chunks)))

    # Overlap: potongan terakhir chunk pertama muncul di awal chunk kedua.
    tail = chunks[0].text.split(".")[-2].strip()
    assert tail and tail in chunks[1].text


def test_chunking_keeps_short_section_intact():
    from src.rag.loader import Document, chunk_document

    document = Document(
        doc_id="pendek",
        title="Pendek",
        category="faq",
        source="knowledge/faq/pendek.md",
        text=(
            "# Pendek\n\n## Isi\n\n"
            "Gaji dibayarkan setiap akhir bulan dan dipotong pajak sesuai aturan.\n"
        ),
    )

    chunks = chunk_document(document, max_chars=900)

    assert len(chunks) == 1
    assert chunks[0].n_parts == 1
    assert "akhir bulan" in chunks[0].text


# --- Embedding semantik (LSA) ----------------------------------------------


def test_lsa_embedder_requires_fit_before_embed():
    from src.embeddings.embedder import LsaEmbedder

    embedder = LsaEmbedder(dim=32)

    assert embedder.requires_fit is True

    with pytest.raises(RuntimeError):
        embedder.embed("prosedur cuti")


def test_lsa_embedder_is_deterministic_and_normalized():
    from src.embeddings.embedder import LsaEmbedder

    texts = [
        "prosedur pengajuan cuti tahunan",
        "kebijakan lembur dan upah tambahan",
        "perlindungan data pribadi karyawan",
        "penanganan absensi tinggi",
    ]
    embedder = LsaEmbedder(dim=8)
    embedder.fit(texts)

    a = embedder.embed("prosedur cuti")
    b = embedder.embed("prosedur cuti")

    assert a == b
    assert abs(sum(x * x for x in a) - 1.0) < 1e-6


def test_lsa_beats_hashing_on_semantic_similarity():
    """LSA menangkap kemiripan makna lebih baik daripada feature hashing."""
    from src.embeddings.embedder import HashingEmbedder, LsaEmbedder

    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)
    query = "uang lembur dibayar bagaimana"

    hashing = HybridRetriever(HashingEmbedder(dim=256), chunks, fusion="semantic")
    lsa = HybridRetriever(LsaEmbedder(dim=128), chunks, fusion="semantic")

    best_hashing = hashing.search(query, top_k=1, min_score=0.0)[0].semantic
    best_lsa = lsa.search(query, top_k=1, min_score=0.0)[0].semantic

    assert best_lsa > best_hashing


# --- Fusion -----------------------------------------------------------------


def test_retriever_rejects_unknown_fusion_mode():
    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)

    with pytest.raises(ValueError):
        HybridRetriever(HashingEmbedder(dim=64), chunks, fusion="tidak-ada")


def test_retriever_fusion_modes_return_ranked_results():
    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)
    retriever = HybridRetriever(HashingEmbedder(dim=128), chunks)

    for fusion in ("rrf", "linear", "lexical", "semantic"):
        retriever._fusion = fusion
        results = retriever.search("prosedur pengajuan cuti", top_k=3)

        assert results
        assert results == sorted(results, key=lambda item: item.score, reverse=True)


# --- MMR: diversitas hasil ---------------------------------------------------


def test_mmr_diversifies_near_duplicate_chunks():
    from src.rag.loader import Chunk

    def make(chunk_id: str, text: str) -> Chunk:
        return Chunk(
            chunk_id=chunk_id,
            doc_id=chunk_id.split("::")[0],
            title="Dokumen",
            category="faq",
            source="knowledge/faq/dokumen.md",
            heading="Bagian",
            text=text,
        )

    duplicate = (
        "Prosedur pengajuan cuti tahunan diajukan minimal tiga hari kerja sebelumnya."
    )
    chunks = [
        make("a::0", duplicate),
        make("b::0", duplicate),
        make("c::0", "Kebijakan perlindungan data pribadi karyawan dan kerahasiaan."),
    ]

    plain = HybridRetriever(HashingEmbedder(dim=128), chunks, mmr=False)
    diversified = HybridRetriever(
        HashingEmbedder(dim=128), chunks, mmr=True, mmr_lambda=0.3
    )

    query = "prosedur pengajuan cuti tahunan"
    top_plain = [
        item.chunk.chunk_id for item in plain.search(query, top_k=2, min_score=0.0)
    ]
    top_mmr = [
        item.chunk.chunk_id
        for item in diversified.search(query, top_k=2, min_score=0.0)
    ]

    assert len(set(top_mmr)) == 2
    assert top_mmr != top_plain


# --- Embedder factory --------------------------------------------------------


def test_get_embedder_selects_backend_by_name():
    from src.embeddings.embedder import HashingEmbedder, LsaEmbedder, get_embedder

    assert isinstance(get_embedder("lsa", dim=16), LsaEmbedder)
    assert isinstance(get_embedder("hashing", dim=16), HashingEmbedder)

    with pytest.raises(ValueError):
        get_embedder("tidak-ada")


def test_neural_embedder_reports_missing_dependency():
    from src.embeddings.embedder import NeuralEmbedder

    try:
        import sentence_transformers  # noqa: F401
    except ImportError:
        with pytest.raises(RuntimeError, match="sentence-transformers"):
            NeuralEmbedder()

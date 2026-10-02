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
    assert results[0].chunk.doc_id == "pengajuan-cuti"
    assert results[0].score > 0


def test_retriever_respects_min_score():
    _, chunks = load_knowledge_base(KNOWLEDGE_DIR)
    retriever = HybridRetriever(HashingEmbedder(dim=256), chunks)

    results = retriever.search("zzzqqq wwyyy vvvuu", top_k=3, min_score=0.5)

    assert results == []

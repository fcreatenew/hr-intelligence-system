from __future__ import annotations

import re
from dataclasses import dataclass, field
from pathlib import Path


@dataclass
class Document:
    doc_id: str
    title: str
    category: str
    source: str
    text: str
    tags: list[str] = field(default_factory=list)


@dataclass
class Chunk:
    chunk_id: str
    doc_id: str
    title: str
    category: str
    source: str
    heading: str
    text: str
    tags: list[str] = field(default_factory=list)
    part: int = 0
    n_parts: int = 1

    @property
    def citation(self) -> dict:
        return {
            "doc_id": self.doc_id,
            "title": self.title,
            "source": self.source,
            "category": self.category,
        }


_HEADING_RE = re.compile(r"^(#{1,6})\s+(.*)$", re.MULTILINE)
_TAG_RE = re.compile(r"^>\s*\*\*Tag:\*\*\s*(.+)$", re.MULTILINE | re.IGNORECASE)

# Pemisah kalimat: titik/tanya/seru diikuti spasi, atau baris baru. Cukup untuk
# knowledge base berbahasa Indonesia; tidak perlu tokenizer kalimat penuh.
_SENTENCE_RE = re.compile(r"(?<=[.!?])\s+|\n+")


def _split_sections(text: str) -> list[tuple[str, str]]:
    """Pecah markdown menjadi (heading, isi) per bagian `##`/`###`.

    Bagian sebelum heading pertama (judul dokumen) digabung sebagai
    konteks pembuka agar tidak hilang.
    """
    matches = list(_HEADING_RE.finditer(text))

    if not matches:
        return [("", text.strip())]

    sections: list[tuple[str, str]] = []
    preamble = text[: matches[0].start()].strip()

    for index, match in enumerate(matches):
        level = len(match.group(1))
        heading = match.group(2).strip()
        start = match.end()
        end = matches[index + 1].start() if index + 1 < len(matches) else len(text)
        body = text[start:end].strip()

        # Baris tag adalah metadata dan sudah diekstrak ke `Document.tags`;
        # membiarkannya di isi menghasilkan chunk "hantu" yang hanya berisi tag.
        body = _TAG_RE.sub("", body).strip()

        if level == 1:
            # Judul dokumen: sisipkan preamble bila ada.
            if preamble:
                body = f"{preamble}\n\n{body}".strip()
            sections.append((heading, body))
        else:
            sections.append((heading, body))

    return [(h, b) for h, b in sections if b]


def load_document(path: Path, root: Path) -> Document:
    text = path.read_text(encoding="utf-8")

    heading = _HEADING_RE.search(text)
    title = heading.group(2).strip() if heading else path.stem

    tag_match = _TAG_RE.search(text)
    tags = (
        [t.strip().lower() for t in tag_match.group(1).split(",")]
        if tag_match
        else []
    )

    category = path.parent.name if path.parent != root else "general"

    return Document(
        doc_id=path.stem,
        title=title,
        category=category,
        source=str(path.relative_to(root.parent)),
        text=text,
        tags=tags,
    )


def _split_sentences(text: str) -> list[str]:
    return [part.strip() for part in _SENTENCE_RE.split(text) if part.strip()]


def _pack_sentences(
    sentences: list[str],
    max_chars: int,
    overlap_chars: int,
) -> list[str]:
    """Bungkus kalimat menjadi potongan ≤ max_chars dengan overlap.

    Overlap menjaga konteks yang terpotong batas tetap terbawa ke potongan
    berikutnya, sehingga retrieval tidak kehilangan makna di tepi chunk.
    Kalimat tunggal yang lebih panjang dari max_chars tetap dipaksa masuk
    apa adanya (lebih baik utuh daripada terpotong di tengah kata).
    """
    chunks: list[str] = []
    current: list[str] = []

    for sentence in sentences:
        candidate = " ".join(current + [sentence])

        if current and len(candidate) > max_chars:
            chunks.append(" ".join(current))

            # Ambil ekor potongan sebelumnya sebagai awalan overlap.
            overlap: list[str] = []
            total = 0

            for previous in reversed(current):
                if total + len(previous) > overlap_chars:
                    break
                overlap.insert(0, previous)
                total += len(previous) + 1

            current = overlap

        current.append(sentence)

    if current:
        chunks.append(" ".join(current))

    return chunks


def chunk_document(
    document: Document,
    min_chars: int = 40,
    max_chars: int = 900,
    overlap_chars: int = 150,
) -> list[Chunk]:
    """Potong dokumen per heading, lalu pecah lagi bila melebihi `max_chars`.

    Dua tingkat: (1) batas heading menjaga potongan tetap fokus pada satu
    sub-topik, (2) batas ukuran + overlap menjaga potongan tetap ringkas
    namun tidak kehilangan konteks di tepi.
    """
    chunks: list[Chunk] = []
    index = 0

    for heading, body in _split_sections(document.text):
        if len(body) < min_chars:
            continue

        pieces = (
            _pack_sentences(_split_sentences(body), max_chars, overlap_chars)
            if len(body) > max_chars
            else [body]
        )

        for part, piece in enumerate(pieces):
            if len(piece) < min_chars and part > 0:
                continue

            chunks.append(
                Chunk(
                    chunk_id=f"{document.doc_id}::{index}",
                    doc_id=document.doc_id,
                    title=document.title,
                    category=document.category,
                    source=document.source,
                    heading=heading or document.title,
                    text=piece,
                    tags=document.tags,
                    part=part,
                    n_parts=len(pieces),
                )
            )
            index += 1

    return chunks


def load_knowledge_base(
    knowledge_dir: Path,
    max_chars: int = 900,
    overlap_chars: int = 150,
) -> tuple[list[Document], list[Chunk]]:
    documents: list[Document] = []
    chunks: list[Chunk] = []

    if not knowledge_dir.exists():
        return documents, chunks

    for path in sorted(knowledge_dir.rglob("*.md")):
        document = load_document(path, knowledge_dir)
        documents.append(document)
        chunks.extend(
            chunk_document(
                document, max_chars=max_chars, overlap_chars=overlap_chars
            )
        )

    return documents, chunks

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


def chunk_document(document: Document, min_chars: int = 40) -> list[Chunk]:
    chunks: list[Chunk] = []

    for index, (heading, body) in enumerate(_split_sections(document.text)):
        if len(body) < min_chars:
            continue

        chunks.append(
            Chunk(
                chunk_id=f"{document.doc_id}::{index}",
                doc_id=document.doc_id,
                title=document.title,
                category=document.category,
                source=document.source,
                heading=heading or document.title,
                text=body,
                tags=document.tags,
            )
        )

    return chunks


def load_knowledge_base(knowledge_dir: Path) -> tuple[list[Document], list[Chunk]]:
    documents: list[Document] = []
    chunks: list[Chunk] = []

    if not knowledge_dir.exists():
        return documents, chunks

    for path in sorted(knowledge_dir.rglob("*.md")):
        document = load_document(path, knowledge_dir)
        documents.append(document)
        chunks.extend(chunk_document(document))

    return documents, chunks

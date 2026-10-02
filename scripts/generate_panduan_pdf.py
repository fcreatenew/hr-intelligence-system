"""Konversi PANDUAN_PENGGUNAAN.md menjadi PDF.

Jalankan dari root repo:

    python scripts/generate_panduan_pdf.py

Butuh paket `markdown` dan `weasyprint`:

    pip install markdown weasyprint
"""

from __future__ import annotations

import sys
from pathlib import Path

import markdown
from weasyprint import CSS, HTML

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "PANDUAN_PENGGUNAAN.md"
TARGET = ROOT / "PANDUAN_PENGGUNAAN.pdf"

CSS_TEXT = """
@page {
  size: A4;
  margin: 18mm 16mm 20mm 16mm;
  @bottom-center {
    content: "HR Intelligence System — Panduan Penggunaan · halaman " counter(page);
    font-size: 8pt;
    color: #6b7280;
  }
}

body {
  font-family: "DejaVu Sans", "Liberation Sans", Arial, sans-serif;
  font-size: 10pt;
  line-height: 1.55;
  color: #1f2937;
}

h1 {
  font-size: 20pt;
  color: #0f172a;
  border-bottom: 3px solid #2563eb;
  padding-bottom: 6px;
  margin-top: 0;
}

h2 {
  font-size: 14pt;
  color: #1d4ed8;
  border-bottom: 1px solid #dbeafe;
  padding-bottom: 3px;
  margin-top: 22px;
  page-break-after: avoid;
}

h3 {
  font-size: 11.5pt;
  color: #1e3a8a;
  margin-top: 16px;
  page-break-after: avoid;
}

p, li { orphans: 3; widows: 3; }

code {
  font-family: "DejaVu Sans Mono", "Liberation Mono", monospace;
  font-size: 8.6pt;
  background: #f1f5f9;
  padding: 1px 4px;
  border-radius: 3px;
  color: #b91c1c;
}

pre {
  background: #0f172a;
  color: #e2e8f0;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 8.4pt;
  line-height: 1.45;
  overflow-wrap: break-word;
  white-space: pre-wrap;
  page-break-inside: avoid;
}

pre code {
  background: transparent;
  color: #e2e8f0;
  padding: 0;
}

table {
  border-collapse: collapse;
  width: 100%;
  margin: 10px 0;
  font-size: 8.8pt;
  page-break-inside: avoid;
}

th {
  background: #1d4ed8;
  color: #ffffff;
  text-align: left;
  padding: 6px 8px;
}

td {
  border: 1px solid #cbd5e1;
  padding: 5px 8px;
  vertical-align: top;
}

tr:nth-child(even) td { background: #f8fafc; }

blockquote {
  border-left: 4px solid #f59e0b;
  background: #fffbeb;
  margin: 12px 0;
  padding: 8px 14px;
  color: #78350f;
}

a { color: #1d4ed8; text-decoration: none; }

hr {
  border: none;
  border-top: 1px solid #e2e8f0;
  margin: 20px 0;
}

ul, ol { padding-left: 20px; }
"""


def build_html(markdown_text: str) -> str:
    body = markdown.markdown(
        markdown_text,
        extensions=["extra", "tables", "fenced_code", "toc", "sane_lists"],
    )

    return f"<html><head><meta charset='utf-8'></head><body>{body}</body></html>"


def main() -> int:
    if not SOURCE.exists():
        print(f"Sumber tidak ditemukan: {SOURCE}", file=sys.stderr)
        return 1

    html = build_html(SOURCE.read_text(encoding="utf-8"))
    HTML(string=html, base_url=str(ROOT)).write_pdf(
        TARGET, stylesheets=[CSS(string=CSS_TEXT)]
    )

    print(f"PDF dibuat: {TARGET} ({TARGET.stat().st_size / 1024:.1f} KB)")

    return 0


if __name__ == "__main__":
    sys.exit(main())

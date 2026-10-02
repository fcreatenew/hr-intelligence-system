"""Verifikasi contoh payload di PANDUAN_PENGGUNAAN.md terhadap skema API nyata.

Jalankan dari root repo:  python scripts/verify_panduan_examples.py

Hanya memakai TestClient (tanpa server), sehingga cepat dan offline.
"""

from __future__ import annotations

import json
import os
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "hr_intelligence_api"))

os.environ.setdefault("HR_API_KEY", "dev-secret")

from fastapi.testclient import TestClient  # noqa: E402
from app.main import app  # noqa: E402

DOC = ROOT / "PANDUAN_PENGGUNAAN.md"
HEADERS = {"X-API-Key": "dev-secret"}


def extract_curls(markdown: str) -> list[tuple[str, dict]]:
    """Ambil pasangan (url, payload) dari blok curl di dokumen."""
    found: list[tuple[str, dict]] = []

    for block in re.findall(r"```bash\n(.*?)```", markdown, re.S):
        if "curl -X POST" not in block:
            continue

        url_match = re.search(r"curl -X POST (http://\S+)", block)
        payload_match = re.search(r"-d '(.*?)'\n", block, re.S) or re.search(
            r'-d \'(.*)\'', block, re.S
        )
        if not url_match or not payload_match:
            continue

        try:
            payload = json.loads(payload_match.group(1))
        except json.JSONDecodeError:
            continue

        found.append((url_match.group(1), payload))

    return found


def main() -> int:
    examples = extract_curls(DOC.read_text(encoding="utf-8"))

    if not examples:
        print("Tidak ada contoh curl yang ditemukan.", file=sys.stderr)
        return 1

    failures = 0
    checked = 0
    # Context manager agar lifespan berjalan -> model ML benar-benar dimuat.
    with TestClient(app) as client:
        for url, payload in examples:
            if "127.0.0.1:8000" not in url:
                # Contoh milik layanan lain (mis. ai_hr_assistant:8100).
                print(f"[lewati] {url} (layanan lain)")
                continue

            checked += 1
            path = url.split("127.0.0.1:8000", 1)[-1]
            response = client.post(path, headers=HEADERS, json=payload)
            status = response.status_code

            if status >= 400:
                print(f"[GAGAL] {path} -> {status} {str(response.json())[:150]}")
                failures += 1
            else:
                print(f"[ok]    {path} -> {status} {str(response.json())[:90]}")

    print()
    print(f"Contoh HR Intelligence API diuji: {checked}, gagal: {failures}")

    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())

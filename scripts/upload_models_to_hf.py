#!/usr/bin/env python
"""Unggah artifact model HR Intelligence ke Hugging Face Hub.

Script ini menyalin seluruh isi folder ``models/`` (model + metadata + pin
versi aktif) ke satu repo Hub, dengan struktur yang sama seperti yang dibaca
``hr_intelligence_api`` lewat ``app/core/hf_store.py``:

    <repo>/
    └── models/
        ├── active_version.json
        ├── v1/…
        ├── v2_mitigated/…
        ├── absenteeism/v1/…
        ├── performance/v1/…
        ├── payroll_anomaly/v1/…
        └── survival_attrition/v1/…

Contoh pemakaian:

    export HF_TOKEN=hf_xxx
    python scripts/upload_models_to_hf.py \
        --repo-id <user>/hr-intelligence-models

    # atau, bila repo belum ada:
    python scripts/upload_models_to_hf.py --repo-id <user>/hr-intelligence-models --create

Token dibaca dari ``HF_TOKEN`` (atau ``HUGGINGFACE_HUB_TOKEN``). Jangan
menuliskan token di baris perintah.
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path


REPO_ROOT = Path(__file__).resolve().parents[1]
MODELS_DIR = REPO_ROOT / "models"

# Folder di dalam repo Hub tempat artifact diletakkan (harus sama dengan
# HUB_ROOT di hr_intelligence_api/app/core/hf_store.py).
# HUB_ROOT = "models"

# Direktori model per use case. Hanya folder ini yang diunggah (plus berkas
# pendukung di root models/) agar ``.gitkeep``/README tidak ikut.
MODEL_DIRS = [
    "v1",
    "v2",
    "v2_mitigated",
    "absenteeism/v1",
    "performance/v1",
    "payroll_anomaly/v1",
    "survival_attrition/v1",
]

# Berkas pendukung di root models/ yang ikut diunggah.
ROOT_FILES = [
    "active_version.json",
    "fairness_report.json",
    "fairness_baseline.json",
    "mitigation_report.json",
    "significance_report.json",
]


def _token() -> str | None:
    return os.getenv("HF_TOKEN") or os.getenv("HUGGINGFACE_HUB_TOKEN")


def _collect_files() -> list[tuple[Path, str]]:
    """(path lokal, path di dalam repo Hub) untuk setiap artifact."""
    files: list[tuple[Path, str]] = []

    for rel in MODEL_DIRS:
        directory = MODELS_DIR / rel

        if not directory.is_dir():
            print(f"  lewati (tidak ada): {rel}")
            continue

        for path in sorted(directory.rglob("*")):
            if path.is_file():
                inside = path.relative_to(MODELS_DIR).as_posix()
                files.append((path, f"{inside}"))

    for name in ROOT_FILES:
        path = MODELS_DIR / name
        if path.is_file():
            files.append((path, f"{name}"))

    return files


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--repo-id",
        required=True,
        help="Repo Hub tujuan, mis. <user>/hr-intelligence-models.",
    )
    parser.add_argument(
        "--repo-type",
        default="model",
        choices=["model", "dataset", "space"],
        help="Jenis repo Hub (default: model).",
    )
    parser.add_argument(
        "--revision",
        default=None,
        help="Branch/revisi tujuan (default: main).",
    )
    parser.add_argument(
        "--create",
        action="store_true",
        help="Buat repo bila belum ada.",
    )
    parser.add_argument(
        "--private",
        action="store_true",
        help="Repo privat saat --create dipakai.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Hanya tampilkan berkas yang akan diunggah.",
    )

    args = parser.parse_args(argv)

    if not MODELS_DIR.is_dir():
        print(f"Folder models tidak ditemukan: {MODELS_DIR}", file=sys.stderr)
        return 1

    files = _collect_files()

    if not files:
        print("Tidak ada artifact yang ditemukan untuk diunggah.", file=sys.stderr)
        return 1

    total_bytes = sum(path.stat().st_size for path, _ in files)
    print(f"Akan mengunggah {len(files)} berkas ({total_bytes / 1e6:.2f} MB):")
    for path, dest in files:
        print(f"  {path.relative_to(REPO_ROOT)}  ->  {dest}")

    if args.dry_run:
        print("\n--dry-run: tidak ada yang diunggah.")
        return 0

    token = _token()
    if not token:
        print(
            "HF_TOKEN (atau HUGGINGFACE_HUB_TOKEN) belum di-set. "
            "Set token dengan izin write sebelum mengunggah.",
            file=sys.stderr,
        )
        return 1

    try:
        from huggingface_hub import HfApi, create_repo
    except ImportError:
        print(
            "Paket 'huggingface_hub' belum terpasang. "
            "Jalankan: pip install -r requirements/base.txt",
            file=sys.stderr,
        )
        return 1

    if args.create:
        create_repo(
            repo_id=args.repo_id,
            repo_type=args.repo_type,
            private=args.private,
            token=token,
            exist_ok=True,
        )
        print(f"Repo siap: {args.repo_id} ({args.repo_type})")

    api = HfApi(token=token)
    revision = args.revision

    for path, dest in files:
        print(f"unggah {dest} …")
        api.upload_file(
            path_or_fileobj=str(path),
            path_in_repo=dest,
            repo_id=args.repo_id,
            repo_type=args.repo_type,
            revision=revision,
        )

    print(
        f"\nSelesai. {len(files)} berkas diunggah ke "
        f"{args.repo_id} (root: )."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
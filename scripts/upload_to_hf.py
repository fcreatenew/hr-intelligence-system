#!/usr/bin/env python3
"""Unggah artifact HR Intelligence System ke Hugging Face Hub.

Tiga target:
  model   : artifact model + metadata -> Model repo
  dataset : dataset sintetis          -> Dataset repo
  space   : halaman statis            -> Static Space

Contoh:
    export HF_TOKEN=hf_...
    python scripts/upload_to_hf.py --target model   --repo <user>/hr-intelligence-models
    python scripts/upload_to_hf.py --target dataset --repo <user>/hr-intelligence-datasets
    python scripts/upload_to_hf.py --target space   --repo <user>/hr-intelligence-demo

Tambahkan --dry-run untuk melihat isi staging tanpa mengunggah.
Butuh paket `huggingface_hub` (pip install huggingface_hub).
"""

from __future__ import annotations

import argparse
import shutil
import sys
import tempfile
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[1]

# Layout model di HF: turnover/<versi>/..., absenteeism/v1/..., dst.
TURNOVER_VERSIONS = ("v1", "v2", "v2_mitigated")
MODEL_DIRS = {
    "absenteeism": "absenteeism/v1",
    "performance": "performance/v1",
    "payroll_anomaly": "payroll_anomaly/v1",
    "survival_attrition": "survival_attrition/v1",
}
MODEL_REPORT_FILES = (
    "fairness_report.json",
    "fairness_baseline.json",
    "mitigation_report.json",
    "significance_report.json",
)

DATASET_FILES = (
    "hr_turnover.csv",
    "hr_absenteeism.csv",
    "hr_performance.csv",
    "hr_payroll.csv",
    "hr_attrition_survival.csv",
)


def _stage_model(dest: Path) -> None:
    src_models = REPO_ROOT / "models"
    turnover_dir = dest / "turnover"
    turnover_dir.mkdir(parents=True, exist_ok=True)

    for version in TURNOVER_VERSIONS:
        source = src_models / version
        if source.is_dir():
            shutil.copytree(source, turnover_dir / version)

    for name, rel in MODEL_DIRS.items():
        source = src_models / rel
        if source.is_dir():
            target = dest / name / Path(rel).name
            shutil.copytree(source, target)

    for report in MODEL_REPORT_FILES:
        source = src_models / report
        if source.is_file():
            shutil.copy2(source, turnover_dir / report)

    shutil.copy2(REPO_ROOT / "hf" / "model_card.md", dest / "README.md")
    shutil.copy2(REPO_ROOT / "LICENSE", dest / "LICENSE")


def _stage_dataset(dest: Path) -> None:
    src_data = REPO_ROOT / "data"

    for filename in DATASET_FILES:
        source = src_data / filename
        if source.is_file():
            shutil.copy2(source, dest / filename)

    shutil.copy2(REPO_ROOT / "hf" / "dataset_card.md", dest / "README.md")
    shutil.copy2(REPO_ROOT / "LICENSE", dest / "LICENSE")


def _stage_space(dest: Path) -> None:
    src_space = REPO_ROOT / "hf" / "space"

    for item in src_space.iterdir():
        if item.is_dir():
            shutil.copytree(item, dest / item.name)
        else:
            shutil.copy2(item, dest / item.name)


STAGERS = {
    "model": _stage_model,
    "dataset": _stage_dataset,
    "space": _stage_space,
}


def _default_repo(target: str, username: str) -> str:
    suffix = {
        "model": "hr-intelligence-models",
        "dataset": "hr-intelligence-datasets",
        "space": "hr-intelligence-demo",
    }[target]

    return f"{username}/{suffix}"


def _print_tree(root: Path) -> None:
    for path in sorted(root.rglob("*")):
        if path.is_file():
            print(f"    {path.relative_to(root)}  ({path.stat().st_size:,} B)")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--target", choices=sorted(STAGERS), required=True)
    parser.add_argument("--repo", help="Repo HF, mis. <user>/hr-intelligence-models")
    parser.add_argument("--revision", default="main")
    parser.add_argument("--private", action="store_true")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    try:
        from huggingface_hub import HfApi
    except ImportError:
        print("Paket 'huggingface_hub' belum terpasang. Jalankan: pip install huggingface_hub")
        return 1

    api = HfApi()

    repo_id = args.repo
    if not repo_id:
        try:
            username = api.whoami()["name"]
        except Exception as exc:  # noqa: BLE001
            print(f"Tidak bisa membaca identitas HF (HF_TOKEN belum diset?): {exc}")
            return 1
        repo_id = _default_repo(args.target, username)

    with tempfile.TemporaryDirectory() as tmp:
        staging = Path(tmp)
        STAGERS[args.target](staging)

        files = [p for p in staging.rglob("*") if p.is_file()]
        if not files:
            print("Tidak ada file untuk diunggah.")
            return 1

        print(f"Target : {args.target}")
        print(f"Repo   : {repo_id} ({'privat' if args.private else 'publik'})")
        print(f"File   : {len(files)}")
        _print_tree(staging)

        if args.dry_run:
            print("\n--dry-run: staging dibuat, tidak ada yang diunggah.")
            return 0

        repo_type = {"model": "model", "dataset": "dataset", "space": "space"}[args.target]

        api.create_repo(
            repo_id=repo_id,
            repo_type=repo_type,
            private=args.private,
            exist_ok=True,
        )

        api.upload_folder(
            repo_id=repo_id,
            repo_type=repo_type,
            folder_path=str(staging),
            revision=args.revision,
            commit_message=f"Unggah {args.target} HR Intelligence System",
        )

    print(f"\nSelesai: https://huggingface.co/{repo_id}")
    return 0


if __name__ == "__main__":
    sys.exit(main())

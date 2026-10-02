from __future__ import annotations

import argparse
import sys

from src.config import DATA_DIR, MODELS_DIR
from src.registry.model_registry import ModelRegistry
from src.train.pipeline import TrainingConfig, train
from src.validation.model_validator import validate_record


def _build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="python -m src.train",
        description="Latih ulang model turnover secara reproducible.",
    )
    parser.add_argument("--version", default="v2", help="Versi artifact tujuan (default: v2).")
    parser.add_argument("--seed", type=int, default=42, help="Random state (default: 42).")
    parser.add_argument(
        "--data",
        default="hr_turnover.csv",
        help="Nama berkas dataset di folder data/ (default: hr_turnover.csv).",
    )
    parser.add_argument(
        "--no-validate",
        action="store_true",
        help="Lewati gate validasi setelah training.",
    )

    return parser


def main(argv: list[str] | None = None) -> int:
    args = _build_parser().parse_args(argv)

    config = TrainingConfig(seed=args.seed, data_file=args.data, version=args.version)

    print(f"Training turnover (versi={config.version}, seed={config.seed}, data={config.data_file})")
    hasil = train(config, data_dir=DATA_DIR)

    print(f"  baris           : {hasil['jumlah_baris']}")
    print(f"  model terpilih  : {hasil['champion']}")
    print(f"  threshold       : {hasil['threshold']}")
    print(f"  ROC-AUC (test)  : {hasil['metrics']['roc_auc']:.4f}")
    print(f"  PR-AUC  (test)  : {hasil['metrics']['pr_auc']:.4f}")
    print(f"  artifact        : {hasil['model_path']}")

    if args.no_validate:
        return 0

    # Gate: model yang baru dilatih harus lolos validasi yang sama seperti CI,
    # sehingga training yang menghasilkan model buruk langsung ketahuan.
    record = ModelRegistry(MODELS_DIR).get("turnover", config.version)
    validasi = validate_record(record)

    print()
    for check in validasi["checks"]:
        tanda = "ok" if check["lulus"] else "!!"
        print(f"  [{tanda}] {check['nama']}: {check['detail']}")

    if not validasi["lulus"]:
        print("\nValidasi GAGAL — model tidak layak dipakai.")
        return 1

    print("\nValidasi lulus — model layak dipakai.")

    return 0


if __name__ == "__main__":
    sys.exit(main())

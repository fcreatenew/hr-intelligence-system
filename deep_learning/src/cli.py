"""CLI eksperimen Deep Learning.

Contoh:
    python -m src.cli train              # latih MLP turnover + catat eksperimen
    python -m src.cli baseline           # jalankan baseline klasik
    python -m src.cli compare            # bandingkan MLP vs baseline
    python -m src.cli experiments        # tampilkan run terbaik
"""

from __future__ import annotations

import argparse
import json
import sys

from src.evaluation.baseline import baseline_turnover
from src.experiments.tracker import ExperimentTracker
from src.training.trainer import train_turnover_mlp


def _print_metrics(title: str, metrics: dict) -> None:
    print(f"\n{title}")
    for key, value in metrics.items():
        if isinstance(value, (int, float)):
            print(f"  {key:<12} {value:.4f}")


def cmd_train(args: argparse.Namespace) -> int:
    hidden = tuple(int(x) for x in args.hidden.split(",") if x.strip())
    result = train_turnover_mlp(
        hidden_sizes=hidden,
        epochs=args.epochs,
        learning_rate=args.lr,
        seed=args.seed,
    )

    _print_metrics("MLP turnover", result.metrics)

    path = ExperimentTracker().log(
        name="mlp_turnover",
        metrics=result.metrics,
        config=result.config,
        notes=f"threshold={result.threshold:.4f}",
    )
    print(f"\nEksperimen dicatat: {path}")

    return 0


def cmd_baseline(args: argparse.Namespace) -> int:
    hasil = baseline_turnover(seed=args.seed)

    _print_metrics("Baseline logistic regression", hasil["logistic_regression"])
    _print_metrics("Baseline majority class", hasil["majority_class"])

    ExperimentTracker().log(
        name="baseline_turnover",
        metrics=hasil["logistic_regression"],
        config={"seed": args.seed},
        notes=f"n_test={hasil['n_test']}, positif={hasil['positif_test']}",
    )

    return 0


def cmd_compare(args: argparse.Namespace) -> int:
    mlp = train_turnover_mlp(epochs=args.epochs, seed=args.seed)
    base = baseline_turnover(seed=args.seed)

    rows = {
        "MLP (deep learning)": mlp.metrics,
        "Logistic Regression": base["logistic_regression"],
        "Majority class": base["majority_class"],
    }

    print(f"\n{'Model':<22}{'ROC AUC':>10}{'F1':>10}{'Recall':>10}{'Accuracy':>10}")
    print("-" * 62)

    for name, metrics in rows.items():
        print(
            f"{name:<22}"
            f"{metrics['roc_auc']:>10.4f}"
            f"{metrics['f1']:>10.4f}"
            f"{metrics['recall']:>10.4f}"
            f"{metrics['accuracy']:>10.4f}"
        )

    terbaik = max(rows, key=lambda k: rows[k]["roc_auc"])
    print(f"\nROC AUC tertinggi: {terbaik}")

    return 0


def cmd_experiments(args: argparse.Namespace) -> int:
    tracker = ExperimentTracker()
    runs = tracker.list_runs()

    if not runs:
        print("Belum ada eksperimen tercatat.")
        return 0

    print(f"{len(runs)} eksperimen tercatat.\n")
    best = tracker.best("roc_auc")

    if best:
        print("Terbaik berdasarkan roc_auc:")
        print(json.dumps(best, indent=2, default=str))

    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Eksperimen Deep Learning HR")
    sub = parser.add_subparsers(dest="command", required=True)

    def add_common(p: argparse.ArgumentParser) -> None:
        p.add_argument("--epochs", type=int, default=200)
        p.add_argument("--seed", type=int, default=42)

    p_train = sub.add_parser("train", help="latih MLP turnover")
    p_train.add_argument("--hidden", default="64,32")
    p_train.add_argument("--lr", type=float, default=0.01)
    add_common(p_train)
    p_train.set_defaults(func=cmd_train)

    p_base = sub.add_parser("baseline", help="baseline klasik")
    add_common(p_base)
    p_base.set_defaults(func=cmd_baseline)

    p_cmp = sub.add_parser("compare", help="bandingkan MLP vs baseline")
    add_common(p_cmp)
    p_cmp.set_defaults(func=cmd_compare)

    p_exp = sub.add_parser("experiments", help="lihat run terbaik")
    p_exp.set_defaults(func=cmd_experiments)

    return parser


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    return args.func(args)


if __name__ == "__main__":
    sys.exit(main())

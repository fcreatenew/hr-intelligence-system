from __future__ import annotations

import argparse
import sys
from pathlib import Path

import pandas as pd

from src.lifecycle import (
    LifecycleError,
    active_version_info,
    list_versions,
    promote,
    resolve_active_version,
    rollback,
    run_retraining,
)
from src.lifecycle.retraining import RetrainingConfig
from src.registry.model_registry import ModelRegistry
from src.validation.model_validator import validate_all


def _cmd_validate(_: argparse.Namespace) -> int:
    """Validasi seluruh model terdaftar; exit code 1 bila ada yang gagal.

    Dipakai sebagai gate di CI sebelum model dianggap layak deploy.
    """
    registry = ModelRegistry()
    hasil = validate_all(registry)

    for item in hasil["hasil"]:
        status = "LULUS" if item["lulus"] else "GAGAL"
        print(f"[{status}] {item['use_case']} ({item['versi']})")
        for check in item["checks"]:
            tanda = "ok" if check["lulus"] else "!!"
            print(f"   {tanda} {check['nama']}: {check['detail']}")

    print()
    print(
        f"Total: {hasil['jumlah']} model, "
        f"semua lulus: {hasil['lulus_semua']}"
    )

    return 0 if hasil["lulus_semua"] else 1


def _cmd_versions(args: argparse.Namespace) -> int:
    registry = ModelRegistry()
    versi = list_versions("turnover", registry=registry)
    aktif = resolve_active_version("turnover", registry)

    print(f"Versi turnover tersedia : {', '.join(versi) or '-'}")
    print(f"Versi aktif             : {aktif}")

    info = active_version_info("turnover")
    if info.get("metrik"):
        nama, nilai = info["metrik"]
        print(f"Metrik versi aktif      : {nama}={nilai}")
    print(f"Dipin lewat file        : {'ya' if info.get('dipin') else 'tidak (default)'}")

    return 0


def _cmd_promote(args: argparse.Namespace) -> int:
    try:
        hasil = promote(args.version, reason=args.reason)
    except LifecycleError as exc:
        print(f"Gagal promosi: {exc}")
        return 1

    print(
        f"Promosi: {hasil['sebelumnya']} -> {hasil['aktif']} "
        f"({hasil['alasan']})"
    )
    return 0


def _cmd_rollback(args: argparse.Namespace) -> int:
    try:
        hasil = rollback(reason=args.reason)
    except LifecycleError as exc:
        print(f"Gagal rollback: {exc}")
        return 1

    print(
        f"Rollback: {hasil['sebelumnya']} -> {hasil['aktif']} "
        f"({hasil['alasan']})"
    )
    return 0


def _cmd_retrain(args: argparse.Namespace) -> int:
    current_path = Path(args.current) if args.current else None

    if current_path is not None:
        if not current_path.exists():
            print(f"Data produksi tidak ditemukan: {current_path}")
            return 1
        current = pd.read_csv(current_path)
    else:
        # Tanpa data produksi, pakai dataset training sebagai pembanding:
        # tidak ada drift (kecuali --force), sehingga aman dijalankan terjadwal.
        from src.config import DATA_DIR

        current = pd.read_csv(DATA_DIR / args.data)

    config = RetrainingConfig(
        seed=args.seed,
        data_file=args.data,
        force=args.force,
        min_metric_delta=args.min_delta,
    )

    hasil = run_retraining(config, current=current)

    print(f"Status retraining : {hasil['status']}")
    print(f"Drift maksimum    : {hasil['drift']['max_psi']} ({hasil['drift']['level']})")
    if hasil.get("versi_baru"):
        print(f"Versi kandidat    : {hasil['versi_baru']}")
    print(f"Versi aktif       : {hasil.get('versi_aktif', resolve_active_version('turnover'))}")
    print(f"Alasan            : {hasil['alasan']}")

    if hasil["status"] in {"gagal_gate", "ditolak"}:
        return 1

    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="python -m src.cli",
        description="Validasi model, siklus hidup versi, dan retraining otomatis.",
    )
    sub = parser.add_subparsers(dest="command")

    sub.add_parser("validate", help="validasi seluruh model (default)")

    sub.add_parser("versions", help="daftar versi turnover + versi aktif")

    promote_p = sub.add_parser("promote", help="pin versi turnover sebagai aktif")
    promote_p.add_argument("version", help="versi tujuan, mis. v2_mitigated")
    promote_p.add_argument("--reason", default="promosi manual")

    rollback_p = sub.add_parser("rollback", help="kembali ke versi sebelumnya")
    rollback_p.add_argument("--reason", default="rollback manual")

    retrain_p = sub.add_parser("retrain", help="retraining turnover berbasis drift")
    retrain_p.add_argument("--current", help="CSV data produksi pembanding drift")
    retrain_p.add_argument("--data", default="hr_turnover.csv", help="berkas dataset training")
    retrain_p.add_argument("--seed", type=int, default=42)
    retrain_p.add_argument("--force", action="store_true", help="latih walau drift tidak terdeteksi")
    retrain_p.add_argument(
        "--min-delta",
        type=float,
        default=0.0,
        dest="min_delta",
        help="selisih metrik minimal untuk mempromosikan kandidat",
    )

    return parser


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    command = args.command or "validate"

    handlers = {
        "validate": _cmd_validate,
        "versions": _cmd_versions,
        "promote": _cmd_promote,
        "rollback": _cmd_rollback,
        "retrain": _cmd_retrain,
    }

    return handlers[command](args)


if __name__ == "__main__":
    sys.exit(main())

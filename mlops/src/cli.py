from __future__ import annotations

import argparse
import sys
from pathlib import Path

import pandas as pd

from src.lifecycle import (
    LifecycleError,
    PerformanceMonitorConfig,
    active_version_info,
    list_versions,
    promote,
    reference_features,
    resolve_active_version,
    rollback,
    run_performance_monitor,
    run_retraining,
)
from src.lifecycle.retraining import RetrainingConfig
from src.monitoring.alerting import AlertDispatcher, build_alert_sender
from src.monitoring.drift_monitor import DriftMonitor
from src.monitoring.fairness import FairnessConfig, run_fairness_audit
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


def _cmd_monitor(args: argparse.Namespace) -> int:
    """Evaluasi drift dan kirim alert bila terdeteksi."""
    from src.config import DATA_DIR

    current_path = Path(args.current) if args.current else DATA_DIR / args.data
    current = pd.read_csv(current_path)

    config = RetrainingConfig(
        data_file=args.data,
        drift_warning=args.warning,
        drift_critical=args.critical,
    )
    referensi = reference_features(config, DATA_DIR)

    monitor = DriftMonitor(warning=args.warning, critical=args.critical)
    report = monitor.evaluate("turnover", referensi, current)

    dispatcher = AlertDispatcher(sender=build_alert_sender(args.webhook))
    hasil = dispatcher.dispatch(report)

    print(f"Drift maksimum : {report.max_psi} ({report.level})")
    print(f"Drift terdeteksi: {report.drift_terdeteksi}")
    print(f"Alert terkirim : {hasil['terkirim']} ({hasil.get('alasan', 'ok')})")

    return 0


def _cmd_fairness(args: argparse.Namespace) -> int:
    """Audit fairness model turnover per atribut pelindung."""
    config = FairnessConfig(
        version=args.version,
        data_file=args.data,
        threshold=args.threshold,
        min_group_size=args.min_group_size,
        impact_ratio_threshold=args.impact_ratio,
    )

    hasil = run_fairness_audit(config)
    ringkasan = hasil["ringkasan"]

    print(f"Versi model    : {hasil['versi']} ({hasil['model']})")
    print(f"Threshold      : {hasil['threshold']}")
    print(f"Sampel         : {hasil['samples']}")

    for atribut, audit in hasil["atribut"].items():
        ratio = audit["impact_ratio"]
        ratio_txt = "-" if ratio is None else f"{ratio}"
        print(
            f"  {atribut:<14} DP diff={audit['demographic_parity_diff']} "
            f"impact ratio={ratio_txt} EO diff={audit['equal_opportunity_diff']}"
        )

    print(f"Status         : {'LOLOS' if ringkasan['lolos'] else 'PELANGGARAN'}")
    for pesan in ringkasan["pelanggaran"]:
        print(f"  - {pesan}")

    return 0 if ringkasan["lolos"] else 1


def _cmd_performance(args: argparse.Namespace) -> int:
    """Evaluasi performa produksi; rollback otomatis bila turun."""
    current_path = Path(args.current)

    if not current_path.exists():
        print(f"Data umpan balik tidak ditemukan: {current_path}")
        return 1

    current = pd.read_csv(current_path)
    config = PerformanceMonitorConfig(tolerance=args.tolerance, min_samples=args.min_samples)

    hasil = run_performance_monitor(current, config=config)
    performa = hasil["performa"]

    print(f"Status performa   : {performa['status']}")
    print(f"ROC AUC produksi  : {performa['roc_auc_produksi']}")
    print(f"ROC AUC referensi : {performa['roc_auc_referensi']}")
    print(f"Versi aktif       : {hasil['versi_aktif']}")
    if hasil.get("rollback"):
        print(f"Rollback ke       : {hasil['rollback']['aktif']}")
    if hasil.get("alasan"):
        print(f"Alasan            : {hasil['alasan']}")

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

    monitor_p = sub.add_parser("monitor", help="evaluasi drift + kirim alert webhook")
    monitor_p.add_argument("--current", help="CSV data produksi (default: dataset training)")
    monitor_p.add_argument("--data", default="hr_turnover.csv")
    monitor_p.add_argument("--webhook", default=None, help="URL webhook alert (default dari env)")
    monitor_p.add_argument("--warning", type=float, default=0.1)
    monitor_p.add_argument("--critical", type=float, default=0.2)

    perf_p = sub.add_parser("performance", help="evaluasi performa produksi + rollback otomatis")
    perf_p.add_argument("--current", required=True, help="CSV umpan balik berlabel")
    perf_p.add_argument("--tolerance", type=float, default=0.05)
    perf_p.add_argument("--min-samples", type=int, default=30, dest="min_samples")

    fair_p = sub.add_parser("fairness", help="audit fairness turnover per atribut pelindung")
    fair_p.add_argument("--version", help="versi turnover (default: versi aktif)")
    fair_p.add_argument("--data", default="hr_turnover.csv", help="berkas dataset")
    fair_p.add_argument("--threshold", type=float, default=None, help="ambang keputusan")
    fair_p.add_argument("--min-group-size", type=int, default=20, dest="min_group_size")
    fair_p.add_argument(
        "--impact-ratio",
        type=float,
        default=0.8,
        dest="impact_ratio",
        help="ambang four-fifths (default 0.8)",
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
        "monitor": _cmd_monitor,
        "performance": _cmd_performance,
        "fairness": _cmd_fairness,
    }

    return handlers[command](args)


if __name__ == "__main__":
    sys.exit(main())

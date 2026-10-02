from __future__ import annotations

import argparse
import json

from src.config import ANOMALY_THRESHOLD, FORECAST_HORIZON
from src.ingestion.loader import load_payroll
from src.processing.transform import clean, enrich
from src.reports.report import build_report, report_to_markdown, save_report


def main() -> None:
    parser = argparse.ArgumentParser(description="Payroll Intelligence CLI")
    parser.add_argument(
        "command",
        choices=["summary", "anomalies", "forecast", "report"],
        help="Perintah yang dijalankan.",
    )
    parser.add_argument("--horizon", type=int, default=FORECAST_HORIZON)
    parser.add_argument("--threshold", type=float, default=ANOMALY_THRESHOLD)

    args = parser.parse_args()

    df = clean(enrich(load_payroll()))

    if args.command == "report":
        report = build_report(df, horizon=args.horizon)
        json_path = save_report(report)
        md_path = json_path.with_suffix(".md")
        md_path.write_text(report_to_markdown(report), encoding="utf-8")
        print(f"Laporan disimpan: {json_path}")
        print(f"Markdown: {md_path}")
        return

    report = build_report(df, horizon=args.horizon)

    if args.command == "summary":
        print(json.dumps(report["ringkasan"], indent=2, ensure_ascii=False))
    elif args.command == "anomalies":
        print(json.dumps(report["anomali"], indent=2, ensure_ascii=False, default=str))
    elif args.command == "forecast":
        print(json.dumps(report["forecast"], indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()

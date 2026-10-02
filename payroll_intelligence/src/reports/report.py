from __future__ import annotations

import json
from pathlib import Path

import pandas as pd

from src.analytics import anomaly, payroll_analytics
from src.calculations import payroll as calc
from src.config import ANOMALY_THRESHOLD, FORECAST_HORIZON, REPORTS_DIR
from src.forecasting.service import (
    forecast_overtime_cost,
    forecast_total_payroll,
)


def build_report(df: pd.DataFrame, horizon: int = FORECAST_HORIZON) -> dict:
    """Susun laporan payroll lengkap: ringkasan, analitik, anomali, forecast."""
    anomalies = anomaly.detect(df, threshold=ANOMALY_THRESHOLD)
    flagged = anomalies[anomalies["is_anomali"]]

    return {
        "ringkasan": payroll_analytics.summary(df),
        "komponen_agregat": calc.komponen_agregat(df),
        "per_departemen": payroll_analytics.by_department(df),
        "per_periode": payroll_analytics.by_period(df),
        "per_level": payroll_analytics.by_level(df),
        "lembur_tertinggi": payroll_analytics.top_overtime(df),
        "distribusi_gaji": payroll_analytics.salary_distribution(df),
        "anomali": {
            "threshold": ANOMALY_THRESHOLD,
            "jumlah_anomali": int(len(flagged)),
            "contoh": flagged.head(20).to_dict(orient="records"),
        },
        "forecast": {
            "total_payroll": forecast_total_payroll(
                df, horizon=horizon
            ).to_dict(),
            "biaya_lembur": forecast_overtime_cost(df, horizon=horizon).to_dict(),
        },
    }


def save_report(report: dict, name: str = "payroll_report") -> Path:
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    path = REPORTS_DIR / f"{name}.json"
    path.write_text(
        json.dumps(report, indent=2, ensure_ascii=False, default=str),
        encoding="utf-8",
    )

    return path


def report_to_markdown(report: dict) -> str:
    ringkasan = report["ringkasan"]
    lines = [
        "# Laporan Payroll Intelligence",
        "",
        "## Ringkasan",
        "",
        f"- Jumlah karyawan: **{ringkasan['jumlah_karyawan']}**",
        f"- Periode: **{ringkasan['periode_awal']}** s.d. **{ringkasan['periode_akhir']}**",
        f"- Total payroll: **{ringkasan['total_payroll']:,.0f}**",
        f"- Rata-rata gaji: **{ringkasan['rata_rata_gaji']:,.0f}**",
        f"- Total biaya lembur: **{ringkasan['total_biaya_lembur']:,.0f}**",
        "",
        "## Payroll per Departemen",
        "",
        "| Departemen | Karyawan | Total Payroll | Rata-rata Gaji |",
        "| --- | ---: | ---: | ---: |",
    ]

    for row in report["per_departemen"]:
        lines.append(
            f"| {row['departemen']} | {row['jumlah_karyawan']} | "
            f"{row['total_payroll']:,.0f} | {row['rata_rata_gaji']:,.0f} |"
        )

    anomali = report["anomali"]
    lines += [
        "",
        "## Anomali Payroll",
        "",
        f"- Ambang skor: **{anomali['threshold']}**",
        f"- Jumlah terdeteksi: **{anomali['jumlah_anomali']}**",
        "",
        "## Forecast Total Payroll",
        "",
        "| Periode | Nilai |",
        "| --- | ---: |",
    ]

    for periode, nilai in zip(
        report["forecast"]["total_payroll"]["periode"],
        report["forecast"]["total_payroll"]["nilai"],
    ):
        lines.append(f"| {periode} | {nilai:,.0f} |")

    lines += [
        "",
        "> Catatan: temuan anomali adalah sinyal untuk verifikasi tim payroll, "
        "bukan bukti kesalahan atau kecurangan.",
        "",
    ]

    return "\n".join(lines)

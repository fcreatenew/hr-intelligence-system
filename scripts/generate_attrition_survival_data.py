"""Bangkitkan dataset sintetis survival attrition (data/hr_attrition_survival.csv).

Jalankan dari root repo:  python scripts/generate_attrition_survival_data.py

Data anonim/sintetis untuk eksperimen ML. Tidak merepresentasikan orang nyata.

Kolom:
- tenure_bulan    : lama observasi (bulan)
- event_keluar    : 1 = keluar (event), 0 = masih bekerja / disensor
"""
from pathlib import Path

import numpy as np
import pandas as pd

SEED = 42
N = 1200
MAX_TENURE = 60  # bulan observasi
rng = np.random.default_rng(SEED)

DEPARTEMEN = ["Finance", "HR", "IT", "Legal", "Marketing", "Operations", "Sales"]
PENDIDIKAN = ["SMA", "Diploma", "S1", "S2"]


def clip(a, lo, hi):
    return np.clip(a, lo, hi)


def main() -> None:
    usia = rng.integers(21, 57, N)
    departemen = rng.choice(DEPARTEMEN, N)
    pendidikan = rng.choice(PENDIDIKAN, N, p=[0.18, 0.22, 0.48, 0.12])
    gaji = clip(rng.normal(9_000_000, 3_000_000, N), 4_000_000, 30_000_000).round(-3)
    lembur = clip(rng.normal(18, 10, N), 0, 60).round(1)
    kepuasan = clip(rng.normal(3.4, 0.8, N), 1, 5).round(2)
    promosi_terakhir = clip(rng.normal(20, 14, N), 0, 90).round(1)  # bulan sejak promosi
    jarak = clip(rng.normal(12, 9, N), 0.5, 70).round(1)

    dept_eff = {"Sales": 0.25, "IT": 0.15, "Marketing": 0.10, "Operations": 0.05,
                "Finance": -0.05, "HR": -0.08, "Legal": -0.10}
    # Risiko keluar bulanan (hazard) — makin besar makin cepat keluar.
    hazard = (
        0.030
        + 0.022 * (1 - (kepuasan - 1) / 4)          # kepuasan rendah -> risiko tinggi
        + 0.015 * (lembur / 60)
        + 0.012 * (promosi_terakhir / 90)           # lama tanpa promosi
        + 0.008 * (jarak / 70)
        + 0.006 * (usia - 21) / 36 * 0
        + np.array([dept_eff[d] for d in departemen])
    )
    hazard = clip(hazard, 0.005, 0.25)

    tenure_true = rng.exponential(1.0 / hazard).round().astype(int)
    tenure_true = np.maximum(tenure_true, 1)

    event = (tenure_true <= MAX_TENURE).astype(int)
    tenure = np.where(event == 1, tenure_true, MAX_TENURE)

    df = pd.DataFrame({
        "employee_id": [f"EMP{i:05d}" for i in range(1, N + 1)],
        "usia": usia,
        "departemen": departemen,
        "pendidikan": pendidikan,
        "gaji_bulanan": gaji.astype(int),
        "jam_lembur_bulanan": lembur,
        "kepuasan_kerja": kepuasan,
        "bulan_sejak_promosi": promosi_terakhir,
        "jarak_km": jarak,
        "tenure_bulan": tenure,
        "event_keluar": event,
    })

    out = Path("data/hr_attrition_survival.csv")
    out.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(out, index=False)
    print(f"ditulis: {out} | baris: {len(df)} | kolom: {df.shape[1]}")
    print(f"event keluar: {int(df['event_keluar'].sum())} ({df['event_keluar'].mean():.1%}) "
          f"| disensor: {int((1 - df['event_keluar']).sum())}")
    print(f"median tenure (bulan): {df['tenure_bulan'].median():.0f}")
    print(df.head(3).to_string(index=False))


if __name__ == "__main__":
    main()

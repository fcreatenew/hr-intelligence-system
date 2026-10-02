"""Bangkitkan dataset sintetis payroll dengan anomali (data/hr_payroll.csv).

Jalankan dari root repo:  python scripts/generate_payroll_data.py

Data anonim/sintetis untuk eksperimen ML. Tidak merepresentasikan orang nyata.
Kolom `is_anomaly` dan `jenis_anomali` adalah label buatan untuk evaluasi
deteksi anomali (di dunia nyata label ini biasanya tidak tersedia).
"""
from pathlib import Path

import numpy as np
import pandas as pd

SEED = 42
N_EMP = 600
BULAN = ["2025-01", "2025-02", "2025-03"]
rng = np.random.default_rng(SEED)

DEPARTEMEN = ["Finance", "HR", "IT", "Legal", "Marketing", "Operations", "Sales"]
LEVEL_GAJI = {"Staff": 5_000_000, "Senior": 9_000_000, "Lead": 14_000_000, "Manager": 22_000_000}


def clip(a, lo, hi):
    return np.clip(a, lo, hi)


def main() -> None:
    rows = []
    emp_level = {f"EMP{i:05d}": rng.choice(list(LEVEL_GAJI)) for i in range(1, N_EMP + 1)}
    emp_dept = {f"EMP{i:05d}": rng.choice(DEPARTEMEN) for i in range(1, N_EMP + 1)}

    for emp, level in emp_level.items():
        base = LEVEL_GAJI[level] * rng.normal(1.0, 0.12)
        for bulan in BULAN:
            gaji_pokok = round(float(clip(base * rng.normal(1.0, 0.02), 3_000_000, 40_000_000)), -3)
            tunjangan = round(float(gaji_pokok * clip(rng.normal(0.20, 0.05), 0.05, 0.45)), -3)
            lembur_jam = int(clip(rng.poisson(8), 0, 60))
            tarif_lembur = round(gaji_pokok / 173 * 1.5, -2)
            potongan = round(float(gaji_pokok * clip(rng.normal(0.08, 0.02), 0.02, 0.20)), -3)
            rows.append({
                "employee_id": emp, "periode": bulan, "departemen": emp_dept[emp],
                "level": level, "gaji_pokok": gaji_pokok, "tunjangan": tunjangan,
                "lembur_jam": lembur_jam, "tarif_lembur": tarif_lembur, "potongan": potongan,
            })

    df = pd.DataFrame(rows)
    df["total_gaji"] = (df["gaji_pokok"] + df["tunjangan"]
                        + df["lembur_jam"] * df["tarif_lembur"] - df["potongan"])
    df["is_anomaly"] = 0
    df["jenis_anomali"] = ""

    # --- Sisipkan anomali (~4%) ---
    n = len(df)
    idx_all = rng.permutation(n)
    n_anom = int(round(0.04 * n))
    dipilih = idx_all[:n_anom]

    jenis = rng.choice(
        ["gaji_typo", "tunjangan_negatif", "potongan_besar", "lembur_ekstrem", "total_tidak_konsisten"],
        n_anom, p=[0.28, 0.18, 0.16, 0.18, 0.20])

    for i, j in enumerate(dipilih):
        k = jenis[i]
        if k == "gaji_typo":
            df.loc[j, "gaji_pokok"] = df.loc[j, "gaji_pokok"] * rng.choice([8, 10, 12])
        elif k == "tunjangan_negatif":
            df.loc[j, "tunjangan"] = -abs(df.loc[j, "tunjangan"]) * rng.uniform(0.5, 1.5)
        elif k == "potongan_besar":
            df.loc[j, "potongan"] = df.loc[j, "gaji_pokok"] * rng.uniform(0.9, 1.6)
        elif k == "lembur_ekstrem":
            df.loc[j, "lembur_jam"] = int(rng.integers(200, 400))
        else:  # total_tidak_konsisten
            df.loc[j, "total_gaji"] = df.loc[j, "total_gaji"] * rng.uniform(0.4, 1.8)
        df.loc[j, "is_anomaly"] = 1
        df.loc[j, "jenis_anomali"] = k

    # Hitung ulang total untuk anomali komponen (agar inkonsistensi benar-benar ada)
    mask_komponen = df["jenis_anomali"].isin(["gaji_typo", "tunjangan_negatif", "potongan_besar", "lembur_ekstrem"])
    df.loc[mask_komponen, "total_gaji"] = (
        df.loc[mask_komponen, "gaji_pokok"] + df.loc[mask_komponen, "tunjangan"]
        + df.loc[mask_komponen, "lembur_jam"] * df.loc[mask_komponen, "tarif_lembur"]
        - df.loc[mask_komponen, "potongan"])

    df = df.sample(frac=1.0, random_state=SEED).reset_index(drop=True)
    out = Path("data/hr_payroll.csv")
    out.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(out, index=False)
    print(f"ditulis: {out} | baris: {len(df)} | kolom: {df.shape[1]}")
    print(f"anomali: {int(df['is_anomaly'].sum())} ({df['is_anomaly'].mean():.2%})")
    print(df["jenis_anomali"].value_counts().to_string())


if __name__ == "__main__":
    main()

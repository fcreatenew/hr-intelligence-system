"""Bangkitkan dataset sintetis kinerja karyawan (data/hr_performance.csv).

Jalankan dari root repo:  python scripts/generate_performance_data.py

Data anonim/sintetis untuk eksperimen ML. Tidak merepresentasikan orang nyata.
Skema kolom didokumentasikan di data/README.md.
"""
from pathlib import Path

import numpy as np
import pandas as pd

SEED = 42
N = 1400
rng = np.random.default_rng(SEED)

DEPARTEMEN = ["Finance", "HR", "IT", "Legal", "Marketing", "Operations", "Sales"]
PENDIDIKAN = ["SMA", "Diploma", "S1", "S2"]
PENDIDIKAN_BOBOT = {"SMA": 0.0, "Diploma": 0.4, "S1": 0.8, "S2": 1.1}


def clip(a, lo, hi):
    return np.clip(a, lo, hi)


def main() -> None:
    usia = rng.integers(20, 58, N)
    masa_kerja = clip(rng.normal(46, 34, N), 1, 360).round().astype(int)
    departemen = rng.choice(DEPARTEMEN, N)
    pendidikan = rng.choice(PENDIDIKAN, N, p=[0.18, 0.22, 0.48, 0.12])
    jam_lembur = clip(rng.normal(18, 11, N), 0, 70).round(1)
    training = clip(rng.poisson(3.2, N), 0, 12)
    proyek = clip(rng.poisson(5.5, N), 0, 20)
    skor_360 = clip(rng.normal(3.6, 0.7, N), 1, 5).round(2)
    absen = clip(rng.poisson(6, N), 0, 40)

    # Sinyal laten (skala 0-1) + sedikit efek departemen
    dept_eff = {
        "Sales": 0.15, "IT": 0.10, "Finance": 0.02, "Marketing": 0.03,
        "Operations": -0.02, "HR": 0.0, "Legal": 0.05,
    }
    sinyal = (
        0.34 * (skor_360 - 1) / 4
        + 0.16 * clip(proyek / 14, 0, 1)
        + 0.12 * clip(training / 9, 0, 1)
        + 0.10 * np.array([PENDIDIKAN_BOBOT[p] for p in pendidikan]) / 1.1
        - 0.12 * clip(absen / 24, 0, 1)
        - 0.06 * clip((jam_lembur - 20) / 45, 0, 1) ** 2
        + 0.06 * clip(masa_kerja / 200, 0, 1)
        + np.array([dept_eff[d] for d in departemen])
    )
    skor = clip(sinyal + rng.normal(0, 0.09, N), 0, 1)
    # Rating 1..5 dari kuantil skor agar distribusinya realistis (tidak menumpuk).
    q = np.quantile(skor, [0.15, 0.40, 0.70, 0.90])
    rating = np.digitize(skor, q) + 1  # 1..5
    kategori = pd.cut(rating, bins=[0, 2, 4, 5],
                      labels=["Rendah", "Sedang", "Tinggi"]).astype(str)

    df = pd.DataFrame({
        "employee_id": [f"EMP{i:05d}" for i in range(1, N + 1)],
        "usia": usia,
        "masa_kerja_bulan": masa_kerja,
        "departemen": departemen,
        "pendidikan": pendidikan,
        "jam_lembur_bulanan": jam_lembur,
        "jumlah_training_setahun": training,
        "proyek_selesai": proyek,
        "skor_umpan_balik_360": skor_360,
        "hari_absen_setahun": absen,
        "rating_kinerja": rating,
        "kategori_kinerja": kategori,
    })

    out = Path("data/hr_performance.csv")
    out.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(out, index=False)
    print(f"ditulis: {out} | baris: {len(df)} | kolom: {df.shape[1]}")
    print("distribusi kategori:")
    print(df["kategori_kinerja"].value_counts().to_string())
    print(df.head(3).to_string(index=False))


if __name__ == "__main__":
    main()

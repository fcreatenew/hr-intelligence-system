"""Bangkitkan dataset sintetis absensi karyawan (data/hr_absenteeism.csv).

Jalankan dari root repo:  python scripts/generate_absenteeism_data.py

Data anonim/sintetis untuk eksperimen ML. Tidak merepresentasikan orang nyata.
Skema kolom didokumentasikan di data/README.md.
"""
from pathlib import Path

import numpy as np
import pandas as pd

SEED = 42
rng = np.random.default_rng(SEED)
N = 1200

DEPARTEMEN = ["Sales", "Marketing", "HR", "Operations", "IT", "Finance", "Legal"]
STATUS_KERJA = ["Tetap", "Kontrak", "Freelance"]
PENDIDIKAN = ["SMA", "Diploma", "S1", "S2"]
ALASAN = [
    "sakit", "izin_pribadi", "urusan_keluarga", "kesehatan",
    "transportasi", "lainnya",
]

departemen = rng.choice(DEPARTEMEN, N)
status_kerja = rng.choice(STATUS_KERJA, N, p=[0.55, 0.30, 0.15])
pendidikan = rng.choice(PENDIDIKAN, N, p=[0.34, 0.23, 0.35, 0.08])

usia = np.clip(rng.normal(34, 8, N), 19, 60).round().astype(int)
masa_kerja_bulan = np.clip(rng.gamma(3, 18, N), 1, 480).round().astype(int)
jarak_km = np.clip(rng.gamma(2.2, 6.5, N), 0.5, 80).round(1)
beban_kerja = np.clip(rng.normal(70, 15, N), 20, 130).round(1)
lembur_jam = np.clip(rng.gamma(2.0, 8, N), 0, 120).round(1)
kepuasan = np.clip(rng.normal(3.6, 0.9, N), 1, 5).round(2)
riwayat_absen_90h = np.clip(rng.poisson(1.6, N), 0, 12)

# Skor risiko laten -> jumlah hari absen (gamma, dengan noise), lalu binarisasi.
skor = (
    0.06 * jarak_km
    + 0.03 * lembur_jam
    - 0.55 * kepuasan
    + 0.35 * riwayat_absen_90h
    + 0.02 * (beban_kerja - 70)
)
skor = (skor - skor.mean()) / skor.std()
mu = np.clip(6 + 2.5 * skor, 0.5, 40)
hari_absen = np.clip(rng.gamma(4, mu / 4), 0, 60).round().astype(int)

# target: absen tinggi (>= 10 hari dalam setahun) -> masalah kehadiran
AMBANG_ABSEN = 10
target = (hari_absen >= AMBANG_ABSEN).astype(int)

print("rasio absen tinggi:", round(float(target.mean()), 3))

employee_id = [f"EMP{i:04d}" for i in range(1, N + 1)]
tanggal_masuk = pd.to_datetime("2025-01-01") - pd.to_timedelta(
    masa_kerja_bulan * 30, unit="D"
)

alasan = rng.choice(ALASAN, N, p=[0.42, 0.18, 0.16, 0.10, 0.08, 0.06])

df = pd.DataFrame({
    "employee_id": employee_id,
    "usia": usia,
    "masa_kerja_bulan": masa_kerja_bulan,
    "departemen": departemen,
    "status_kerja": status_kerja,
    "pendidikan": pendidikan,
    "jarak_km": jarak_km,
    "beban_kerja": beban_kerja,
    "lembur_jam_bulanan": lembur_jam,
    "kepuasan_kerja": kepuasan,
    "riwayat_absen_90hari": riwayat_absen_90h,
    "alasan_dominan": alasan,
    "hari_absen_setahun": hari_absen,
    "tanggal_masuk": tanggal_masuk.strftime("%Y-%m-%d"),
    "target_absen_tinggi": target,
})

out = Path("data/hr_absenteeism.csv")
df.to_csv(out, index=False)
print("ditulis:", out, "| baris:", len(df), "| kolom:", len(df.columns))
print(df.head(3).to_string())

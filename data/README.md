# Data

Dataset untuk eksperimen Machine Learning HR Intelligence System.

```text
data/
├── hr_turnover.csv             # dataset turnover (anonim)
├── hr_absenteeism.csv          # dataset absensi (SINTETIS)
├── hr_performance.csv          # dataset kinerja (SINTETIS)
├── hr_payroll.csv              # dataset payroll + anomali (SINTETIS)
├── hr_attrition_survival.csv   # dataset survival attrition (SINTETIS)
└── README.md
```

> **Semua data di sini anonim/sintetis.** Jangan menaruh data pribadi karyawan
> asli di repository (lihat `USE_CASES.md` #16 dan UU PDP No. 27/2022).

---

## `hr_turnover.csv`

Dataset turnover karyawan (1.000 baris) yang dipakai untuk melatih model
turnover v1 & v2.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `employee_id` | string | ID anonim |
| `usia` | int | usia karyawan (tahun) |
| `masa_kerja_bulan` | int | masa kerja (bulan) |
| `jam_lembur_bulanan` | float | rata-rata jam lembur per bulan |
| `gaji_bulanan` | int | gaji bulanan (Rupiah) |
| `tanggal_masuk` | date | tanggal bergabung |
| `tanggal_promosi` | date | tanggal promosi terakhir (bisa kosong) |
| `departemen` | kategori | Finance, HR, IT, Legal, Marketing, Operations, Sales |
| `status_kerja` | kategori | Tetap, Kontrak, Freelance |
| `pendidikan` | kategori | SMA, Diploma, S1, S2 |
| `target_turnover` | 0/1 | 1 = keluar |

Fitur turunan dihitung oleh `buat_fitur()` (kontrak sama di notebook training
dan `hr_intelligence_api`): `log_gaji`, `usia_saat_masuk`, `ada_promosi`,
`bulan_sejak_promosi`, `bulan_hingga_promosi`.

---

## `hr_absenteeism.csv` (SINTETIS)

Dataset absensi **buatan** (1.200 baris) untuk use case #3 — prediksi absen
tinggi. Dibangkitkan oleh `scripts/generate_absenteeism_data.py`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `employee_id` | string | ID anonim |
| `usia` | int | usia karyawan |
| `masa_kerja_bulan` | int | masa kerja (bulan) |
| `departemen` | kategori | 7 departemen |
| `status_kerja` | kategori | Tetap, Kontrak, Freelance |
| `pendidikan` | kategori | SMA, Diploma, S1, S2 |
| `jarak_km` | float | jarak rumah–kantor (km) |
| `beban_kerja` | float | indeks beban kerja (0–130) |
| `lembur_jam_bulanan` | float | rata-rata jam lembur per bulan |
| `kepuasan_kerja` | float | skor kepuasan 1–5 |
| `riwayat_absen_90hari` | int | jumlah absen 90 hari terakhir |
| `alasan_dominan` | kategori | alasan absen yang dominan |
| `hari_absen_setahun` | int | total hari absen setahun |
| `tanggal_masuk` | date | tanggal bergabung |
| `target_absen_tinggi` | 0/1 | 1 = absen ≥ 10 hari setahun |

**Cara membangkitkan ulang** (deterministik, seed = 42):

```bash
python scripts/generate_absenteeism_data.py
```

Kolom yang **tidak boleh** dipakai sebagai fitur (kebocoran): `employee_id`,
`hari_absen_setahun`, `alasan_dominan`.

---

## `hr_performance.csv` (SINTETIS)

1.400 baris. Dibangkitkan oleh `scripts/generate_performance_data.py`.
Target: `kategori_kinerja` (Rendah / Sedang / Tinggi), berasal dari
`rating_kinerja` **ordinal** 1–5.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `usia`, `masa_kerja_bulan` | int | usia & masa kerja |
| `departemen`, `pendidikan` | kategori | sama dengan dataset lain |
| `jam_lembur_bulanan` | float | jam lembur per bulan |
| `jumlah_training_setahun` | int | jumlah training setahun |
| `proyek_selesai` | int | proyek yang diselesaikan |
| `skor_umpan_balik_360` | float | skor feedback 360 (1–5) |
| `hari_absen_setahun` | int | hari absen setahun |
| `rating_kinerja` | int | 1–5 (ordinal) — **jangan dipakai sebagai fitur** |
| `kategori_kinerja` | kategori | target |

> Karena target **ordinal**, metrik utama: macro-F1 dan **MAE ordinal**, bukan
> akurasi saja. Jangan pakai untuk keputusan PHK.

---

## `hr_payroll.csv` (SINTETIS)

1.800 baris (600 karyawan × 3 periode). Dibangkitkan oleh
`scripts/generate_payroll_data.py`.

| Kolom | Keterangan |
|---|---|
| `employee_id`, `periode`, `departemen`, `level` | identitas & konteks |
| `gaji_pokok`, `tunjangan`, `potongan` | komponen gaji |
| `lembur_jam`, `tarif_lembur` | komponen lembur |
| `total_gaji` | total (dihitung) |
| `is_anomaly` | 1 = anomali (label buatan, ~4%) |
| `jenis_anomali` | jenis anomali (label buatan) — **jangan dipakai sebagai fitur** |

Jenis anomali yang disisipkan: `gaji_typo`, `tunjangan_negatif`,
`potongan_besar`, `lembur_ekstrem`, `total_tidak_konsisten`.

> Label buatan hanya untuk evaluasi. Di dunia nyata label jarang tersedia →
> pakai pendekatan unsupervised.

---

## `hr_attrition_survival.csv` (SINTETIS)

1.200 baris. Dibangkitkan oleh `scripts/generate_attrition_survival_data.py`.
Format survival (durasi + event/censoring).

| Kolom | Keterangan |
|---|---|
| `usia`, `departemen`, `pendidikan` | demografi |
| `gaji_bulanan`, `jam_lembur_bulanan`, `kepuasan_kerja` | kondisi kerja |
| `bulan_sejak_promosi`, `jarak_km` | faktor retensi |
| `tenure_bulan` | **durasi** observasi (bulan) |
| `event_keluar` | **event**: 1 = keluar, 0 = disensor (masih bekerja) |

> Data survival tidak boleh dipakai dengan classifier biasa tanpa
> memperhitungkan censoring — gunakan Kaplan–Meier / Cox PH.

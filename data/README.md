# Data

Dataset untuk eksperimen Machine Learning HR Intelligence System.

```text
data/
├── hr_turnover.csv        # dataset turnover (anonim)
├── hr_absenteeism.csv     # dataset absensi (SINTETIS)
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

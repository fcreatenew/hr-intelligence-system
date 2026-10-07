---
license: mit
language:
  - id
tags:
  - tabular
  - human-resources
  - synthetic
  - hr-analytics
  - fairness
configs:
  - config_name: turnover
    data_files: hr_turnover.csv
  - config_name: absenteeism
    data_files: hr_absenteeism.csv
  - config_name: performance
    data_files: hr_performance.csv
  - config_name: payroll
    data_files: hr_payroll.csv
  - config_name: attrition_survival
    data_files: hr_attrition_survival.csv
---

# HR Intelligence — Dataset Sintetis

Lima dataset untuk melatih model HR Intelligence System. **Seluruh data
anonim/sintetis** — tidak ada data pribadi karyawan asli (UU PDP No. 27/2022).

| File | Baris | Sifat | Target |
|---|---|---|---|
| `hr_turnover.csv` | 1.000 | anonim | `target_turnover` (0/1) |
| `hr_absenteeism.csv` | 1.200 | sintetis | absen tinggi |
| `hr_performance.csv` | — | sintetis | kategori kinerja |
| `hr_payroll.csv` | — | sintetis | anomali (unsupervised) |
| `hr_attrition_survival.csv` | — | sintetis | durasi + event |

## Skema utama (turnover)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `employee_id` | string | ID anonim |
| `usia` | int | usia (tahun) |
| `masa_kerja_bulan` | int | masa kerja (bulan) |
| `jam_lembur_bulanan` | float | rata-rata jam lembur/bulan |
| `gaji_bulanan` | int | gaji bulanan (Rupiah) |
| `tanggal_masuk` | date | tanggal bergabung |
| `tanggal_promosi` | date | promosi terakhir (bisa kosong) |
| `departemen` | kategori | Finance, HR, IT, Legal, Marketing, Operations, Sales |
| `status_kerja` | kategori | Tetap, Kontrak, Freelance |
| `pendidikan` | kategori | SMA, Diploma, S1, S2 |
| `target_turnover` | 0/1 | 1 = keluar |

`tanggal_acuan` = `2025-01-01`. Fitur turunan (`log_gaji`, `usia_saat_masuk`,
`ada_promosi`, `bulan_sejak_promosi`, `bulan_hingga_promosi`) dihitung oleh
`buat_fitur()` pada repo source code.

## Cara memuat

```python
from datasets import load_dataset

ds = load_dataset("<username>/hr-intelligence-datasets", "turnover")
```

## Pembangkitan ulang

Semua dataset sintetis dibangkitkan deterministik (seed 42):

```bash
python scripts/generate_absenteeism_data.py
python scripts/generate_performance_data.py
python scripts/generate_payroll_data.py
python scripts/generate_attrition_survival_data.py
```

## Lisensi

MIT.

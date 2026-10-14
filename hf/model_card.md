---
license: mit
library_name: scikit-learn
pipeline_tag: tabular-classification
tags:
  - human-resources
  - hr-analytics
  - turnover-prediction
  - survival-analysis
  - anomaly-detection
  - fairness
  - cpu-only
---

# HR Intelligence Models

Artifact model Machine Learning untuk **HR Intelligence System**. Lima use case,
seluruhnya berjalan **CPU-only** (scikit-learn & lifelines), dilatih pada data
**sintetis/anonim**.

> **Catatan keamanan:** artifact disimpan sebagai `joblib`/`pickle`. Memuat
> pickle berarti mengeksekusi kode dari sumbernya — hanya muat dari repo ini,
> dan verifikasi dengan skrip training bila ragu.

## Struktur

```text
turnover/
├── v1/                 # Logistic Regression
├── v2/                 # Soft Voting ensemble
├── v2_mitigated/       # v2 + reweighting (default API, paling adil)
├── fairness_report.json
├── fairness_baseline.json
├── mitigation_report.json
└── significance_report.json
absenteeism/v1/         # Logistic Regression
performance/v1/         # Logistic Regression (ordinal)
payroll_anomaly/v1/     # Isolation Forest
survival_attrition/v1/  # Cox Proportional Hazards
```

Setiap folder versi berisi artifact + `model_metadata.json` (metrik, threshold,
daftar fitur, `tanggal_acuan`, dan hasil audit fairness).

## Use case

| Use case | Algoritma | Artifact | Metrik utama |
|---|---|---|---|
| Turnover | Soft Voting (LR + RF) | `turnover/v2_mitigated/turnover_model.joblib` | ROC AUC 0.699, PR AUC 0.622 |
| Absensi tinggi | Logistic Regression | `absenteeism/v1/absenteeism_model.joblib` | lihat metadata |
| Kategori kinerja | Logistic Regression (ordinal) | `performance/v1/performance_model.joblib` | lihat metadata |
| Anomali payroll | Isolation Forest | `payroll_anomaly/v1/payroll_anomaly_model.joblib` | ROC AUC 0.978, PR AUC 0.871 |
| Survival attrition | Cox Proportional Hazards | `survival_attrition/v1/cox_model.pkl` | C-index (root metadata) |

## Kontrak fitur turnover

`tanggal_acuan` = `2025-01-01`. Kolom mentah: `usia`, `masa_kerja_bulan`,
`jam_lembur_bulanan`, `gaji_bulanan`, `tanggal_masuk`, `tanggal_promosi`,
`departemen`, `status_kerja`, `pendidikan`. Fitur turunan dihitung oleh
`buat_fitur()`.

Kontrak ini identik di empat tempat: notebook training, `hr_intelligence_api`,
`mlops/src/train/pipeline.py`, dan notebook analisis. Perubahan nama fitur /
rumus / reference date = **breaking** → training ulang + versi baru.

## Fairness

Turnover diaudit dengan impact ratio (four-fifths), demographic parity,
equal opportunity, dan equalized odds. `v2_mitigated` memakai reweighting
Kamiran–Calders pada `status_kerja` dan `pendidikan`. Detail di
`turnover/fairness_report.json` dan `model_metadata.json`.

## Cara memuat

```python
import joblib

model = joblib.load("turnover/v2_mitigated/turnover_model.joblib")
metadata = __import__("json").load(open("turnover/v2_mitigated/model_metadata.json"))

# `metadata["threshold"]` (0.31) dipakai untuk mengubah probabilitas -> kelas.
probabilitas = model.predict_proba(X)[:, 1]
```

Untuk prediksi end-to-end (termasuk `buat_fitur`), pakai HR Intelligence API
pada repo source code.

## Reproduksi

Notebook training dan generator data ada di repo source code:
`notebooks/train_model*.ipynb`, `scripts/generate_*_data.py`, dan
`mlops/src/train/`. Semua deterministik (seed 42).

## Lisensi

MIT. Data sintetis/anonim; tidak memuat data pribadi asli.

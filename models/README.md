# Model Artifacts

Folder ini berisi hasil training Machine Learning yang digunakan oleh sistem HR Intelligence.

Model artifact dihasilkan oleh proses training pada:

```text
notebooks/train_model.ipynb
```

Artifact kemudian digunakan oleh:

```text
hr_intelligence_api/
```

untuk melakukan inference/prediction.

---

# Struktur

```text
models/
│
├── v1/
│   ├── turnover_model.joblib
│   └── model_metadata.json
│
├── v2/
│   ├── turnover_model.joblib
│   └── model_metadata.json
│
├── absenteeism/
│   └── v1/
│       ├── absenteeism_model.joblib
│       └── model_metadata.json
│
├── fairness_report.json
└── README.md
```

Model turnover dilayani oleh `hr_intelligence_api`. Model absensi saat ini
hanya artifact hasil notebook `02_absenteeism.ipynb` (belum diekspos API).

---

# Model Turnover

Model ini digunakan untuk memprediksi kemungkinan seorang karyawan mengalami turnover.

Target:

```text
target_turnover

0 = tidak keluar
1 = keluar
```

Model:

```text
v1: Logistic Regression
v2: Soft Voting (ensemble Logistic Regression + Random Forest)
```

---

# Versi Model

## v1

Artifact:

```text
turnover_model.joblib
model_metadata.json
```

Tanggal pembuatan:

```text
2026-09-29
```

Model:

```text
Logistic Regression
```

Hyperparameter utama:

```json
{
  "C": 0.006026889128682512
}
```

Notebook training:

```text
notebooks/train_model.ipynb
```

---

## v2

Versi ini dihasilkan dari notebook **metode alternatif** yang membandingkan
beberapa algoritma selain Logistic Regression (tree ensemble, boosting, SVM,
dan ensemble soft-voting).

Notebook training:

```text
notebooks/train_model_alternatives.ipynb
```

Artifact:

```text
turnover_model.joblib
model_metadata.json
```

Model terpilih:

```text
Soft Voting (ensemble)
```

Ensemble ini menggabungkan dua model terbaik hasil tuning:

```text
Logistic Regression (C = 0.00603)
Random Forest (n_estimators = 645, max_depth = 4, max_features = log2, min_samples_leaf = 9)
```

Threshold:

```text
0,33
```

Perbandingan metode (ROC-AUC cross-validation, data train):

| Metode                        | ROC-AUC CV | PR-AUC CV |
| ----------------------------- | ---------: | --------: |
| Soft Voting (ensemble)        |     0,7085 |    0,6495 |
| Random Forest (tuned)         |     0,7050 |    0,6166 |
| Logistic Regression (tuned)   |     0,6971 |    0,6290 |
| Logistic Regression (default) |     0,6900 |    0,6290 |
| Random Forest (default)       |     0,6838 |    0,6166 |
| SVM (RBF)                     |     0,6704 |    0,6132 |
| CatBoost                      |     0,6697 |    0,6103 |
| LightGBM                      |     0,6529 |    0,5815 |
| XGBoost                       |     0,6510 |    0,5871 |
| Hist Gradient Boosting        |     0,6509 |    0,5893 |
| Dummy (prior)                 |     0,5000 |    0,3563 |

Metrik holdout test v2:

| Metric     | Nilai |
| ---------- | ----: |
| ROC-AUC    | 0,6993 |
| ROC-AUC 95% CI | 0,6217–0,7739 |
| PR-AUC     | 0,6223 |
| Brier      | 0,2029 |
| Precision  | 0,4752 |
| Recall     | 0,6761 |
| F1         | 0,5581 |

**Cara membaca perbandingan v1 vs v2:** selisih ROC-AUC test hanya
`0,6993 − 0,6929 = 0,0064`, jauh lebih kecil dari lebar confidence interval
(±0,15). Artinya ensemble v2 **tidak terbukti lebih baik secara statistik**;
ia hanya sedikit lebih tinggi. Untuk produksi, pilih yang lebih sederhana dan
lebih mudah dijelaskan kecuali ada bukti tambahan pada data yang lebih besar.

Model v2 tetap memakai **kontrak fitur yang sama** dengan v1 dan
`hr_intelligence_api` (nama fitur, urutan kolom, `tanggal_acuan`), sehingga
dapat dilayani API tanpa perubahan kode.

> **Catatan keadilan (fairness).** Audit bias (`notebooks/05_fairness_audit_turnover.ipynb`,
> hasil di `models/fairness_report.json`) menunjukkan v2 **lebih tidak setara**
> daripada v1 pada kelompok `status_kerja` dan `pendidikan` (impact ratio v2
> 0,45–0,48 vs v1 0,58–0,62). Karena itu, sebelum dipakai untuk keputusan nyata,
> perlu langkah mitigasi (reweighting, threshold per kelompok, atau kembali ke v1).
> Lihat `USE_CASES.md` #16.

---

# Performa Model

Evaluasi dilakukan menggunakan holdout test set.

| Metric                    |         Nilai |
| ------------------------- | ------------: |
| ROC-AUC CV setelah tuning |        0,6971 |
| ROC-AUC holdout test      |        0,6929 |
| ROC-AUC 95% CI            | 0,6136–0,7724 |
| PR-AUC                    |        0,6235 |
| Precision                 |          0,46 |
| Recall                    |        0,6479 |
| F1                        |        0,5380 |

ROC-AUC cross-validation dan ROC-AUC holdout test merupakan evaluasi yang berbeda.

```text
Tuned Cross-Validation
ROC-AUC = 0,6971

Holdout Test
ROC-AUC = 0,6929
```

ROC-AUC 95% confidence interval pada holdout test:

```text
0,6136 – 0,7724
```

---

# Threshold

Threshold model:

```text
0,33
```

Threshold dipilih berdasarkan prediksi out-of-fold pada data training.

Secara umum:

```text
probability >= 0,33
        ↓
prediksi turnover = 1

probability < 0,33
        ↓
prediksi turnover = 0
```

Threshold-dependent metrics seperti precision, recall, dan F1 pada dokumentasi model merupakan hasil evaluasi threshold tersebut pada holdout test set.

Threshold bukan probabilitas risiko yang terpisah dari model. Threshold adalah batas yang digunakan untuk mengubah probability menjadi class prediction.

---

# Training Data

Dataset:

```text
hr_turnover.csv
```

Jumlah data:

```text
1000 rows
```

Rasio target positif:

```text
35,6%
```

Target:

```text
target_turnover
```

---

# Feature Engineering

Model menggunakan fitur numerik:

```text
usia
masa_kerja_bulan
jam_lembur_bulanan
log_gaji
usia_saat_masuk
ada_promosi
bulan_sejak_promosi
bulan_hingga_promosi
```

Fitur kategorikal:

```text
departemen
status_kerja
pendidikan
```

Kolom mentah yang dibutuhkan:

```text
usia
masa_kerja_bulan
jam_lembur_bulanan
gaji_bulanan
tanggal_masuk
tanggal_promosi
departemen
status_kerja
pendidikan
```

---

# Reference Date

Feature engineering menggunakan tanggal acuan:

```text
2025-01-01
```

Nilai ini harus konsisten antara training dan inference.

Metadata:

```json
{
  "tanggal_acuan": "2025-01-01"
}
```

API menggunakan nilai tersebut ketika melakukan feature engineering terhadap input prediction.

---

# Preprocessing

Feature preprocessing dilakukan menggunakan scikit-learn Pipeline dan ColumnTransformer.

### Numeric

```text
Median Imputation
        ↓
StandardScaler
```

### Categorical

```text
Most Frequent Imputation
        ↓
OneHotEncoder
```

OneHotEncoder menggunakan:

```text
handle_unknown="ignore"
```

sehingga kategori yang belum pernah muncul pada training tidak langsung menyebabkan error ketika inference.

---

# Model Artifact

## `turnover_model.joblib`

File ini berisi pipeline model yang sudah dilatih.

Artifact digunakan oleh API untuk:

```python
model.predict_proba(X)
```

API tidak melakukan training ulang ketika artifact ini dimuat.

---

# Metadata

## `model_metadata.json`

Metadata menyimpan informasi penting mengenai artifact, termasuk:

```text
tanggal pembuatan
tujuan model
sumber dataset
jumlah data
rasio target positif
algoritma
hyperparameter
threshold
feature numerik
feature kategorikal
kolom mentah
tanggal acuan
metrik holdout test
versi library
catatan penggunaan
```

Metadata digunakan oleh API untuk mengetahui kontrak model tanpa harus melakukan inspeksi manual terhadap notebook.

---

# Training Pipeline

Proses training secara umum:

```text
Raw Dataset
     │
     ▼
Data Validation
     │
     ▼
Feature Engineering
     │
     ▼
Train / Test Split
     │
     ▼
Candidate Models
     │
     ▼
Cross Validation
     │
     ▼
Model Selection
     │
     ▼
Hyperparameter Tuning
     │
     ▼
Out-of-Fold Prediction
     │
     ▼
Threshold Selection
     │
     ▼
Holdout Test Evaluation
     │
     ▼
Bootstrap Confidence Interval
     │
     ▼
Final Refit
     │
     ▼
Model Artifact
     │
     ├── turnover_model.joblib
     └── model_metadata.json
```

---

# Hubungan dengan API

Model artifact digunakan oleh:

```text
hr_intelligence_api/
```

Alurnya:

```text
models/<MODEL_VERSION>/
        │
        ├── turnover_model.joblib
        │
        └── model_metadata.json
                    │
                    ▼
             ModelService
                    │
                    ▼
          PredictionService
                    │
                    ▼
              API Response
```

API tidak mengubah model artifact.

Versi model yang dilayani dipilih lewat env `MODEL_VERSION` (default **`v2`**).
Untuk rollback, set `MODEL_VERSION=v1` tanpa mengubah kode. Bisa juga
meng-override path langsung lewat `MODEL_PATH` / `METADATA_PATH`.

---

# Konsistensi Feature Engineering

Feature engineering pada API harus konsisten dengan feature engineering ketika training.

Training:

```text
notebooks/train_model.ipynb
```

Inference:

```text
hr_intelligence_api/app/features/features.py
```

Keduanya harus menghasilkan schema fitur yang kompatibel dengan model.

Perubahan terhadap:

* nama fitur,
* rumus fitur,
* reference date,
* transformasi data,
* kategori,
* preprocessing,

dapat menyebabkan model tidak kompatibel.

Jika perubahan tersebut diperlukan, lakukan training ulang dan buat versi model baru.

---

# Model Versioning

Model menggunakan versioning agar artifact lama tidak langsung hilang ketika model baru dibuat.

Contoh:

```text
models/
└── turnover/
    ├── v1/
    │   ├── turnover_model.joblib
    │   └── model_metadata.json
    │
    ├── v2/
    │   ├── turnover_model.joblib
    │   └── model_metadata.json
    │
    └── v3/
        ├── turnover_model.joblib
        └── model_metadata.json
```

Contoh perubahan:

```text
v1
↓
Model awal

v2
↓
Training dengan dataset/features baru

v3
↓
Model berikutnya
```

Setiap versi sebaiknya memiliki metadata sendiri.

---

# Aturan Perubahan Model

Jangan mengganti:

```text
turnover_model.joblib
```

secara sembarangan tanpa memperbarui metadata.

Jika model dilatih ulang:

1. Buat versi model baru.
2. Simpan model artifact baru.
3. Simpan metadata baru.
4. Catat perubahan training.
5. Evaluasi model.
6. Update API ke versi model yang digunakan.
7. Simpan artifact versi sebelumnya untuk rollback.

---

# Library Version

Model v1 dibuat menggunakan:

```text
Python / scikit-learn environment
```

dengan versi library yang tercatat pada metadata:

```text
scikit-learn = 1.9.1
pandas = 3.0.6
numpy = 2.5.3
```

Environment inference sebaiknya menggunakan versi yang kompatibel.

---

# Sumber Training

Notebook training:

```text
notebooks/train_model.ipynb
```

Dataset:

```text
data/raw/hr_turnover.csv
```

Model artifact:

```text
models/turnover/v1/
```

API:

```text
hr_intelligence_api/
```

---

# Status

Model turnover:

```text
v1: tersedia
v2: tersedia
Algorithm v1: Logistic Regression
Algorithm v2: Soft Voting (ensemble Logistic Regression + Random Forest)
Artifact: tersedia
Metadata: tersedia
API integration: tersedia
Model versioning: struktur awal
Monitoring: belum tersedia
Automated retraining: belum tersedia
Model registry: belum tersedia
```

Fitur seperti model registry, automated retraining, monitoring, drift detection, dan rollback otomatis akan dikembangkan pada fase MLOps.

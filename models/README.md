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
└── turnover/
    │
    ├── v1/
    │   ├── turnover_model.joblib
    │   └── model_metadata.json
    │
    └── README.md
```

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
Logistic Regression
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
models/turnover/v1/
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

Model turnover v1:

```text
Status: tersedia
Algorithm: Logistic Regression
Artifact: tersedia
Metadata: tersedia
API integration: tersedia
Model versioning: struktur awal
Monitoring: belum tersedia
Automated retraining: belum tersedia
Model registry: belum tersedia
```

Fitur seperti model registry, automated retraining, monitoring, drift detection, dan rollback otomatis akan dikembangkan pada fase MLOps.

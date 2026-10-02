# MLOps

Project untuk mengelola **Machine Learning lifecycle** dalam HR Intelligence System.

MLOps bertanggung jawab menghubungkan proses development model dengan deployment, monitoring, versioning, dan maintenance model.

---

# Tujuan

Membangun lifecycle Machine Learning yang dapat direproduksi dan dikelola secara sistematis.

```text
Data
 ↓
Training
 ↓
Evaluation
 ↓
Model Versioning
 ↓
Deployment
 ↓
Monitoring
 ↓
Drift Detection
 ↓
Retraining
```

---

# Fokus

## Model Versioning

Mengelola versi model Machine Learning.

Contoh:

```text
models/
└── turnover/
    ├── v1/
    ├── v2/
    └── v3/
```

Setiap versi dapat memiliki:

* Model artifact
* Metadata
* Feature information
* Evaluation metrics
* Training configuration

---

## CI/CD

Automation untuk:

* Testing
* Linting
* Build
* Model validation
* Docker image build
* Deployment

---

## Deployment

Mengelola deployment service.

Contoh:

```text
Development
     ↓
Staging
     ↓
Production
```

Deployment dapat menggunakan Docker dan infrastructure lainnya sesuai kebutuhan.

---

## Monitoring

Monitoring digunakan untuk mengetahui kondisi aplikasi dan model.

Contoh:

* API health
* Request volume
* Response time
* Error rate
* Prediction distribution
* Model performance

---

## Data Drift

Mendeteksi perubahan distribusi data.

Contoh:

```text
Training Data
     │
     ▼
Reference Distribution
     │
     │
     ▼
Production Data
     │
     ▼
Drift Detection
```

Jika terjadi perubahan signifikan, model dapat dievaluasi kembali.

---

## Retraining

Model dapat menjalani proses retraining berdasarkan kondisi tertentu.

Contoh trigger:

```text
Data berubah
     │
     ▼
Drift detected
     │
     ▼
Model evaluation
     │
     ▼
Retraining
     │
     ▼
New model version
```

---

# Struktur Project

```text
mlops/
│
├── README.md
├── requirements.txt
│
├── src/
│   ├── config.py                 # path model/data, ambang drift & AUC
│   ├── registry/model_registry.py    # registry berbasis model_metadata.json
│   ├── validation/model_validator.py # gate kelayakan deploy
│   ├── monitoring/drift.py           # PSI + deteksi drift per fitur
│   ├── monitoring/metrics.py         # MetricsCollector (format Prometheus)
│   ├── api.py                    # FastAPI
│   └── cli.py                    # validasi semua model (exit 1 bila gagal)
│
├── docker/
│   ├── api/Dockerfile            # image MLOps
│   ├── hr-api/Dockerfile         # image HR Intelligence API
│   └── docker-compose.yml
│
├── ci/github-actions/ci.yml      # test modul + validasi model
│
├── monitoring/
│   ├── alerts/alerts.yml         # aturan alert Prometheus
│   └── dashboards/checklist.md   # checklist monitoring
│
└── tests/test_mlops.py
```

Struktur dapat berkembang mengikuti kebutuhan deployment dan infrastructure.

---

# Validasi Model

`src/cli.py` memvalidasi kelima model dan menjadi **gate** sebelum model
dianggap layak deploy. Pemeriksaan:

- Metadata lengkap (versi & jenis model).
- Ketersediaan metrik.
- Metrik utama di atas ambang (`MIN_ROC_AUC`, default 0.6). Metrik utama dipilih
  sesuai jenis model: ROC AUC untuk klasifikasi, **C-index** untuk survival,
  **macro-F1** untuk model ordinal (kinerja).
- Model unsupervised (IsolationForest payroll) dinilai dari PR AUC, bukan ROC AUC.
- Akurasi wajar (≥ 0.5) bila tersedia.

```bash
cd mlops && python -m src.cli   # exit 1 bila ada model yang gagal
```

---

# Docker

Docker digunakan untuk membuat environment yang konsisten.

Contoh:

```text
Docker
 │
 ├── HR Intelligence API
 ├── HR Dashboard
 └── Supporting Services
```

Container dapat digunakan pada development maupun deployment.

---

# CI/CD

Pipeline CI/CD dapat menjalankan proses seperti:

```text
Git Push
   │
   ▼
Run Tests
   │
   ▼
Lint
   │
   ▼
Build
   │
   ▼
Model Validation
   │
   ▼
Deploy
```

---

# Monitoring Architecture

```text
Application
     │
     ▼
 Metrics / Logs
     │
     ▼
 Monitoring
     │
     ├── Performance
     ├── Errors
     ├── Predictions
     └── Drift
```

Monitoring menjadi dasar untuk mengetahui apakah service dan model berjalan sesuai ekspektasi.

---

# Integrasi dengan ML System

MLOps mengelola lifecycle model yang digunakan oleh:

```text
notebooks/
     │
     ▼
models/
     │
     ▼
hr_intelligence_api/
     │
     ▼
hr_application/
     │
     ▼
hr_dashboard/
```

Dengan demikian, model tidak hanya berhenti pada proses training tetapi dapat dikelola sampai deployment dan monitoring.

---

# Teknologi

Teknologi yang dapat digunakan:

* Docker
* Docker Compose
* GitHub Actions
* Python
* FastAPI
* scikit-learn
* Monitoring Tools
* CI/CD Tools

Tool dapat berkembang sesuai kebutuhan infrastructure project.

---

# Prinsip Pengembangan

* Reproducible deployment
* Infrastructure as Code jika diperlukan
* Automated testing
* Automated build
* Model versioning
* Monitoring
* Observability
* Controlled deployment
* Rollback capability

---

# Pengembangan

Masuk ke directory:

```bash
cd mlops
```

Komponen MLOps dapat dijalankan sesuai kebutuhan masing-masing.

Contoh menjalankan Docker Compose:

```bash
docker compose up --build
```

Pipeline CI/CD dijalankan melalui konfigurasi pada:

```text
ci/
```

Monitoring configuration berada pada:

```text
monitoring/
```

---

## License

Private / Proprietary.

Hak penggunaan, distribusi, modifikasi, dan akses terhadap source code mengikuti kebijakan pemilik proyek.

© 2026 All Rights Reserved.

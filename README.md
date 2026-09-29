# HR Intelligence System

Sistem **AI/ML untuk kebutuhan Human Resources (HR)** yang dikembangkan secara modular.

Project dimulai dari **Employee Turnover Prediction**, kemudian dikembangkan menjadi sistem HR Intelligence yang mencakup:

* HR Application
* HR Dashboard
* Machine Learning
* Payroll Intelligence
* Deep Learning
* AI HR Assistant
* MLOps

Project ini menjadi tempat untuk menggabungkan:

**Software Engineering + Data Science + Machine Learning + AI + MLOps**

Detail tahapan pengembangan tersedia di [`ROADMAP.md`](./ROADMAP.md).

---

# Arsitektur

Project menggunakan pendekatan **modular / multi-project architecture**.

Setiap komponen memiliki source code, dependency, testing, dan dokumentasi masing-masing.

```text
                     HR INTELLIGENCE SYSTEM
                              │
       ┌──────────────────────┼──────────────────────┐
       │                      │                      │
       ▼                      ▼                      ▼
┌───────────────┐      ┌───────────────┐      ┌────────────────┐
│hr_application │      │ hr_dashboard  │      │ai_hr_assistant │
│   Laravel     │      │  Analytics    │      │   LLM / RAG    │
└───────┬───────┘      └───────┬───────┘      └───────┬────────┘
        │                      │                       │
        └──────────────────────┼───────────────────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │hr_intelligence_api │
                    │    FastAPI + ML     │
                    └──────────┬──────────┘
                               │
                               ▼
                         ┌────────────┐
                         │ ML Models  │
                         └────────────┘

          ┌──────────────────────┐
          │ payroll_intelligence │
          └──────────────────────┘

          ┌──────────────────────┐
          │   deep_learning      │
          └──────────────────────┘

          ┌──────────────────────┐
          │       mlops          │
          └──────────────────────┘
```

### Prinsip

* Setiap project memiliki tanggung jawab yang jelas.
* Komponen dapat dikembangkan secara independen.
* Integrasi antar service menggunakan kontrak yang jelas.
* Model ML menggunakan versioning.
* Dokumentasi teknis berada di masing-masing project.
* Credential dan secret tidak disimpan di repository.

---

# Komponen

## `hr_application/`

Aplikasi utama untuk menjalankan **proses bisnis HR**.

Contoh fungsi:

* Employee Management
* Attendance
* Leave
* Payroll
* Employee History
* HR Administration
* Reporting
* Integrasi dengan ML API

**Teknologi:** Laravel / PHP

Dokumentasi:

[`hr_application/README.md`](./hr_application/README.md)

---

## `hr_intelligence_api/`

Service untuk menjalankan **Machine Learning inference** dan menyediakan akses model melalui API.

Contoh fungsi:

* Employee Turnover Prediction
* Batch Prediction
* Model Information
* Health Check
* API Authentication

**Teknologi:** Python / FastAPI / scikit-learn

Dokumentasi:

[`hr_intelligence_api/README.md`](./hr_intelligence_api/README.md)

---

## `hr_dashboard/`

Aplikasi untuk **visualisasi dan analisis data HR**.

Contoh:

* Workforce Analytics
* Turnover Analytics
* Employee Risk
* Attendance Analytics
* Payroll Analytics
* HR KPI

**Teknologi:** Frontend / Dashboard

Dokumentasi:

[`hr_dashboard/README.md`](./hr_dashboard/README.md)

---

## `payroll_intelligence/`

Project untuk **analitik dan intelligence terkait payroll**.

Contoh:

* Payroll Analytics
* Payroll Anomaly Detection
* Salary Analysis
* Overtime Analysis
* Payroll Forecasting

**Teknologi:** Python / Data Analytics / Machine Learning

Dokumentasi:

[`payroll_intelligence/README.md`](./payroll_intelligence/README.md)

---

## `deep_learning/`

Project untuk eksperimen dan pengembangan **Deep Learning**.

Fokus:

* Neural Network
* Computer Vision
* OCR
* Document Understanding
* Text / Image Classification

**Teknologi:** Python / Deep Learning Framework

Dokumentasi:

[`deep_learning/README.md`](./deep_learning/README.md)

---

## `ai_hr_assistant/`

Project untuk membangun **AI Assistant khusus HR** menggunakan LLM.

Fokus:

* LLM
* RAG
* Knowledge Base
* Embeddings
* Vector Search
* AI Tools

**Teknologi:** Python / LLM / RAG

Dokumentasi:

[`ai_hr_assistant/README.md`](./ai_hr_assistant/README.md)

---

## `mlops/`

Project untuk mengelola **Machine Learning lifecycle**.

Fokus:

* Model Versioning
* Training Pipeline
* CI/CD
* Deployment
* Monitoring
* Model / Data Drift
* Retraining

**Teknologi:** Docker / CI/CD / MLOps Tools

Dokumentasi:

[`mlops/README.md`](./mlops/README.md)

---

# Struktur Repository

```text
hr-intelligence-system/
│
├── README.md
├── ROADMAP.md
├── CONTRIBUTE.md
│
├── data/
│   └── raw/
│       └── hr_turnover.csv
│
├── notebooks/
│   └── train_model.ipynb
│
├── models/
│   ├── README.md
│   └── turnover/
│       └── v1/
│           ├── turnover_model.joblib
│           └── model_metadata.json
│
├── hr_intelligence_api/
│   ├── README.md
│   ├── app/
│   ├── tests/
│   ├── requirements.txt
│   ├── Dockerfile
│   └── .env.example
│
├── hr_application/
│   ├── README.md
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── resources/
│   ├── storage/
│   ├── tests/
│   └── ...
│
├── hr_dashboard/
│   ├── README.md
│   └── src/
│
├── payroll_intelligence/
│   ├── README.md
│   ├── notebooks/
│   └── src/
│
├── deep_learning/
│   ├── README.md
│   └── notebooks/
│
├── ai_hr_assistant/
│   ├── README.md
│   ├── knowledge/
│   ├── prompts/
│   └── src/
│
└── mlops/
    ├── README.md
    ├── docker/
    ├── ci/
    └── monitoring/
```

---

# Model Saat Ini

Model pertama yang dikembangkan adalah **Employee Turnover Prediction**.

```text
Model     : Logistic Regression
Target    : Employee Turnover
Threshold : 0.33
ROC-AUC   : 0.6929
PR-AUC    : 0.6235
```

Model artifact:

```text
models/
└── turnover/
    └── v1/
        ├── turnover_model.joblib
        └── model_metadata.json
```

Training dilakukan melalui:

```text
notebooks/train_model.ipynb
```

Detail model, feature engineering, evaluasi, dan metadata tersedia di:

[`models/README.md`](./models/README.md)

---

# Cara Mulai

Untuk memulai development:

```bash
git clone <repository-url>
cd hr-intelligence-system
```

Setiap project memiliki environment dan cara menjalankan masing-masing.

Mulai dari dokumentasi project yang ingin dikembangkan:

```text
hr_application/README.md
hr_intelligence_api/README.md
hr_dashboard/README.md
payroll_intelligence/README.md
deep_learning/README.md
ai_hr_assistant/README.md
mlops/README.md
```

Untuk kontribusi, baca:

[`CONTRIBUTE.md`](./CONTRIBUTE.md)

---

# Project Philosophy

Project ini tidak hanya bertujuan membuat sebuah model Machine Learning.

Tujuannya adalah mempelajari bagaimana membangun sistem end-to-end:

```text
Data
 ↓
Data Science
 ↓
Machine Learning
 ↓
Model
 ↓
API
 ↓
Application
 ↓
Dashboard
 ↓
AI
 ↓
MLOps
 ↓
Production
```

Model tidak berhenti sebagai notebook, tetapi dikembangkan menjadi bagian dari sistem yang dapat diintegrasikan dengan aplikasi nyata.

> **Build → Learn → Share → Improve**

---

## License

Private / Proprietary.

Hak penggunaan, distribusi, modifikasi, dan akses terhadap source code mengikuti kebijakan pemilik proyek.

© 2026 All Rights Reserved.
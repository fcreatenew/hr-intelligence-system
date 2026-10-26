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

## Model AI dan Machine Learning

Model artifact yang digunakan atau dievaluasi untuk proyek ini dapat ditelusuri di [HR Intelligence Models — Hugging Face](https://huggingface.co/fcreatenew/hr-intelligence-models/tree/main).

Gunakan halaman tersebut untuk memeriksa model yang tersedia, model card, cara penggunaan, batasan, dan lisensi masing-masing model. Jika model digunakan untuk inference atau integrasi, pastikan lisensi model dan bobotnya mengizinkan tujuan penggunaan Anda; lisensi repository software tidak otomatis menggantikan lisensi model AI atau dataset.

Model lokal yang disertakan dalam repository ini tetap didokumentasikan pada [`models/README.md`](./models/README.md). Untuk komponen AI Assistant, lihat [`ai_hr_assistant/README.md`](./ai_hr_assistant/README.md).


Panduan fitur dan cara menjalankan seluruh komponen tersedia di:

* [`PANDUAN_PENGGUNAAN.md`](./PANDUAN_PENGGUNAAN.md) — versi Markdown
* [`PANDUAN_PENGGUNAAN.pdf`](./PANDUAN_PENGGUNAAN.pdf) — versi PDF (15 halaman)

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

Aplikasi HRIS (Human Resource Information System) berbasis **Laravel / PHP**.
Aplikasi bisnis untuk staf HR sekaligus konsumen layanan ML
(`hr_intelligence_api` dan `payroll_intelligence`).

> **Status: HRIS inti sudah berjalan.** Siklus karyawan inti — Employee
> Management, Employee History, Attendance, Leave, Payroll, Reporting, Audit
> Trail, dan Manajemen Pengguna — sudah dapat dipakai staf HR.

Sudah berjalan:

* Integrasi dengan ML API (semua 5 use case)
* Pemetaan record karyawan → input model (`EmployeeFeatureMapper`)
* Prediksi batch terjadwal (bulanan/mingguan/kuartalan)
* Penyimpanan & penelusuran hasil prediksi
* Autentikasi, profil, reset kata sandi, peran `admin` & `hr-manager`
* **Employee Management** — CRUD, pencarian, filter, paginasi, terminasi
* **Employee History** — linimasa mutasi/jabatan/gaji/status
* **Attendance** — pencatatan harian, rekap periode, impor CSV mesin absensi
* **Leave** — pengajuan, persetujuan, saldo cuti, kalender tim
* **Payroll** — periode, perhitungan dari absensi, finalisasi, slip, ekspor
* **Reporting** — headcount, turnover, biaya per departemen + ekspor CSV
* **Audit Trail** & **Manajemen Pengguna**
* Fitur absensi model dihitung dari data absensi, bukan diisi manual

Rencana modul proses bisnis HR (melengkapi siklus karyawan end-to-end):

| # | Modul | Menyambung ke ML |
|---|---|---|
| 1 | Recruitment / ATS | Resume screening & person-job fit |
| 2 | Onboarding / Offboarding | Turnover & survival |
| 3 | Performance Management | Prediksi kinerja & HiPo |
| 4 | Training & Development | Rekomendasi pelatihan |
| 5 | Compensation & Benefits | Anomali & forecasting payroll |
| 6 | Time & Attendance lanjutan | Prediksi absensi |
| 7 | Employee Self-Service | — |
| 8 | Document Management | — |
| 9 | Compliance & Statutory Reporting | — |

Detail tiap modul, rumus payroll, dan jadwal prediksi ada di
[`hr_application/README.md`](./hr_application/README.md); urutan fase & kriteria
selesai di [`ROADMAP.md`](./ROADMAP.md).

**Teknologi:** Laravel / PHP

Dokumentasi:

[`hr_application/README.md`](./hr_application/README.md)

---

## `hr_intelligence_api/`

Service untuk menjalankan **Machine Learning inference** dan menyediakan akses model melalui API.

Contoh fungsi:

* Multi-model prediction: turnover, absensi, kinerja, payroll anomaly, survival
* Batch Prediction
* Model Information per use case
* Health Check
* API Authentication

**Teknologi:** Python / FastAPI / scikit-learn

Dokumentasi:

[`hr_intelligence_api/README.md`](./hr_intelligence_api/README.md)

---

## `hr_dashboard/`

Aplikasi **React + Vite** untuk visualisasi dan analisis data HR.

Halaman: Ringkasan, Workforce, Turnover, Attendance, Payroll, HR KPI & Model.
Membaca dari HR Intelligence API dan Payroll Intelligence API. Default berjalan
dalam **mode demo** (data contoh lokal) agar tidak pernah kosong; set
`VITE_DATA_MODE=live` untuk memakai API sungguhan.

**Teknologi:** React / Vite / Recharts

```bash
cd hr_dashboard && npm install && npm run dev
```

Dokumentasi:

[`hr_dashboard/README.md`](./hr_dashboard/README.md)

---

## `payroll_intelligence/`

Project untuk **analitik dan intelligence terkait payroll**.

* Ingestion & transformasi data payroll
* Perhitungan komponen gaji
* Payroll Analytics (ringkasan, per departemen, per periode, lembur)
* Payroll Anomaly Detection (IsolationForest)
* Payroll Forecasting (Holt / seasonal)
* REST API + CLI

**Teknologi:** Python / FastAPI / scikit-learn / statsmodels

```bash
cd payroll_intelligence && python -m pytest tests/ -q
cd payroll_intelligence && uvicorn src.api:app --port 8300
```

Dokumentasi:

[`payroll_intelligence/README.md`](./payroll_intelligence/README.md)

---

## `deep_learning/`

Eksperimen **neural network** (MLP murni NumPy) untuk kasus HR, dibandingkan
dengan baseline klasik.

* MLP + backpropagation + early stopping (tanpa PyTorch/TensorFlow)
* Kontrak fitur turnover identik dengan `hr_intelligence_api`
* Baseline Logistic Regression & majority class sebagai pembanding
* Pencatatan eksperimen (JSON) + CLI + API

**Teknologi:** Python / NumPy / FastAPI

```bash
cd deep_learning && python -m pytest tests/ -q
cd deep_learning && python -m src.cli compare
```

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

Assistant dapat menjawab pertanyaan kebijakan/prosedur dari knowledge base
(retrieval + sitasi) dan memanggil kelima model ML yang sudah ada (turnover,
absensi, kinerja, payroll, survival) lewat HR Intelligence API.

**Teknologi:** Python / FastAPI / LLM / RAG

Dokumentasi:

[`ai_hr_assistant/README.md`](./ai_hr_assistant/README.md)

---

## `mlops/`

Project untuk mengelola **Machine Learning lifecycle**.

* Model Registry (metadata `models/` sebagai sumber kebenaran)
* Model Validation (gate kelayakan deploy)
* Drift Detection (PSI) & Metrics Collector (format Prometheus)
* REST API + CLI validasi
* Docker Compose, workflow CI GitHub Actions, aturan alert, checklist monitoring

**Teknologi:** Python / FastAPI / Docker / CI/CD

```bash
cd mlops && python -m pytest tests/ -q
cd mlops && python -m src.cli          # validasi semua model (gate CI)
```

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
├── USE_CASES.md
├── MODEL_RESULTS.md
├── PANDUAN_PENGGUNAAN.md
├── PANDUAN_PENGGUNAAN.pdf
├── .python-version                   # Python 3.14 (runtime seluruh modul Python)
│
├── data/
│   ├── README.md
│   ├── hr_turnover.csv
│   ├── hr_absenteeism.csv          # sintetis
│   ├── hr_performance.csv          # sintetis
│   ├── hr_payroll.csv              # sintetis
│   └── hr_attrition_survival.csv   # sintetis
│
├── requirements/
│   ├── base.txt                     # sumber tunggal versi dependensi Python
│   └── README.md
│
├── scripts/
│   ├── generate_absenteeism_data.py
│   ├── generate_performance_data.py
│   ├── generate_payroll_data.py
│   ├── generate_attrition_survival_data.py
│   ├── generate_panduan_pdf.py      # PANDUAN_PENGGUNAAN.md -> PDF
│   └── verify_panduan_examples.py   # uji contoh curl di panduan
│
├── notebooks/
│   ├── README.md
│   ├── train_model.ipynb
│   ├── train_model_alternatives.ipynb
│   ├── 02_absenteeism.ipynb
│   ├── 03_performance_prediction.ipynb
│   ├── 04_payroll_anomaly_detection.ipynb
│   ├── 05_fairness_audit_turnover.ipynb
│   ├── 06_survival_attrition.ipynb
│   ├── 07_significance_test.ipynb
│   └── 08_bias_mitigation_turnover.ipynb
│
├── models/
│   ├── README.md
│   ├── v1/                    # turnover — Logistic Regression
│   ├── v2/                    # turnover — Soft Voting
│   ├── v2_mitigated/          # turnover — v2 + reweighting
│   ├── absenteeism/v1/        # absensi
│   ├── performance/v1/        # kinerja
│   ├── payroll_anomaly/v1/    # anomali payroll
│   ├── survival_attrition/v1/ # survival
│   ├── fairness_report.json
│   ├── mitigation_report.json
│   └── significance_report.json
│
├── hr_intelligence_api/
│   ├── README.md
│   ├── app/
│   ├── tests/
│   ├── requirements.txt             # -r ../requirements/base.txt
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
│   ├── src/
│   ├── experiments/
│   └── tests/
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

HR Intelligence API melayani **lima use case** Machine Learning. Semuanya sudah
diekspos lewat API dan dapat dipanggil dari `hr_application` maupun
`ai_hr_assistant`.

| # | Use case | Endpoint | Model | Versi | Metrik utama |
|---|----------|----------|-------|-------|--------------|
| 1 | Turnover | `POST /predict` | Soft Voting (ensemble LR + RF) | v2_mitigated | ROC-AUC 0,6993 |
| 2 | Absensi | `POST /predict/absensi` | Logistic Regression | v1 | ROC-AUC 0,8287 |
| 3 | Kinerja | `POST /predict/kinerja` | Logistic Regression (ordinal) | v1 | macro-F1 0,6357 |
| 4 | Payroll anomaly | `POST /predict/payroll` | Isolation Forest | v1 | PR-AUC 0,8709 |
| 5 | Survival attrition | `POST /predict/survival` | Cox Proportional Hazards | v1 | C-index 0,8144 |

Turnover tersedia dalam tiga artifact: `v1`, `v2`, dan `v2_mitigated`
(reweighting, paling setara — **default API**).

Model turnover juga tersedia dalam versi deep learning (MLP NumPy) sebagai
pembanding di `deep_learning/`; pada dataset ini baseline klasik masih unggul.

Model artifact:

```text
models/
├── v1/                        # turnover — Logistic Regression
├── v2/                        # turnover — Soft Voting
├── v2_mitigated/              # turnover — v2 + reweighting
├── absenteeism/v1/            # absensi
├── performance/v1/            # kinerja
├── payroll_anomaly/v1/        # anomali payroll
└── survival_attrition/v1/     # survival
```

Detail model, feature engineering, evaluasi, metadata, audit fairness, dan uji
signifikansi tersedia di:

* [`models/README.md`](./models/README.md)
* [`MODEL_RESULTS.md`](./MODEL_RESULTS.md)

---

# Use Case ML Lain untuk HR

Turnover bukan satu-satunya masalah HR yang bisa didekati dengan Machine Learning.
Katalog use case lain (rekrutmen, NLP, HiPo, workforce segmentation) beserta data
& referensinya ada di:

[`USE_CASES.md`](./USE_CASES.md)

Hasil lengkap perbandingan model (v1 vs v2, absensi, audit fairness) ada di:

[`MODEL_RESULTS.md`](./MODEL_RESULTS.md)

---

# Cara Mulai

Untuk memulai development:

```bash
git clone <repository-url>
cd hr-intelligence-system
```

Setiap project memiliki environment dan cara menjalankan masing-masing.

**Prasyarat versi:** modul Python memakai **Python 3.14** (dipin di
`.python-version`, ditegakkan di CI dan Dockerfile), `hr_dashboard` memakai
Node.js 24, dan `hr_application` memakai PHP 8.4. Detail per komponen ada di
`PANDUAN_PENGGUNAAN.md` bagian 3.

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

## Menjalankan dengan Docker

Seluruh stack inti dapat dijalankan sekaligus dengan Docker Compose:

```bash
HR_API_KEY=<secret> docker compose up --build
```

| Layanan | URL | Isi |
|---|---|---|
| `hr-api` | http://localhost:8000 | HR Intelligence API (prediksi model) |
| `payroll-api` | http://localhost:8300 | Payroll Intelligence (analitik & skor anomali) |
| `hr-app` | http://localhost:8080 | Laravel HRIS (attendance/leave/payroll) |
| `dashboard` | http://localhost:8081 | React dashboard |
| `assistant` | http://localhost:8100 | AI HR Assistant |

`hr-app` otomatis diarahkan ke `hr-api` dan `payroll-api` di dalam jaringan
Compose, sehingga prediksi dan skor anomali berjalan tanpa konfigurasi manual.
`APP_KEY` untuk Laravel diisi lewat environment (`APP_KEY=base64:...`).

Dashboard memakai nginx sebagai reverse proxy. Upstream API dapat diatur lewat
`HR_API_UPSTREAM` dan `PAYROLL_API_UPSTREAM` (lihat
`hr_dashboard/nginx.conf.template`); defaultnya sudah cocok untuk Compose.

## Demo & Deploy Gratis

Untuk menampilkan demo di layanan gratis (Render, Cloudflare Pages, Koyeb,
Oracle Cloud, Hugging Face Hub), lihat:

* [`deploy/DEPLOY.md`](./deploy/DEPLOY.md) — panduan lengkap + `render.yaml`
* [`hf/space/`](./hf/space) — halaman showcase untuk Hugging Face Space (statis)
* [`scripts/upload_to_hf.py`](./scripts/upload_to_hf.py) — unggah model/dataset ke HF Hub

Ringkas: model & dataset ke **Hugging Face Hub**, dashboard ke **Cloudflare
Pages** (mode `demo` tanpa backend) atau Render, API Python & Laravel ke
**Render** free web service, dan stack penuh always-on via **Oracle Cloud
Always Free** (`deploy/oracle-cloud/`).

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

Saat ini repository ini menggunakan **MIT License**. Anda dapat melihat ketentuan lengkapnya pada file [LICENSE](./LICENSE).

MIT mengizinkan penggunaan, modifikasi, dan distribusi, termasuk dalam produk komersial, dengan tetap mempertahankan pemberitahuan hak cipta dan lisensi. Lisensi ini **tidak mewajibkan semua karya turunan menjadi open source**.

Jika tujuan proyek berubah menjadi mewajibkan distribusi turunan di bawah lisensi copyleft dan menyediakan source code untuk versi yang dimodifikasi yang digunakan melalui jaringan, maintainer dapat mempertimbangkan migrasi ke **GNU AGPL-3.0-only**. Perubahan lisensi perlu dilakukan secara konsisten dan hanya untuk kode yang hak lisensinya dapat diberikan oleh pemiliknya. Mengganti lisensi tidak otomatis mencabut hak MIT yang sudah diberikan untuk salinan/versi terdahulu.

Baca [LICENSE](./LICENSE) untuk lisensi yang berlaku pada versi repository ini.
# AGENTS.md

Panduan kerja untuk agen/AI di repository ini.

## Struktur

| Folder | Isi |
|---|---|
| `hr_intelligence_api/` | FastAPI — melayani prediksi multi-model (turnover, absensi, kinerja, payroll, survival) |
| `hr_application/` | Laravel — aplikasi HR (konsumen API) |
| `notebooks/` | Notebook training & analisis |
| `models/` | Artifact model + metadata per versi |
| `data/` | Dataset (anonim/sintetis) |
| `scripts/` | Generator data & utilitas |
| `ai_hr_assistant/`, `payroll_intelligence/`, `deep_learning/`, `mlops/`, `hr_dashboard/` | Modul per domain |

## AI HR Assistant (`ai_hr_assistant/`)

Asisten HR berbasis LLM + RAG + AI tools (FastAPI, Python).

- **Knowledge base** (markdown) ada di `ai_hr_assistant/knowledge/{policies,procedures,regulations,faq}`;
  prompt terstruktur di `ai_hr_assistant/prompts/`.
- **AI tools** memanggil kelima model ML lewat HR Intelligence API
  (`predict_turnover`, `predict_absensi`, `predict_kinerja`, `predict_payroll`,
  `predict_survival`) plus `model_info`, `list_models`, `health`.
- **Provider LLM** diatur env `LLM_PROVIDER` (`mock` default, `openai` opsional).
  `mock` menyusun jawaban deterministik dari retrieval + tool (tanpa jaringan).
- Konfigurasi memakai env `HR_INTELLIGENCE_BASE_URL` dan `HR_INTELLIGENCE_API_KEY`.

```bash
cd ai_hr_assistant && python -m pytest tests/ -q
cd ai_hr_assistant && uvicorn src.main:app --port 8100   # butuh HR Intelligence API di port 8000
```

## Modul lain

- **`payroll_intelligence/`** — analitik payroll, deteksi anomali (IsolationForest),
  forecasting (statsmodels). `cd payroll_intelligence && python -m pytest tests/ -q`;
  API di port 8300.
- **`mlops/`** — registry, validasi model (gate deploy), drift PSI, metrics,
  training reproducible (`python -m src.train`), dan siklus hidup model
  (`src/lifecycle/`): promosi/rollback + retraining otomatis berbasis drift.
  `cd mlops && python -m src.cli` (subcommand default `validate`) memvalidasi
  kelima model dan **exit 1 bila ada yang gagal** (dipakai di CI).
  `python -m src.cli versions|promote vN|rollback [--use-case <uc>]` mengelola
  versi aktif **semua use case** (`models/active_version.json`; pin = path relatif,
  mis. `performance/v2`); `python -m src.cli retrain [--current CSV]`
  menjalankan drift → latih → gate → promosi/rollback; `python -m src.cli
  monitor` mengirim alert webhook (`ALERT_WEBHOOK_URL`) bila drift terdeteksi;
  `python -m src.cli performance --current CSV` mengevaluasi ROC AUC produksi
  dan rollback otomatis bila turun melewati toleransi
  (`src/lifecycle/performance.py`); `python -m src.cli fairness` mengaudit
  keadilan turnover (impact ratio four-fifths, DP/EO/equalized-odds diff) dan
  **exit 1 bila ada pelanggaran** (`src/monitoring/fairness.py`, juga tersedia
  sebagai `POST /monitoring/fairness`); `python -m src.cli fairness-gate`
  mencegah **regresi** keadilan di CI (baseline `models/fairness_baseline.json`,
  `src/monitoring/fairness_gate.py`); `python -m src.cli feedback [--export CSV]`
  mengelola umpan balik produksi (`src/monitoring/feedback.py`, `POST /feedback`);
  `python -m src.cli validate-data <csv>` memvalidasi dataset mentah (kontrak,
  rentang, kategori, target, **tolak PII**) sebelum data nyata dipakai
  (`src/validation/data_validation.py`). Penjadwalan: `docker/cron/` +
  `docker/k8s/cronjobs.yaml`. `python -m src.train
  --version vN` melatih turnover dari `data/` lalu langsung menjalankan gate yang
  sama. Model registry membaca `models/<dir>/model_metadata.json`;
  metrik diambil dari `metrik_holdout_test`/`metrik_evaluasi`/`metrik`, atau root
  metadata untuk survival (`c_index`).
- **`deep_learning/`** — MLP murni NumPy (tanpa PyTorch/TensorFlow) untuk turnover,
  plus baseline klasik. Kontrak fitur turnover **dipakai ulang dari**
  `hr_intelligence_api/app/features/features.py`. `cd deep_learning && python -m src.cli compare`.
- **`hr_application/`** — Laravel (HRIS, konsumen API ML). `php artisan test`
  (SQLite in-memory, `RefreshDatabase`). Selain HRIS inti, tersedia modul
  **Fase 7**: Recruitment/ATS (`JobOpening`/`Candidate` + `RecruitmentService`),
  Performance (`PerformanceGoal`/`PerformanceReview`), Compensation
  (`CompensationComponent`), Documents (`EmployeeDocument`), dan Compliance
  (`StatutoryReport` + `StatutoryReportService`, tarif di `config/statutory.php`).
  Konvensinya: tiap modul = migrasi + model (`HasFactory`/`RecordsAudit`) +
  policy (daftar di `AppServiceProvider`) + form request + controller + Blade di
  `resources/views/<modul>/` + test feature. Hanya `admin` yang boleh menghapus;
  `admin`/`hr-manager` boleh membuat/mengubah.
- **`hr_dashboard/`** — React + Vite. Default `VITE_DATA_MODE=demo` (fixtures lokal);
  set `live` untuk memakai API. Proxy dev: `/api` → 8000, `/payroll-api` → 8300
  (prefix `/payroll-api` agar tidak bentrok dengan route SPA `/payroll`).
  `cd hr_dashboard && npm install && npm run dev`. Lint memakai ESLint 9 **flat
  config** (`eslint.config.js`, bukan `.eslintrc`); test memakai Vitest + Testing
  Library (`npm run lint`, `npm test`). Keduanya dijalankan di CI.

## Perintah penting

Semua perintah notebook dijalankan dari **root repo** (notebook mencari `data/` relatif ke cwd).

```bash
# API
cd hr_intelligence_api && python -m pytest tests/ -q

# Data sintetis (deterministik, seed 42)
python scripts/generate_absenteeism_data.py
python scripts/generate_performance_data.py
python scripts/generate_payroll_data.py
python scripts/generate_attrition_survival_data.py
```

Dependensi ML lengkap: `numpy pandas scikit-learn scipy joblib matplotlib seaborn
xgboost lightgbm catboost shap lifelines nbformat nbclient`.

Versi model yang dilayani API diatur lewat env `MODEL_VERSION` (default
`v2_mitigated`, versi turnover paling adil):

```bash
cd hr_intelligence_api && MODEL_VERSION=v1 python -m uvicorn app.main:app
```

## Toolchain CI (jangan diturunkan tanpa alasan)

- **PHP CI = 8.4.** `composer.lock` mengunci `symfony/* v8.1.x` yang butuh
  PHP >= 8.4.1. Menjalankan `composer install` di 8.3 gagal ("lock file does
  not contain a compatible set of packages"). Jangan menurunkan versi symfony
  untuk memaksa 8.3 — `laravel/framework ^13` mensyaratkan Symfony 8.
- **Node CI = 24.** `jsdom` 30 menarik `undici` 8.x yang memakai
  `worker_threads.markAsUncloneable` (Node >= 22.19). Di Node 20, Vitest crash
  dengan `webidl.util.markAsUncloneable is not a function`.
- **Satu sumber versi Python: `requirements/base.txt`.** Semua
  `<modul>/requirements.txt` memakai `-r ../requirements/base.txt` dan hanya
  menambah dependensi khusus modul. Jangan menulis pin versi berbeda per modul
  (mis. scikit-learn) — inilah yang dulu menyebabkan drift.
- **Runtime Python = 3.14.** Dipin di `.python-version`, ditegakkan di CI
  (`python-version: "3.14"` + langkah `Assert Python 3.14`) dan di tiap
  `Dockerfile` (`FROM python:3.14-slim`). Semua pin `base.txt` punya wheel
  `cp314` atau `py3-none-any`, jadi tidak perlu build dari sumber. Jangan
  menurunkan ke <3.14 tanpa alasan; `numpy==2.5.3`/`scipy==1.18.1` sendiri sudah
  mensyaratkan `>=3.12`.
- **`pandas` di `requirements/base.txt` harus `<3.0`** (dipakai 2.3.3).
  `lifelines==0.30.3` (model survival CoxPH) mensyaratkan `pandas<3.0`.
- **Verifikasi perubahan dependensi** dengan
  `pip install --dry-run -r <modul>/requirements.txt` di venv bersih, lalu
  jalankan test seluruh modul. `requirements.txt` bukan lagi hasil freeze;
  `pytest` ikut ter-pin di `base.txt` (CI memasangnya terpisah).
- **`hr_dashboard` lint** memakai ESLint 9 flat config (`eslint.config.js`).
  ESLint 9 tidak membaca `.eslintrc`, dan flag `--ext` sudah dihapus.

## Konvensi

- **Bahasa dokumentasi:** Indonesia.
- **Kontrak fitur turnover** harus identik di empat tempat: `notebooks/train_model*.ipynb`,
  `hr_intelligence_api/app/features/features.py`, `mlops/src/train/pipeline.py`, dan
  notebook analisis. `tanggal_acuan` = `2025-01-01`.
- **Jangan** menaruh data pribadi asli di repo. Dataset harus anonim/sintetis.
- Model baru → buat versi baru (`models/<nama>/vN/`), jangan timpa versi lama (mendukung rollback).
- Perubahan pada nama fitur/rumus/reference date = perubahan breaking → training ulang + versi baru.
- Setiap model HR wajib dipertimbangkan audit fairness (lihat `USE_CASES.md` #16).

## Titik penting (jangan diubah tanpa alasan)

- `hr_intelligence_api/app/core/config.py` — `MODEL_VERSION` (default `v2_mitigated`) & path artifact.
  Artifact berada di **root** repo (`models/`), bukan di dalam folder API.
- `hr_intelligence_api/app/services/model_service.py` — `model_version` properti (bukan nama algoritma).
- `hr_intelligence_api/app/core/exceptions.py` — handler 422 harus tetap JSON-serializable.
- `models/v2/model_metadata.json` — `versi_model` dipakai API untuk laporan versi.

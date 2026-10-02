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
- **`mlops/`** — registry, validasi model (gate deploy), drift PSI, metrics.
  `cd mlops && python -m src.cli` memvalidasi kelima model dan **exit 1 bila ada
  yang gagal** (dipakai di CI). Model registry membaca `models/<dir>/model_metadata.json`;
  metrik diambil dari `metrik_holdout_test`/`metrik_evaluasi`/`metrik`, atau root
  metadata untuk survival (`c_index`).
- **`deep_learning/`** — MLP murni NumPy (tanpa PyTorch/TensorFlow) untuk turnover,
  plus baseline klasik. Kontrak fitur turnover **dipakai ulang dari**
  `hr_intelligence_api/app/features/features.py`. `cd deep_learning && python -m src.cli compare`.
- **`hr_dashboard/`** — React + Vite. Default `VITE_DATA_MODE=demo` (fixtures lokal);
  set `live` untuk memakai API. Proxy dev: `/api` → 8000, `/payroll-api` → 8300
  (prefix `/payroll-api` agar tidak bentrok dengan route SPA `/payroll`).
  `cd hr_dashboard && npm install && npm run dev`.

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

Versi model yang dilayani API diatur lewat env `MODEL_VERSION` (default `v2`):

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
- **`pandas` di `hr_intelligence_api/requirements.txt` harus `<3.0`** (dipakai
  2.3.3). `lifelines==0.30.3` (model survival CoxPH) mensyaratkan
  `pandas<3.0`; menaikkan pandas ke 3.x membuat `pip install` mustahil.
- **`requirements.txt` = hasil freeze, tanpa pytest.** Job CI memasang pytest
  terpisah. Jangan tambah pin baru sembarangan; kalau memang perlu, verifikasi
  dengan `pip install --dry-run -r requirements.txt` di venv bersih.
- **`hr_dashboard` lint** memakai ESLint 9 flat config (`eslint.config.js`).
  ESLint 9 tidak membaca `.eslintrc`, dan flag `--ext` sudah dihapus.

## Konvensi

- **Bahasa dokumentasi:** Indonesia.
- **Kontrak fitur turnover** harus identik di tiga tempat: `notebooks/train_model*.ipynb`,
  `hr_intelligence_api/app/features/features.py`, dan notebook analisis. `tanggal_acuan` = `2025-01-01`.
- **Jangan** menaruh data pribadi asli di repo. Dataset harus anonim/sintetis.
- Model baru → buat versi baru (`models/<nama>/vN/`), jangan timpa versi lama (mendukung rollback).
- Perubahan pada nama fitur/rumus/reference date = perubahan breaking → training ulang + versi baru.
- Setiap model HR wajib dipertimbangkan audit fairness (lihat `USE_CASES.md` #16).

## Titik penting (jangan diubah tanpa alasan)

- `hr_intelligence_api/app/core/config.py` — `MODEL_VERSION` (default v2) & path artifact.
  Artifact berada di **root** repo (`models/`), bukan di dalam folder API.
- `hr_intelligence_api/app/services/model_service.py` — `model_version` properti (bukan nama algoritma).
- `hr_intelligence_api/app/core/exceptions.py` — handler 422 harus tetap JSON-serializable.
- `models/v2/model_metadata.json` — `versi_model` dipakai API untuk laporan versi.

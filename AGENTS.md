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

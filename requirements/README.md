# Requirements (sumber tunggal)

Folder ini menyimpan **satu sumber versi** dependensi Python untuk seluruh modul
HR Intelligence System.

## Runtime

Seluruh modul Python memakai **Python 3.14**. Versi dipin di `.python-version`
(root repo) dan ditegakkan di CI (`python-version: "3.14"` + langkah
`Assert Python 3.14`) serta di tiap `Dockerfile` (`FROM python:3.14-slim`).

Semua pin di `base.txt` sudah menyediakan wheel `cp314` atau paket murni
`py3-none-any`, sehingga `pip install` di 3.14 tidak perlu kompilasi dari
sumber. Verifikasi resolusi tanpa memasang:

```bash
python3.14 -m pip install --dry-run -r hr_intelligence_api/requirements.txt
```

## Mengapa

Sebelumnya setiap modul punya pin versi sendiri, dan hasilnya tidak konsisten.
Contoh nyata: `hr_intelligence_api` mengunci `scikit-learn==1.9.1` (hasil
`pip freeze`), sementara `ai_hr_assistant`, `payroll_intelligence`, dan `mlops`
hanya menulis `scikit-learn>=1.4`. Di mesin yang berbeda, `>=1.4` bisa
menghasilkan versi berbeda-beda, sementara API tetap 1.9.1 — model yang sama
dilatih dan dilayani dengan dua versi library yang berbeda.

Selain itu `hr_intelligence_api/requirements.txt` adalah hasil freeze seluruh
environment: ikut terbawa `streamlit`, `jupyter`, `ipykernel`, `altair`,
`agent-detector`, dan `sentry-sdk` yang tidak dipakai API.

## Struktur

| File | Isi |
|---|---|
| `base.txt` | Pin kanonik: fastapi, uvicorn, pydantic, numpy, pandas, scikit-learn, scipy, joblib, httpx, python-dotenv, pytest |
| `../<modul>/requirements.txt` | `-r ../requirements/base.txt` + dependensi khusus modul |

Dependensi khusus modul (tidak ada di `base.txt`):

| Modul | Tambahan | Alasan |
|---|---|---|
| `hr_intelligence_api` | `lifelines==0.30.3` | Model survival CoxPH |
| `payroll_intelligence` | `statsmodels==0.15.0` | Forecasting Holt-Winters |
| `mlops` | — | Semua sudah di `base.txt` |
| `deep_learning` | — | Semua sudah di `base.txt` |
| `ai_hr_assistant` | — | Semua sudah di `base.txt` |

## Menambah / menaikkan versi

1. Ubah **hanya** `base.txt` (atau tambahkan dependensi khusus di modul terkait).
2. Verifikasi resolusi tanpa memasang:
   `pip install --dry-run -r <modul>/requirements.txt` (jalankan di Python 3.14).
3. Jalankan test seluruh modul (lihat `CONTRIBUTE.md`).
4. Jangan menulis versi berbeda di `<modul>/requirements.txt`.

> Catatan: `pandas` wajib `<3.0` (dipakai `2.3.3`) karena `lifelines==0.30.3`
> mensyaratkannya. `pandas==2.3.3` dan `statsmodels==0.15.0` sama-sama punya
> wheel `cp314`, jadi tetap kompatibel dengan Python 3.14.

## Catatan upgrade ke Python 3.14

Seluruh pin `base.txt` + dependensi khusus modul sudah diverifikasi terpasang
dan lulus test di **Python 3.14.7**:

| Modul | Hasil test (Python 3.14) |
|---|---|
| `hr_intelligence_api` | 36 passed |
| `ai_hr_assistant` | 50 passed |
| `deep_learning` | 17 passed |
| `mlops` | 103 passed |
| `payroll_intelligence` | 13 passed |

Pin yang dinaikkan saat pindah ke 3.14: `fastapi` `0.141.1` → `0.142.2` dan
`python-dotenv` `1.2.3` → `1.2.4`. Sisanya sudah mendukung 3.14 tanpa perubahan.

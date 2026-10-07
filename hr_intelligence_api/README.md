# HR Intelligence API

Service **FastAPI** untuk melayani prediksi lima use case Machine Learning HR:
turnover, absensi, kinerja, anomali payroll, dan survival attrition.

API ini adalah **satu-satunya tempat model ML dijalankan**. Modul lain
(`hr_application`, `hr_dashboard`, `ai_hr_assistant`, `payroll_intelligence`)
memanggilnya lewat HTTP dan tidak memuat file model sendiri.

> Dokumen ini menggantikan README lama yang keliru (isinya README aplikasi
> Laravel). Panduan operasional seluruh sistem ada di
> [`../PANDUAN_PENGGUNAAN.md`](../PANDUAN_PENGGUNAAN.md); konvensi repo ada di
> [`../AGENTS.md`](../AGENTS.md).

---

## 1. Teknologi

| Komponen | Teknologi |
| --- | --- |
| Framework | FastAPI |
| Runtime | Python 3.14 (CI memakai 3.14) |
| Model | scikit-learn, lifelines (CoxPH), joblib |
| Data | pandas, numpy, scipy |
| Dependensi | `requirements/base.txt` (sumber tunggal) + `lifelines` |

> **Perangkat: CPU-only.** Kelima model memakai scikit-learn & lifelines yang
> tidak punya backend GPU. API ini **tidak membutuhkan GPU** — cukup CPU + RAM.
> Lihat §4 untuk tuning CPU dan alasan kenapa GPU tidak memberi manfaat.

---

## 2. Menjalankan

```bash
cd hr_intelligence_api
pip install -r requirements.txt

HR_API_KEY=dev-secret uvicorn app.main:app --port 8000
```

Saat start, log menampilkan status pemuatan tiap model. Dokumentasi interaktif
nonaktif secara default; aktifkan dengan `ENABLE_DOCS=true` lalu buka
<http://127.0.0.1:8000/docs> (lihat §7).

Konfigurasi lewat environment:

| Variabel | Default | Arti |
| --- | --- | --- |
| `HR_API_KEY` | (kosong) | API key wajib; bila kosong semua request gagal 500 |
| `MODEL_VERSION` | `v2_mitigated` | Versi artifact turnover di `models/<versi>/` |
| `RATE_LIMIT_PER_MINUTE` | `120` | Batas request per menit per API key |
| `MAX_BATCH_SIZE` | `5000` | Batas karyawan per request batch |
| `HR_NUM_THREADS` | (kosong) | Batas thread BLAS/OpenMP (mis. `1`); kosong = default library |
| `COMPUTE_DEVICE` | `cpu` | Label perangkat komputasi (dilaporkan di `/health`) |
| `LOG_LEVEL` | `INFO` | Level logging |
| `ENABLE_DOCS` | `false` | Aktifkan `/docs`, `/redoc`, `/openapi.json` |
| `CORS_ALLOW_ORIGINS` | (kosong) | Origin browser yang diizinkan, dipisah koma |

---

## 3. Endpoint

| Method | Path | Auth | Keterangan |
| --- | --- | --- | --- |
| `GET` | `/health` | — | Status + jumlah model termuat |
| `GET` | `/model` | `X-API-Key` | Daftar use case |
| `GET` | `/model/{use_case}/info` | `X-API-Key` | Metadata, metrik, versi model |
| `GET` | `/model/info` | `X-API-Key` | Info model turnover (kompatibel lama) |
| `POST` | `/predict` | `X-API-Key` | Turnover (satu karyawan) |
| `POST` | `/predict/batch` | `X-API-Key` | Turnover (banyak karyawan) |
| `POST` | `/predict/absensi` | `X-API-Key` | Absen tinggi |
| `POST` | `/predict/absensi/batch` | `X-API-Key` | Batch absensi |
| `POST` | `/predict/kinerja` | `X-API-Key` | Kategori kinerja |
| `POST` | `/predict/kinerja/batch` | `X-API-Key` | Batch kinerja |
| `POST` | `/predict/payroll` | `X-API-Key` | Anomali payroll |
| `POST` | `/predict/payroll/batch` | `X-API-Key` | Batch payroll |
| `POST` | `/predict/survival` | `X-API-Key` | Survival attrition |
| `POST` | `/predict/survival/batch` | `X-API-Key` | Batch survival |

Semua endpoint prediksi & `/model*` memerlukan header `X-API-Key`. Endpoint
batch dibatasi `MAX_BATCH_SIZE` karyawan per request.

---

## 4. Use case, algoritma & kegunaan

| Use case | Algoritma (artifact) | Pertanyaan yang dijawab | Hasil utama |
| --- | --- | --- | --- |
| `turnover` | Soft Voting (ensemble LR + RF) | Siapa yang **berisiko keluar**? | `probabilitas_keluar`, `prediksi_keluar`, `level_risiko` |
| `absensi` | Logistic Regression | Siapa yang **berisiko absen tinggi**? | `probabilitas_absen_tinggi`, `level_risiko` |
| `kinerja` | Logistic Regression (ordinal) | **Kategori kinerja** periode berikutnya? | `kategori_prediksi`, `probabilitas`, `skor_ordinal` |
| `payroll` | Isolation Forest | Baris payroll mana yang **menyimpang**? | `skor_anomali`, `is_anomali`, `level` |
| `survival` | Cox Proportional Hazards (lifelines) | **Kapan** karyawan berisiko keluar? | `risiko_relatif`, `level_risiko`, `median_survival_bulan` |

> Algoritma di atas diambil dari `models/*/model_metadata.json`, bukan dikarang.
> README lama menyebut "Gradient Boosting"/"XGBoost"; itu tidak benar — artifact
> yang dilayani adalah Logistic Regression.

Artifact turnover punya tiga versi: `v1`, `v2`, dan `v2_mitigated`
(default, versi paling adil). Ganti lewat `MODEL_VERSION`:

```bash
MODEL_VERSION=v1 uvicorn app.main:app   # rollback ke v1
```

### Kegunaan pada data asli

Selama ini model dilatih pada data **anonim/sintetis** (`data/*.csv`). Saat
memakai data HR asli, perannya:

- **Turnover** — skrining retensi: tandai karyawan berisiko tinggi agar HR
  menjadwalkan 1-on-1/retensi lebih awal. Model dilatih ulang pada data asli
  (riwayat keluar) supaya probabilitasnya bermakna.
- **Absensi** — deteksi dini pola absen; dasarkan pada riwayat absen 30/90 hari
  nyata. Bermanfaat untuk manajemen kehadiran dan penjadwalan shift.
- **Kinerja** — proyeksi kategori kinerja untuk perencanaan coaching/bonus.
  Ini **estimasi ordinal**, bukan keputusan final; metadata menegaskan "jangan
  dipakai untuk keputusan PHK".
- **Payroll** — audit penggajian: IsolationForest menandai baris tak lazim
  (lembur/koreksi tak wajar) untuk ditinjau, bukan untuk memotong gaji otomatis.
- **Survival** — perencanaan suksesi & anggaran: memperkirakan *kapan* (bukan
  hanya *apakah*) risiko keluar terjadi, lewat median survival per karyawan.

### Kualitas data menentukan kualitas model

- **Target/label harus nyata** — turnover butuh status keluar, survival butuh
  `tanggal_masuk`/`tanggal_keluar`, kinerja butuh rating historis, payroll
  butuh baris yang sudah dilabeli anomali untuk validasi.
- **Retraining berkala** — pakai `mlops` (`python -m src.cli retrain`/`monitor`)
  agar drift terdeteksi dan model dipromosikan/di-rollback otomatis.
- **Data pribadi** — jangan menyimpan PII di repo; `validate-data` menolak kolom
  PII. Di produksi, simpan data asli di sistem HR, bukan di repo.
- **Fairness wajib** — setiap model HR harus lewat audit keadilan
  (`python -m src.cli fairness`), karena keputusan personalia berdampak hukum/etis.

### Perangkat & tuning CPU

Kelima model **murni CPU** dan tidak punya jalur GPU: scikit-learn/lifelines
tidak menyediakan backend CUDA, dan tidak ada library GPU (torch/tensorflow/
xgboost) di dependensi. `python:3.14-slim` sudah cukup; tidak ada `--gpus` di
Docker/compose/k8s.

Kenapa GPU tidak sepadan untuk beban ini:

- **Model & data kecil** (≈1000 baris) — operasi matriksnya mikro.
- **Latensi didominasi overhead tetap**, bukan komputasi. Hasil ukur lokal:
  `predict_batch` 1 baris ≈ 91 ms, 1000 baris ≈ 120 ms (≈0,12 ms/baris),
  5000 baris ≈ 156 ms (≈0,03 ms/baris). Artinya biaya per-request didominasi
  feature engineering + overhead Python, bukan FLOP.
- **Thread BLAS nyaris tidak berpengaruh** untuk batch kecil (113–117 ms pada
  `HR_NUM_THREADS` = default/1/4). Yang penting justru **membatasi** thread agar
  tidak oversubscription antar worker.

Setelan yang disarankan:

- **1 worker per container** dengan `HR_NUM_THREADS=1`, lalu tambah replika
  secara horizontal (bukan menambah core/GPU per pod). Ini menjaga latensi
  stabil dan rate limit per-proses tetap dapat diprediksi.
- **Warm-up** sudah otomatis: `load_all_models()` dipanggil di `lifespan` startup
  (terlihat dari log "Model '…' siap."). Tidak ada lazy-load di request pertama.
- **Pakai endpoint batch** (`/predict/*/batch`) untuk volume besar — jauh lebih
  efisien daripada memanggil `/predict` per karyawan.

`/health` mengembalikan `compute_device` dan `thread_limit` agar konsumen bisa
memastikan konfigurasi perangkat tanpa menebak.

---

## 5. Contoh

### Turnover

```bash
curl -X POST http://127.0.0.1:8000/predict \
  -H "X-API-Key: $HR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "EMP0001",
    "usia": 38,
    "masa_kerja_bulan": 172,
    "jam_lembur_bulanan": 2,
    "gaji_bulanan": 14514816,
    "tanggal_masuk": "2010-08-12",
    "tanggal_promosi": "2015-07-28",
    "departemen": "Marketing",
    "status_kerja": "Tetap",
    "pendidikan": "S1"
  }'
```

```json
{
  "employee_id": "EMP0001",
  "probabilitas_keluar": 0.68,
  "prediksi_keluar": 1,
  "level_risiko": "Tinggi"
}
```

### Absensi

```bash
curl -X POST http://127.0.0.1:8000/predict/absensi \
  -H "X-API-Key: $HR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": "EMP0001",
    "usia": 32,
    "masa_kerja_bulan": 48,
    "jarak_km": 12.5,
    "beban_kerja": 78.0,
    "lembur_jam_bulanan": 22.0,
    "kepuasan_kerja": 3.2,
    "riwayat_absen_90hari": 4,
    "departemen": "IT",
    "status_kerja": "Tetap",
    "pendidikan": "S1"
  }'
```

Skema lengkap tiap use case ada di `app/schemas/`; contoh payload ada di
`model_config.json_schema_extra` masing-masing skema.

---

## 6. Level risiko turnover

`level_risiko` memakai threshold model (batas `prediksi_keluar`), bukan 0.50
tetap, agar label risiko konsisten dengan prediksi:

```text
Rendah  : probabilitas < threshold/2
Sedang  : threshold/2 <= probabilitas < threshold
Tinggi  : probabilitas >= threshold
```

Untuk default `v2_mitigated` (threshold 0.31): `< 0.155` Rendah,
`0.155 – < 0.31` Sedang, `>= 0.31` Tinggi. Lihat
`app/services/risk_service.py`.

---

## 7. Keamanan

- **API key** dikirim lewat header `X-API-Key` dan dibandingkan dengan
  `HR_API_KEY`. Bila `HR_API_KEY` kosong, API mengembalikan 500 (fail closed).
- **Rate limit** in-memory per API key (`RATE_LIMIT_PER_MINUTE`). Batas ini
  per-proses: bila dijalankan dengan banyak worker, limit efektifnya berlipat.
  Untuk deployment multi-instance, gunakan rate limiter berbasis Redis.
- **CORS** hanya aktif bila `CORS_ALLOW_ORIGINS` diisi. `hr_dashboard` di dev
  memakai proxy Vite sehingga tidak butuh CORS; konsumen browser lain perlu
  mengisi variabel ini.
- **Docs interaktif** (`/docs`, `/redoc`, `/openapi.json`) nonaktif secara
  default agar tidak membuka skema tanpa kunci. Aktifkan dengan
  `ENABLE_DOCS=true` (mis. di lokal).

---

## 8. Struktur

```text
hr_intelligence_api/
├── app/
│   ├── api/routes/        # health, model, predict
│   ├── core/              # config, auth, middleware, exceptions, logging
│   ├── features/          # kontrak fitur (features.py)
│   ├── schemas/           # skema Pydantic per use case
│   └── services/          # model_service, prediction_service, risk_service
├── tests/                 # 41 test (pytest)
└── requirements.txt       # -r ../requirements/base.txt + lifelines
```

---

## 9. Test

```bash
cd hr_intelligence_api
python -m pytest tests/ -q
```

---

## 10. Integrasi di `hr_application`

`hr_application` memakai `HrIntelligenceService`; endpoint tiap use case diatur
di `config/hr_intelligence.php` (key `use_cases`). Env yang dipakai:
`HR_INTELLIGENCE_BASE_URL` dan `HR_INTELLIGENCE_API_KEY` (bukan
`HR_INTELLIGENCE_API_URL`).

```php
use App\Facades\HrIntelligence;

HrIntelligence::predictUseCase('absensi', $employeeData);
HrIntelligence::predictUseCaseBatch('payroll', $employeeList);
HrIntelligence::useCaseInfo('survival');
```

Batch dijalankan lewat `RunUseCaseBatchPredictionJob` dan dijadwalkan di
`routes/console.php`, atau manual:

```bash
php artisan hr-intelligence:predict absensi
php artisan hr-intelligence:predict payroll --sync
```

---

## License

Private / Proprietary. © 2026 All Rights Reserved.

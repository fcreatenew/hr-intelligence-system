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

`DriftMonitor` (`src/monitoring/drift_monitor.py`) menjalankan deteksi drift lalu
mengekspor hasilnya sebagai metrik Prometheus: gauge `drift_max_psi` dan
`drift_features_total` per use case, serta counter `drift_alerts_total` dengan
label `level`/`use_case` ketika drift terdeteksi. Aturan alert di
`monitoring/alerts/alerts.yml` membaca `drift_max_psi` (peringatan > 0.1, kritis
> 0.2). Riwayat evaluasi dan alert terbaru tersedia lewat endpoint:

```text
GET /monitoring/drift/history[?use_case=turnover]
GET /monitoring/drift/alerts
```

---

## Audit Fairness

Model turnover dipakai untuk keputusan ketenagakerjaan, sehingga termasuk
**high-risk AI** (EU AI Act Annex III; NYC Local Law 144; UU PDP 27/2022).
Audit bias yang sebelumnya hanya ada di
`notebooks/05_fairness_audit_turnover.ipynb` kini dipindahkan ke
`src/monitoring/fairness.py` agar dapat diuji dan dijalankan berkala.

```bash
python -m src.cli fairness                 # audit versi aktif; exit 1 bila ada pelanggaran
python -m src.cli fairness --version v2 --min-group-size 30
```

Atau lewat API: `POST /monitoring/fairness`.

### Gate regresi fairness (CI)

Karena dataset sintetis saat ini memang masih punya disparitas, gate fairness
di CI dibuat **regression-aware**: yang dicegah adalah **perburukan** keadilan,
bukan keberadaan pelanggaran itu sendiri. Gate membandingkan audit saat ini
dengan baseline tersimpan (`models/fairness_baseline.json`) dan **exit 1** bila:

1. impact ratio sebuah atribut turun lebih dari `--tolerance` (default 0.02); atau
2. atribut yang sebelumnya lolos four-fifths kini melanggar.

```bash
python -m src.cli fairness-gate                      # jalankan gate (dipakai CI)
python -m src.cli fairness-gate --update-baseline    # tetapkan ulang baseline
```

Gate dijalankan otomatis di job `model-validation` CI. Perbarui baseline hanya
setelah meninjau perubahan keadilan secara sadar.

Yang dihitung per atribut (`usia` dipecah per rentang, `departemen`,
`status_kerja`, `pendidikan`):

| Metrik | Arti | Ideal |
|---|---|---|
| demographic parity difference | selisih selection rate antar kelompok | mendekati 0 |
| impact ratio (four-fifths) | min/max selection rate | >= 0,80 |
| equal opportunity difference | selisih TPR antar kelompok | mendekati 0 |
| equalized odds difference | maks(selisih TPR, selisih FPR) | mendekati 0 |

Kelompok dengan sampel di bawah `min_group_size` (default 20) tidak dipakai
untuk menghitung disparitas agar satu kelompok mungil tidak mendikte
kesimpulan. Dataset ini **tidak** memuat atribut gender/etnis; keterbatasan itu
dilaporkan di `ringkasan.catatan`.

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

Retraining otomatis sudah tersedia di `src/lifecycle/retraining.py` dan
menyatukan komponen yang ada menjadi satu keputusan yang dapat diaudit:

```text
data produksi
     │  DriftMonitor.evaluate (PSI)
     ▼
drift terdeteksi? ── tidak ──▶ selesai (versi aktif tidak berubah)
     │ ya
     ▼
latih versi baru (src.train) di models/<vN>/
     │
     ▼
gate validate_record ── gagal ──▶ tolak, versi aktif tetap
     │ lulus
     ▼
metrik kandidat ≥ metrik incumbent?
     │ ya                    │ tidak
     ▼                       ▼
promosikan (pin)          tolak, versi aktif tetap
```

Kandidat yang ditolak tetap tersimpan sebagai artifact versi baru
(append-only) agar dapat diperiksa; yang berubah hanyalah pin versi aktif.

Jalankan lewat CLI:

```bash
# Bandingkan data produksi terhadap data training; latih & promosikan bila perlu
python -m src.cli retrain --current ../data/produksi_turnover.csv

# Paksa latih walau drift tidak terdeteksi (mis. retraining berkala)
python -m src.cli retrain --force

# Hanya promosikan bila kandidat minimal +0.01 lebih baik dari incumbent
python -m src.cli retrain --force --min-delta 0.01
```

Atau lewat API MLOps: `POST /retraining/run`.

### Penjadwalan retraining

Crontab container (`mlops/docker/cron/crontab`) menjalankan tiga tugas harian:
`monitor` (01:00), `retrain` (02:30), dan `validate` (03:00). Jalankan lewat
`docker compose` (service `mlops-cron`) atau terapkan CronJob Kubernetes:

```bash
kubectl apply -f mlops/docker/k8s/cronjobs.yaml
```

### Rollback berbasis performa produksi

Retraining bereaksi terhadap drift **input**. Untuk menangkap penurunan
performa **output**, `src/lifecycle/performance.py` mengevaluasi ROC AUC pada
data umpan balik berlabel (kolom `skor` dan `label_aktual`). Bila turun
melebihi `tolerance` dibanding metrik holdout versi aktif, versi sebelumnya
dipin kembali otomatis:

```bash
python -m src.cli performance --current ../data/feedback_turnover.csv
python -m src.cli performance --current ../data/feedback_turnover.csv --tolerance 0.03
```

Atau lewat API: `POST /monitoring/performance`. Bila belum ada pin (tidak ada
versi sebelumnya), hasilnya `turun_tanpa_pin` dan tidak mengubah apa pun.

### Umpan balik produksi (prediksi + label)

Monitoring performa butuh pasangan prediksi–label nyata. `src/monitoring/feedback.py`
menyimpannya ke satu berkas JSONL append-only, lalu mengekspornya sebagai CSV
yang otomatis memuat kolom kanonik `skor` dan `label_aktual`, sehingga bisa
langsung dipakai `performance`/`retrain`:

```bash
python -m src.cli feedback                                   # ringkasan umpan balik
python -m src.cli feedback --export ../data/feedback_turnover.csv
python -m src.cli feedback --clear
```

Sistem HR / job ETL mengirim label lewat API MLOps:

```bash
curl -X POST http://127.0.0.1:8400/feedback \
  -H 'Content-Type: application/json' \
  -d '{"prediction":{"employee_id":"EMP1","usia":30},
       "actual":{"target_turnover":1}}'
```

Endpoint lain: `GET /feedback`, `POST /feedback/export`.

## Validasi Asupan Data

Sebelum data nyata/anonim dipakai training, validasi kontraknya dengan
`src/validation/data_validation.py`. Validator memakai ulang
`RAW_COLUMNS_REQUIRED` dari pipeline training sehingga tidak pernah menyimpang,
dan **menolak kolom berpotensi PII** (nama, NIK, email, dst.):

```bash
python -m src.cli validate-data hr_turnover.csv       # exit 1 bila ada masalah
python -m src.cli validate-data data_baru.csv --min-rows 500
```

Yang diperiksa: jumlah baris minimum, kolom wajib, PII, rasio nilai kosong,
rentang numerik, domain kategori, dan target biner. Langkah ini ikut berjalan di
CI.

## Siklus Hidup Versi (Promosi & Rollback)

Versi aktif **semua use case** dipin di `models/active_version.json` — sumber
kebenaran tunggal yang dibaca MLOps dan API HR Intelligence. Nilai pin adalah
path relatif di bawah `models/` (mis. `v2` untuk turnover, `performance/v2`
untuk kinerja).

```bash
python -m src.cli versions                       # versi tersedia + versi aktif
python -m src.cli versions --use-case kinerja
python -m src.cli promote v2_mitigated           # pin versi aktif turnover
python -m src.cli promote v2 --use-case kinerja  # pin versi aktif use case lain
python -m src.cli rollback                       # kembali ke versi sebelumnya
```

API MLOps menyediakan padanannya: `GET /lifecycle/{use_case}`,
`POST /lifecycle/{use_case}/promote`, `POST /lifecycle/{use_case}/rollback`.
Promosi memverifikasi artifact ada dan punya metrik sebelum dipin, sehingga
versi rusak tidak bisa dijadikan aktif. API HR Intelligence membaca pin yang
sama, jadi promosi/rollback langsung berlaku tanpa mengubah konfigurasi API.

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
│   ├── lifecycle/model_lifecycle.py  # pin versi aktif, promosi, rollback
│   ├── lifecycle/retraining.py       # orkestrasi drift → latih → gate → promosi
│   ├── validation/data_validation.py # validasi dataset mentah (kontrak + PII)
│   ├── monitoring/fairness.py        # audit fairness per atribut
│   ├── monitoring/fairness_gate.py   # gate regresi fairness (CI)
│   ├── monitoring/feedback.py        # simpan/ekspor prediksi + label produksi
│   ├── monitoring/drift.py           # PSI + deteksi drift per fitur
│   ├── monitoring/metrics.py         # MetricsCollector (format Prometheus)
│   ├── monitoring/drift_monitor.py   # DriftMonitor: metrik + alert + riwayat
│   ├── train/pipeline.py         # training turnover yang reproducible
│   ├── api.py                    # FastAPI
│   └── cli.py                    # validate / versions / promote / rollback / retrain
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

# Training Reproducible

`src/train/` adalah sumber kebenaran tunggal untuk pipeline training turnover:
pemuatan data, rekayasa fitur, pemilihan model, pemilihan ambang, dan penulisan
artifact. Notebook dipakai untuk eksplorasi; **artifact yang dipakai API
sebaiknya dihasilkan dari CLI ini** agar dapat direproduksi dan lolos gate.

```bash
cd mlops && python -m src.train --version v3 --seed 42
```

Alur pipeline:

```text
data/hr_turnover.csv
        │  buat_fitur (kontrak sama dengan features.py)
        ▼
split train/test stratified (seed tetap)
        │  bandingkan 3 kandidat + soft voting lewat CV ROC-AUC
        ▼
pilih ambang dari prediksi out-of-fold (bukan test)
        │
        ▼
refit pada seluruh data → models/<versi>/turnover_model.joblib
                          models/<versi>/model_metadata.json
        │
        ▼
gate `validate_record` (exit 1 bila model di bawah ambang)
```

Opsi:

| Flag | Arti | Default |
|---|---|---|
| `--version` | versi artifact tujuan (`models/<versi>/`) | `v2` |
| `--seed` | random state; seed sama = hasil sama | `42` |
| `--data` | nama berkas di `data/` | `hr_turnover.csv` |
| `--no-validate` | lewati gate validasi | — |

Jalankan dengan `--version` baru (mis. `v3`) agar versi lama tetap utuh untuk
rollback. Perubahan nama fitur, rumus, atau `tanggal_acuan` bersifat breaking:
latih ulang dan terbitkan versi baru.

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
cd mlops && python -m src.cli validate   # exit 1 bila ada model yang gagal
```

`validate` adalah subcommand default, sehingga `python -m src.cli` tetap
berperilaku sama seperti sebelumnya (dipakai di CI). Subcommand lain:
`versions`, `promote`, `rollback`, dan `retrain` — lihat bagian
[Retraining](#retraining) dan [Siklus Hidup Versi](#siklus-hidup-versi-promosi--rollback).

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

Contoh menjalankan Docker Compose (file ada di `mlops/docker/`):

```bash
docker compose -f docker/docker-compose.yml up --build
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

Kode sumber `mlops/` ditujukan untuk GNU AGPL-3.0-only pada rancangan migrasi ini. Lihat [LICENSE di root repository](../LICENSE). Dokumentasi buatan proyek mengikuti CC BY 4.0 sesuai [DOCUMENTATION_LICENSE.md](../DOCUMENTATION_LICENSE.md). Dependency, model, dataset, dan materi pihak ketiga tetap mengikuti lisensi masing-masing.

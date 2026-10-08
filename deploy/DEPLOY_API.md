# Deploy API gratis (tahap development)

Panduan meng-upload **HR Intelligence API** ke hosting gratis untuk uji coba.
Model tidak dibundel di image — artifact diunduh dari Hugging Face Hub saat
runtime (`app/core/hf_store.py`).

## Prasyarat

Pastikan artifact sudah ada di repo Hub. Dari root repo:

```bash
python scripts/upload_models_to_hf.py --repo <user>/hr-intelligence-models
```

Isi juga `active_version.json` di repo Hub (pin versi aktif per use case).

## Opsi 1 — Render (disarankan, gratis)

1. Render Dashboard → **New +** → **Blueprint** → pilih repo ini.
2. Render membaca `render.yaml` (root) dan membuat satu web service gratis
   (`hr-intelligence-api`).
3. Isi secret yang diminta:
   - `HR_MODEL_REPO` = `<user>/hr-intelligence-models`
   - `HF_TOKEN` = token read repo model (kosongkan bila publik)
   - `HR_API_KEY` dibuat otomatis (`generateValue`)
4. Setelah deploy, cek `https://<nama>.onrender.com/health` →
   `models_loaded: 5`.

Catatan free plan:

- Service **tidur** setelah ~15 menit idle; request pertama (cold start)
  ~30–50 detik.
- Cache Hugging Face **tidak persisten** (tanpa disk) → tiap container baru
  mengunduh ulang. Untuk produksi, pakai plan ber-disk atau
  `HR_MODEL_SOURCE=local`.
- Deploy pertama memuat kelima model saat health check `/health`. Bila
  health check gagal karena cold start, naikkan `healthCheckTimeout` di
  `render.yaml`.

## Opsi 2 — Hugging Face Spaces (Docker)

> Docker/Gradio Space kini butuh akun **PRO**. Bila akun PRO tersedia, pakai
> folder `hr_intelligence_api/space/` sebagai repo Space.

1. Buat Space baru dengan SDK **Docker**.
2. Salin `hr_intelligence_api/space/{README.md,Dockerfile}` ke root repo Space.
3. Space → **Settings → Variables and secrets**:
   - `GITHUB_TOKEN` (secret) — token read repo GitHub aplikasi (repo privat)
   - `APP_REPO` (variable) = `fcreatenew/hr-intelligence-system`
   - `APP_REF` (variable) = `main`
   - `HR_MODEL_REPO` = `<user>/hr-intelligence-models`
   - `HF_TOKEN` (secret) — token read repo model
   - `HR_API_KEY` (secret)
4. Space membangun image (kloning repo lewat token) dan mengekspos port 7860.

## Opsi 3 — VPS always-on (Oracle Cloud Always Free)

Untuk stack penuh tanpa cold start, pakai `docker compose` di VM ARM gratis.
Jalankan `docker compose up --build` di VM (lihat `docker-compose.yml`).

## Env var penting

| Variabel | Nilai | Keterangan |
|---|---|---|
| `HR_MODEL_SOURCE` | `hub` (default) / `local` | `local` = baca folder `models/` |
| `HR_MODEL_REPO` | `<user>/hr-intelligence-models` | repo artifact |
| `HR_MODEL_REVISION` | `main` / commit SHA | pin SHA agar beku |
| `HF_TOKEN` | token read | repo model privat |
| `HR_API_KEY` | secret | wajib; kosong = API 500 (fail closed) |
| `HR_NUM_THREADS` | `1` | CPU-only, hindari oversubscription |
| `HF_HOME` | path writable | cache unduhan (jangan mount read-only) |

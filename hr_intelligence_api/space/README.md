---
title: HR Intelligence API
emoji: 🧠
colorFrom: blue
colorTo: indigo
sdk: docker
app_port: 7860
pinned: false
---

# HR Intelligence API (demo)

FastAPI untuk prediksi HR multi-model (turnover, absensi, kinerja, payroll,
survival). Model **tidak** dibundel di image; artifact diunduh dari Hugging
Face Hub saat runtime.

## Deploy ke Hugging Face Spaces

Repo Space ini hanya berisi metadata + Dockerfile tipis. Image dibangun dari
repo aplikasi di GitHub (butuh **token GitHub read** untuk repo privat).

Setelah Space dibuat, isi **Settings -> Variables and secrets**:

| Nama | Jenis | Nilai |
|---|---|---|
| `GITHUB_TOKEN` | secret | token read repo GitHub aplikasi |
| `APP_REPO` | variable | `fcreatenew/hr-intelligence-system` (opsional) |
| `APP_REF` | variable | `main` (opsional; pin ke commit untuk beku) |
| `HR_MODEL_REPO` | variable | `fcreatenew/hr-intelligence-models` |
| `HF_TOKEN` | secret | token read repo model HF (bila privat) |
| `HR_API_KEY` | secret | API key yang diharapkan |

Endpoint: `GET /health`, `GET /models`, `POST /predict/*`.

> Catatan: Docker/Gradio Space kini butuh akun PRO. Untuk uji coba gratis,
> pakai Render (`render.yaml` di root repo).

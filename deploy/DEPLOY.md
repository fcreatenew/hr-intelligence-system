# Deploy & Demo

Panduan menampilkan HR Intelligence System untuk **uji coba/demo** memakai
layanan gratis. Semua komponen sudah punya Dockerfile, sehingga bisa dijalankan
di PaaS mana pun yang mendukung container.

> Ringkas: **model & dataset** ke Hugging Face Hub (gratis), **dashboard** ke
> Cloudflare Pages, **API Python & Laravel** ke Render (free web service).
> Untuk stack penuh always-on, pakai **Oracle Cloud Always Free**.

---

## Peta service

| Service | Folder | Port | Kebutuhan |
|---|---|---|---|
| `hr-api` | `hr_intelligence_api/` | 8000 | artifact `models/` (ter-copy ke image) |
| `payroll-api` | `payroll_intelligence/` | 8300 | `data/hr_payroll.csv` (ter-copy) |
| `hr-assistant` | `ai_hr_assistant/` | 8100 | knowledge base (in-repo); LLM opsional |
| `hr-app` | `hr_application/` | 8080 | SQLite (ephemeral) |
| `dashboard` | `hr_dashboard/` | 80 | build statis + nginx proxy |

Dockerfile memakai **konteks build = root repo** (kecuali `hr_application` dan
`hr_dashboard` yang memakai konteks foldernya sendiri). Ini penting di PaaS:
set *build context* ke root untuk ketiga API Python.

---

## Resep A — Render (paling cepat)

1. Push repo ini ke GitHub.
2. Render Dashboard → **New +** → **Blueprint** → pilih repo.
3. Render membaca `render.yaml` dan membuat 5 service gratis.
4. Isi nilai untuk env yang diminta (`sync: false` / `generateValue` sudah
   otomatis untuk secret).

Yang perlu diperhatikan:

- **URL antar-service otomatis** lewat `fromService: RENDER_EXTERNAL_URL`.
  Tidak ada jaringan privat seperti docker-compose, jadi jangan pakai
  `http://hr-api:8000`.
- **Dashboard memakai nginx reverse proxy** (satu origin), sehingga tidak
  perlu CORS. Bila memakai static site, aktifkan blok `dashboard-static` di
  `render.yaml` dan isi `CORS_ALLOW_ORIGINS` di `hr-api`.
- **Free web service tidur** setelah ~15 menit idle. Request pertama menunggu
  ~30–50 detik (cold start).
- **Kuota 750 jam/bulan** dihitung total untuk semua free web service.
  Lima service yang sering tidur masih aman.
- **SQLite ephemeral**: `hr-app` mereset DB tiap redeploy/restart. Untuk
  persistensi, pindah ke Postgres (Neon/Supabase) dan ubah `DB_*`.

Env lengkap per service ada di `deploy/env/`.

### Dashboard sebagai static site (tanpa Docker)

Bila ingin dashboard tanpa container, pakai blueprint varian
**`deploy/render/render.static.yaml`**: salin isinya ke root sebagai
`render.yaml` (mengganti yang default), lalu buat Blueprint.

Bedanya dengan blueprint default:

| | Default (`render.yaml`) | Varian (`deploy/render/render.static.yaml`) |
|---|---|---|
| Dashboard | Docker + nginx proxy | Static Site (tanpa container) |
| CORS | Tidak perlu (satu origin) | **Wajib** — isi `CORS_ALLOW_ORIGINS` manual setelah deploy |
| Build | Docker build | `npm run build` |

> Jangan menjalankan kedua blueprint di akun yang sama tanpa mengganti nama
> service: Render mencocokkan service berdasarkan `name`.

Alternatif manual (host statis lain):

1. Build: `cd hr_dashboard && npm ci && npm run build` (output `hr_dashboard/dist`).
2. Host `dist` di **Cloudflare Pages** / Vercel / Netlify / GitHub Pages.
3. Isi `VITE_DATA_MODE=live`, `VITE_HR_API_BASE=<URL hr-api>`,
   `VITE_PAYROLL_API_BASE=<URL payroll-api>` saat build.
4. Set `CORS_ALLOW_ORIGINS=<URL dashboard>` di `hr-api`.

Tanpa API pun dashboard bisa dibuka: set `VITE_DATA_MODE=demo` (fixtures lokal).

---

## Resep B — Oracle Cloud Always Free (stack penuh, always-on)

Satu VM ARM gratis selamanya, menjalankan `docker compose` seperti lokal.
Tanpa cold start.

```bash
REPO_URL=https://github.com/<user>/<repo>.git bash deploy/oracle-cloud/setup.sh
```

Skrip menyiapkan Docker, meng-clone repo, membuat `.env` (secret acak), lalu
menjalankan stack. Dashboard tersedia di `http://<IP-VM>:8081`.

Untuk domain + HTTPS, pakai Caddy di host:
`deploy/oracle-cloud/Caddyfile` (ganti `demo.example.com`).

Catatan: VM ARM — semua base image sudah punya varian arm64, jadi aman.
Buka port 80/443 di security list Oracle.

---

## Resep C — Hugging Face Hub

Hugging Face kini **hanya Static Space yang gratis**; Gradio/Docker Space
membutuhkan PRO. Karena itu HF dipakai untuk model, dataset, dan halaman
showcase.

```bash
pip install huggingface_hub
export HF_TOKEN=hf_...

python scripts/upload_to_hf.py --target model   --repo <user>/hr-intelligence-models
python scripts/upload_to_hf.py --target dataset --repo <user>/hr-intelligence-datasets
python scripts/upload_to_hf.py --target space   --repo <user>/hr-intelligence-demo
```

`--dry-run` menampilkan isi staging tanpa mengunggah. Kartu model/dataset ada
di `hf/model_card.md` dan `hf/dataset_card.md`.

Setelah deployment Resep A/B selesai, isi URL-nya di **`hf/space/config.json`**
lalu unggah ulang Space — halaman akan mengaktifkan tautan demo secara otomatis
(tanpa menyunting HTML):

```json
{
  "dashboard": "https://dashboard.onrender.com",
  "hr_api": "https://hr-api.onrender.com/docs",
  "payroll_api": "https://payroll-api.onrender.com/docs",
  "assistant": "https://hr-assistant.onrender.com/docs",
  "source_code": "https://github.com/fcreatenew/hr-intelligence-system"
}
```

Tautan yang dibiarkan kosong tetap nonaktif, jadi tidak ada tombol menuju
halaman mati.

---

## Resep D — Koyeb (alternatif Render)

Lihat `deploy/koyeb/README.md`. Koyeb memberi 1 service gratis
(scale-to-zero) dengan Dockerfile; build context harus root repo.

---

## Checklist keamanan

- [ ] `HR_API_KEY`, `ASSISTANT_API_KEY`, `APP_KEY` diisi **secret acak** dan
      disimpan sebagai secret platform (bukan di image/repo).
- [ ] `ENABLE_DOCS=false` untuk demo publik.
- [ ] `VITE_HR_API_KEY` **kosong** di produksi (user login lewat dashboard).
- [ ] `CORS_ALLOW_ORIGINS` diisi origin dashboard saja (bukan `*`).
- [ ] Jangan commit `.env` asli. `.gitignore` sudah menutupnya.

## Batasan demo

| Batasan | Dampak | Mitigasi |
|---|---|---|
| Cold start PaaS | Request pertama lambat | Pinger berkala (cek ToS), atau Resep B |
| SQLite ephemeral | Data hilang saat redeploy | Postgres (Neon/Supabase) |
| Scheduler/queue Laravel | Job terjadwal tidak andal saat tidur | Nonaktifkan untuk demo |
| LLM berbayar | Biaya per request | `LLM_PROVIDER=mock` atau free tier |
| Embedding neural | Berat/berbiaya | `EMBEDDING_BACKEND=lsa` (default) |

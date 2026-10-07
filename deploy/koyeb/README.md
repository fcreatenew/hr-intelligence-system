# Alternatif: Koyeb (free tier)

Koyeb cocok sebagai pengganti Render untuk ketiga API Python. Koyeb
menjalankan container (Firecracker microVM), mendukung Dockerfile, dan punya
satu layanan gratis yang scale-to-zero.

## Ringkasan

| Aspek | Koyeb |
|---|---|
| Layanan gratis | 1 service (Eco), scale-to-zero |
| Cold start | Ya (bangun saat ada request) |
| Build | Dockerfile atau buildpack |
| Konfigurasi | UI, CLI (`koyeb`), atau GitHub integration |

## Langkah (per API)

Ulangi untuk `hr_intelligence_api`, `payroll_intelligence`, dan
`ai_hr_assistant`.

1. **New App** di dashboard Koyeb → pilih GitHub repo ini.
2. **Builder**: Dockerfile.
   - `hr_intelligence_api`: Dockerfile `hr_intelligence_api/Dockerfile`,
     build context = root repo.
   - `payroll_intelligence`: Dockerfile `payroll_intelligence/Dockerfile`,
     build context = root repo.
   - `ai_hr_assistant`: Dockerfile `ai_hr_assistant/Dockerfile`,
     build context = root repo.
3. **Port**: 8000 / 8300 / 8100 sesuai service.
4. **Health check**: `/health`.
5. **Environment**: isi dari `deploy/env/<modul>.env.example`.

> Koyeb memerlukan **build context = root repo** karena setiap Dockerfile
> menyalin `requirements/base.txt` lewat path relatif `../requirements/`.
> Set build context ke root, bukan ke folder modul.

## Catatan

- Koyeb tidak punya private network antar-app pada free tier seperti compose.
  Isi URL antar-service dengan URL publik Koyeb masing-masing.
- Untuk dashboard, tetap pakai Cloudflare Pages (mode `demo`) atau Render
  (nginx proxy). Koyeb hanya 1 service gratis, jadi prioritaskan API.

## CLI (opsional)

```bash
# Contoh deploy hr_intelligence_api
koyeb app init hr-api \
  --git github.com/<user>/<repo> \
  --git-branch main \
  --git-builder docker \
  --git-docker-dockerfile hr_intelligence_api/Dockerfile \
  --git-docker-context . \
  --port 8000:http \
  --env MODEL_VERSION=v2_mitigated \
  --env HR_API_KEY=<secret>
```

Detail: <https://www.koyeb.com/docs>

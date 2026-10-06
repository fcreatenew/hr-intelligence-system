# Checklist monitoring HR Intelligence System.

## Health
- [ ] `/health` HR Intelligence API mengembalikan daftar model yang termuat
- [ ] `/health` MLOps melaporkan registry & validasi model
- [ ] Semua model terdaftar lulus validasi (`python -m src.cli`)

## Metrik
- [ ] Volume request per use case
- [ ] Latensi respons (p50, p95)
- [ ] Tingkat error per use case
- [ ] Distribusi skor prediksi
- [ ] Jumlah prediksi berisiko tinggi

## Drift
- [x] PSI fitur produksi vs data referensi dihitung berkala
- [x] Ambang: stabil < 0.1, peringatan 0.1-0.2, kritis > 0.2
- [x] Drift kritis memicu evaluasi ulang model
- [x] Alert dikirim otomatis lewat webhook (`ALERT_WEBHOOK_URL`) saat drift terdeteksi
- [x] Riwayat alert tersedia di `GET /monitoring/drift/alerts`

## Retraining
- [x] Trigger: drift kritis, penurunan metrik, atau jadwal berkala
- [x] Versi model baru dicatat di `models/` dengan metadata lengkap
- [x] Model baru melewati validasi sebelum dijadikan versi aktif
- [x] Rollback tersedia (`python -m src.cli rollback` / `POST /lifecycle/{use_case}/rollback`)
- [x] Notifikasi/pengiriman alert (`mlops/src/monitoring/alerting.py`, CLI `monitor`)
- [x] Retraining terjadwal (cron container & CronJob Kubernetes)
- [x] Rollback otomatis saat performa produksi turun (`src/lifecycle/performance.py`)

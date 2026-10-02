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
- [ ] PSI fitur produksi vs data referensi dihitung berkala
- [ ] Ambang: stabil < 0.1, peringatan 0.1-0.2, kritis > 0.2
- [ ] Drift kritis memicu evaluasi ulang model

## Retraining
- [ ] Trigger: drift kritis, penurunan metrik, atau jadwal berkala
- [ ] Versi model baru dicatat di `models/` dengan metadata lengkap
- [ ] Model baru melewati validasi sebelum dijadikan versi aktif

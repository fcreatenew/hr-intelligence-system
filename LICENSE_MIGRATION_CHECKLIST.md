# Checklist Migrasi Lisensi — Review

Branch ini menyiapkan target GNU AGPL-3.0-only untuk kode sumber dan CC BY 4.0 untuk dokumentasi. **Jangan gabungkan branch sebelum item yang relevan ditinjau dan ditandai selesai.**

## Hak cipta dan kontribusi

- [ ] Pastikan pemilik proyek memiliki hak atau izin yang cukup untuk melisensikan ulang setiap komponen yang akan dicakup.
- [ ] Tinjau riwayat pull request dan kontribusi eksternal; catat file/kontribusi yang masuk dari pihak lain.
- [ ] Pastikan kontribusi yang sebelumnya ditandai MIT dapat dipakai dalam versi baru dengan mempertahankan pemberitahuan dan kewajiban MIT yang relevan.
- [ ] Selesaikan konflik/pernyataan `Private / Proprietary` lama di README modul, terutama `hr_intelligence_api/`, `hr_dashboard/`, `payroll_intelligence/`, `mlops/`, dan `ai_hr_assistant/`.
- [ ] Tinjau kode yang berasal dari employer, sekolah, klien, template, generator, atau repositori lain.
- [ ] Pastikan kontributor baru memahami target AGPL dan dokumentasi CC BY 4.0.

## Komponen pihak ketiga

- [ ] Inventarisasi dependency dan lisensinya; periksa kompatibilitas dan kewajiban notice.
- [ ] Periksa lisensi model, tokenizer, bobot, dataset pelatihan, serta model/dataset yang diunggah ke Hugging Face secara terpisah.
- [ ] Pastikan `hf/model_card.md` dan `hf/dataset_card.md` akurat untuk artefak yang benar-benar diterbitkan.
- [ ] Pastikan skrip unggah tidak menyalin lisensi kode utama ke repositori model/dataset.
- [ ] Periksa data HR agar tidak ada data pribadi/rahasia yang diterbitkan.

## Sebelum merge

- [ ] Baca ulang seluruh pemberitahuan lisensi setelah perubahan.
- [ ] Jalankan test/CI yang relevan dan dry-run `scripts/upload_to_hf.py` untuk target model dan dataset.
- [ ] Pastikan versi/commit lama tetap terdokumentasi sebagai versi yang pernah menggunakan MIT; jangan menyatakan izin MIT lama dicabut.
- [ ] Minta tinjauan hukum jika hak kontribusi atau cakupan karya turunan masih meragukan.

## Status

- [ ] Audit hak cipta dan kontribusi selesai.
- [ ] Audit dependency/model/dataset selesai.
- [ ] Semua pemilik hak yang diperlukan telah menyetujui cakupan lisensi baru.
- [ ] Branch siap ditinjau dan digabungkan.

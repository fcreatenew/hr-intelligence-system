# Gap Audit — HR Intelligence System

Dokumen ini adalah snapshot risiko dan celah yang perlu diverifikasi terhadap branch default. Status fitur tidak boleh disimpulkan dari dokumentasi saja: cek kode, test, dan hasil CI sebelum menyatakan fitur selesai.

## Prinsip audit

- Branch/PR yang belum merge bukan bagian dari rilis default.
- Metrik dari data sintetis bukan bukti performa di organisasi nyata.
- Status keamanan/CI harus merujuk pada workflow yang benar-benar dijalankan.
- Jangan gunakan data karyawan, payroll, kredensial, atau rahasia nyata untuk reproduksi issue publik.

## Prioritas P0 — kelayakan penggunaan

| Area | Celah / risiko | Kriteria penutupan |
|---|---|---|
| Provenance dan lisensi | Lisensi kode, dependency, model, bobot, dan dataset dapat berbeda. Checklist migrasi masih berisi item yang belum diselesaikan. | Catat hak/provenance per komponen; jangan redistribusikan artefak yang izinnya belum jelas. |
| Validitas model | Sebagian evaluasi menggunakan data sintetis; fairness dan generalisasi perlu diverifikasi pada holdout yang sesuai. | Evaluasi dapat direproduksi, memakai data berizin, menghindari leakage, serta melaporkan keterbatasan dan ketidakpastian. |
| Privasi HR | Data HR bersifat sensitif dan prediksi dapat memengaruhi pekerja. | Minimisasi data, akses least-privilege, retensi, audit, dan human review teruji. |

## Prioritas P1 — kemampuan produk

| Area | Celah / risiko | Kriteria penutupan |
|---|---|---|
| Attendance | Impor CSV bukan otomatis berarti kompatibel dengan semua mesin absensi; lembur membutuhkan aturan shift, istirahat, zona waktu, dan koreksi. | Preview impor, validasi per baris, idempotensi, aturan terdokumentasi, serta tests shift lintas tengah malam. |
| Employee Self-Service | Portal karyawan harus membatasi data ke record pengguna yang terautentikasi. | Uji akses lintas akun untuk profil, cuti, dan slip gaji; otorisasi di server. |
| Integrasi layanan | Sistem modular rentan terhadap perubahan skema, timeout, autentikasi, dan versi model yang tidak kompatibel. | Contract tests dan smoke tests berjalan di CI dengan data sintetis. |
| Observability MLOps | Drift score saja tidak membuktikan degradasi kualitas model. | Pantau drift, kalibrasi, outcome tertunda, fairness dengan sampel memadai, alert, dan prosedur rollback. |
| Keamanan dependency | Dependency dapat memiliki CVE atau secret tidak sengaja masuk riwayat Git. | Pemindaian otomatis, triage temuan, dan proses rotasi secret terdokumentasi. |

## Prioritas P2 — kematangan produk

- Dukungan workflow onboarding/offboarding, checklist dan penetapan aset.
- Jadwal interview, penawaran kerja, dan pipeline kandidat dengan kontrol akses dokumen.
- Performance review dan goal setting dengan audit perubahan serta proses banding.
- Rekomendasi pelatihan berbasis skill gap, bukan profil sensitif atau inferensi yang tidak relevan.
- Pelaporan tenaga kerja dengan filter waktu yang benar, definisi metrik konsisten, dan ekspor yang aman.
- Aksesibilitas dashboard, lokalisasi, dan dukungan pagination/filter untuk dataset besar.

## Verifikasi sebelum menutup gap

1. Tunjukkan file/route/service yang mengimplementasikan perilaku.
2. Tambahkan test untuk kasus sukses, input invalid, otorisasi, dan kegagalan dependency.
3. Jalankan test komponen serta CI yang relevan.
4. Perbarui README/ROADMAP hanya setelah bukti di atas tersedia.

Snapshot ini bukan sertifikasi keamanan atau persetujuan produksi. Lakukan review kode dan validasi lingkungan target sebelum deployment.

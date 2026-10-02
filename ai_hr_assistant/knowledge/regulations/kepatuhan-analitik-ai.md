# Kepatuhan Penggunaan Analitik HR dan AI

> **Tag:** regulasi, ai, fairness, bias, tata kelola, kepatuhan

## Tujuan

Analitik HR dan AI dipakai untuk **membantu** pengambilan keputusan, bukan
menggantikan penilaian manusia. Dokumen ini menetapkan batas penggunaannya.

## Prinsip Fairness

Model HR wajib dipertimbangkan audit fairness terhadap kelompok yang relevan,
misalnya departemen, pendidikan, dan status kerja.

* Ketimpangan hasil antar kelompok harus diukur dan didokumentasikan.
* Bila ditemukan ketimpangan bermakna, tim data harus menempuh langkah mitigasi.
* Hasil mitigasi harus diaudit ulang sebelum model dipakai luas.

## Batasan Keputusan Otomatis

Dilarang membuat keputusan berikut hanya berdasarkan skor model:

* pemutusan hubungan kerja (PHK)
* penurunan jabatan atau hukuman disiplin
* penolakan kenaikan gaji atau promosi

Skor model adalah sinyal awal yang harus diverifikasi manusia dengan bukti
obyektif.

## Transparansi

Setiap model memiliki metadata: versi, tanggal pelatihan, algoritma, ambang
keputusan, dan metrik evaluasi. Metadata ini dapat diakses lewat HR Intelligence
API pada endpoint info model.

## Explainability

Untuk keputusan yang berdampak pada karyawan, alasan yang dapat dipahami harus
dapat dijelaskan kepada pihak yang berkepentingan.

## Audit Berkala

Audit fairness dan evaluasi performa model dilakukan secara berkala dan setiap
kali model dilatih ulang atau diganti versinya.

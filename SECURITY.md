# Security Policy

## Ruang lingkup

Kebijakan ini mencakup kerentanan keamanan pada kode dan konfigurasi HR Intelligence System yang dikelola di repository ini.

## Melaporkan kerentanan

**Jangan** melaporkan kerentanan yang belum diperbaiki melalui issue publik jika laporan tersebut dapat membantu pihak lain mengeksploitasi sistem.

Gunakan fitur **Private vulnerability reporting** pada tab **Security** repository jika tersedia. Jika fitur itu tidak tersedia, hubungi maintainer melalui kanal kontak yang tercantum pada profil GitHub pemilik repository dan minta kanal pelaporan privat.

Sertakan, bila aman:
- komponen atau file yang terdampak;
- langkah reproduksi yang minimal;
- dampak yang mungkin terjadi;
- versi/commit yang terdampak;
- saran mitigasi, jika ada.

Jangan sertakan password, API key, token, data karyawan, atau informasi pribadi. Redaksi semua data sensitif dari bukti dan log.

## Praktik keamanan untuk kontribusi

- Jangan commit file .env, credential, private key, atau token.
- Gunakan data HR sintetis untuk pengembangan dan pengujian.
- Validasi otorisasi dan akses data, khususnya pada endpoint yang memproses data karyawan.
- Tambahkan test untuk perbaikan keamanan bila memungkinkan.
- Laporkan kebocoran rahasia yang sudah terlanjur masuk repository kepada maintainer agar dapat ditangani; menghapus file dari commit terbaru saja tidak selalu mencabut rahasia yang sudah terekspos.

## Respons

Maintainer akan berusaha meninjau laporan secara wajar, mengonfirmasi penerimaan bila memungkinkan, dan mengoordinasikan perbaikan sebelum detail kerentanan dipublikasikan.

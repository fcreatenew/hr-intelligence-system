# HR Intelligence System

<p align="center">
  <strong>Platform modular untuk alur kerja SDM, analitik, pembelajaran mesin, dan layanan kecerdasan buatan.</strong>
</p>

<p align="center">
  <a href="https://github.com/fcreatenew/hr-intelligence-system/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/fcreatenew/hr-intelligence-system/actions/workflows/ci.yml/badge.svg?branch=main"></a>
  <a href="https://www.python.org/"><img alt="Python 3.14" src="https://img.shields.io/badge/Python-3.14-3776AB?logo=python&logoColor=white"></a>
  <a href="https://nodejs.org/"><img alt="Node.js 24" src="https://img.shields.io/badge/Node.js-24-339933?logo=nodedotjs&logoColor=white"></a>
  <a href="https://www.php.net/"><img alt="PHP 8.4" src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white"></a>
</p>

**HR Intelligence System** adalah proyek rekayasa modular yang mengeksplorasi integrasi alur kerja sumber daya manusia (SDM), analitik, pembelajaran mesin, dan layanan AI—mulai dari validasi data dan evaluasi model hingga API, aplikasi, dasbor, serta MLOps.

Proyek ini bermula dari **prediksi pergantian karyawan** dan berkembang menjadi platform yang mencakup:

- **Aplikasi SDM** — alur kerja sistem informasi sumber daya manusia (HRIS) dan pengelolaan karyawan
- **Dasbor SDM** — analitik dan visualisasi tenaga kerja
- **API HR Intelligence** — inferensi model dan integrasi layanan
- **Analitik penggajian** — analisis penggajian dan deteksi anomali
- **Pembelajaran mesin dan pembelajaran mendalam** — eksperimen yang dapat direproduksi dan evaluasi model
- **Asisten AI SDM** — komponen asisten berbasis LLM dan RAG
- **MLOps** — validasi model, pengelolaan versi, dan alur kerja operasional

> **Status proyek:** Masih aktif dikembangkan. Kemampuan, tingkat kematangan, dan kebutuhan penerapan berbeda untuk setiap komponen. Periksa dokumentasi dan pengujian masing-masing komponen sebelum menggunakannya di lingkungan produksi.

## Tautan Cepat

| Kebutuhan | Dokumentasi |
|---|---|
| Memahami arsitektur dan cakupan proyek | [Arsitektur](#arsitektur) |
| Menjalankan layanan secara lokal | [Menjalankan dengan Docker](#menjalankan-dengan-docker) |
| Menyiapkan komponen tertentu | [Komponen](#komponen) |
| Meninjau hasil dan keterbatasan model | [Hasil Model](./MODEL_RESULTS.md) dan [Model Cards](./models/model_cards/README.md) |
| Melihat rencana pengembangan | [Peta Jalan](./ROADMAP.md) |
| Berkontribusi pada kode atau dokumentasi | [CONTRIBUTING.md](./CONTRIBUTING.md) dan [CONTRIBUTE.md](./CONTRIBUTE.md) |
| Melaporkan masalah keamanan | [Kebijakan Keamanan](./SECURITY.md) |
| Meninjau migrasi lisensi | [Daftar Periksa Migrasi Lisensi](./LICENSE_MIGRATION_CHECKLIST.md) |

**Utamakan privasi:** Gunakan data sintetis atau data yang penggunaannya telah mendapat izin yang sesuai untuk pengembangan dan demonstrasi. Jangan pernah memasukkan data karyawan asli, kredensial, rahasia, atau informasi SDM yang bersifat rahasia ke dalam repositori.

## Model AI dan Pembelajaran Mesin

Artefak model yang digunakan atau dievaluasi dalam proyek ini tersedia di [koleksi HR Intelligence Models di Hugging Face](https://huggingface.co/fcreatenew/hr-intelligence-models/tree/main).

Sebelum menggunakan model, periksa berkas, kartu model, petunjuk penggunaan, keterbatasan, dan lisensinya. Pastikan model beserta bobotnya mengizinkan tujuan penggunaan Anda. Lisensi repositori perangkat lunak tidak otomatis berlaku untuk model AI atau kumpulan data.

Model yang disertakan dalam repositori ini didokumentasikan di [models/README.md](./models/README.md). Untuk asisten AI, lihat [ai_hr_assistant/README.md](./ai_hr_assistant/README.md).

Panduan pengguna tersedia dalam dua format:

- [PANDUAN_PENGGUNAAN.md](./PANDUAN_PENGGUNAAN.md) — panduan Markdown
- [PANDUAN_PENGGUNAAN.pdf](./PANDUAN_PENGGUNAAN.pdf) — panduan PDF (15 halaman)

Nama berkas panduan dipertahankan agar tautan dan penggunaan yang sudah ada tetap kompatibel. README utama ini menggunakan bahasa Indonesia.

---

## Arsitektur

Repositori ini menggunakan **arsitektur modular yang terdiri dari beberapa proyek**. Setiap komponen memiliki kode sumber, dependensi, pengujian, dan dokumentasi masing-masing.

- **hr_application** — aplikasi HRIS berbasis Laravel
- **hr_dashboard** — dasbor analitik berbasis React
- **hr_intelligence_api** — layanan FastAPI untuk inferensi pembelajaran mesin
- **payroll_intelligence** — analitik penggajian dan deteksi anomali
- **deep_learning** — eksperimen jaringan saraf
- **ai_hr_assistant** — asisten berbasis LLM dan RAG
- **mlops** — perangkat pengelolaan siklus hidup dan validasi model

### Prinsip Perancangan

- Setiap proyek memiliki tanggung jawab yang jelas.
- Setiap komponen dapat dikembangkan secara mandiri.
- Integrasi antarlayanan menggunakan kontrak yang jelas.
- Model pembelajaran mesin memiliki pengelolaan versi.
- Dokumentasi teknis ditempatkan bersama komponen terkait.
- Kredensial dan rahasia tidak disimpan di repositori.

---

## Komponen

### hr_application/

Aplikasi Sistem Informasi Sumber Daya Manusia (HRIS) berbasis Laravel dan PHP untuk staf SDM serta operasional karyawan. Aplikasi ini juga menggunakan layanan dari HR Intelligence API dan Payroll Intelligence.

**Status:** Fitur inti HRIS telah tersedia, termasuk pengelolaan karyawan, riwayat karyawan, kehadiran, cuti, penggajian, pelaporan, jejak audit, dan pengelolaan pengguna.

Fitur yang telah tersedia:

- Integrasi API pembelajaran mesin untuk kelima kasus penggunaan
- Pemetaan data karyawan menjadi masukan model melalui EmployeeFeatureMapper
- Prediksi kelompok terjadwal (bulanan, mingguan, dan triwulanan)
- Penyimpanan dan penelusuran riwayat prediksi
- Autentikasi, profil, pengaturan ulang kata sandi, serta peran admin dan hr-manager
- **Pengelolaan Karyawan:** operasi CRUD, pencarian, penyaringan, paginasi, dan terminasi
- **Riwayat Karyawan:** linimasa mutasi, jabatan, gaji, dan status
- **Kehadiran:** pencatatan harian, rekap periode, dan impor CSV dari sistem absensi
- **Cuti:** pengajuan, persetujuan, saldo cuti, dan kalender tim
- **Penggajian:** periode gaji, perhitungan berbasis kehadiran, finalisasi, slip gaji, dan ekspor
- **Pelaporan:** jumlah karyawan, pergantian karyawan, biaya per departemen, dan ekspor CSV
- **Jejak Audit** dan **Pengelolaan Pengguna**
- Fitur model terkait kehadiran dihitung dari catatan kehadiran, bukan diisi secara manual

Rencana modul proses SDM:

| No. | Modul | Potensi integrasi pembelajaran mesin |
|---|---|---|
| 1 | Rekrutmen / Sistem Pelacakan Pelamar | Penyaringan CV dan kecocokan kandidat dengan pekerjaan |
| 2 | Orientasi / Pengakhiran Hubungan Kerja | Analisis pergantian dan masa kerja karyawan |
| 3 | Pengelolaan Kinerja | Prediksi kinerja dan identifikasi karyawan berpotensi tinggi |
| 4 | Pelatihan dan Pengembangan | Rekomendasi pelatihan |
| 5 | Kompensasi dan Tunjangan | Deteksi anomali dan peramalan penggajian |
| 6 | Pengelolaan Waktu dan Kehadiran Lanjutan | Prediksi ketidakhadiran |
| 7 | Layanan Mandiri Karyawan | — |
| 8 | Pengelolaan Dokumen | — |
| 9 | Kepatuhan dan Pelaporan Wajib | — |

Rincian modul, rumus penggajian, dan jadwal prediksi tersedia di [hr_application/README.md](./hr_application/README.md). Tahapan pengembangan dan kriteria penyelesaian dijelaskan di [ROADMAP.md](./ROADMAP.md).

**Teknologi:** Laravel, PHP

Dokumentasi: [hr_application/README.md](./hr_application/README.md)

### hr_intelligence_api/

Layanan inferensi pembelajaran mesin dan akses model melalui API.

Fitur yang tersedia:

- Prediksi pergantian karyawan, ketidakhadiran, kinerja, anomali penggajian, dan analisis masa kerja
- Prediksi kelompok
- Informasi model untuk setiap kasus penggunaan
- Pemeriksaan kesehatan layanan
- Autentikasi API

**Teknologi:** Python, FastAPI, scikit-learn

Dokumentasi: [hr_intelligence_api/README.md](./hr_intelligence_api/README.md)

### hr_dashboard/

Aplikasi React dan Vite untuk visualisasi serta analisis data SDM.

Halaman yang tersedia mencakup Ringkasan, Tenaga Kerja, Pergantian Karyawan, Kehadiran, Penggajian, KPI SDM, dan Model. Dasbor dapat mengambil data dari HR Intelligence API dan Payroll Intelligence API. Secara bawaan, dasbor berjalan dalam **mode demo** menggunakan data contoh lokal. Atur variabel lingkungan VITE_DATA_MODE=live untuk menggunakan API aktif.

**Teknologi:** React, Vite, Recharts

Jalankan secara lokal dari direktori komponen dengan perintah npm install, lalu npm run dev.

Dokumentasi: [hr_dashboard/README.md](./hr_dashboard/README.md)

### payroll_intelligence/

Proyek analitik dan kecerdasan penggajian yang mencakup:

- Pengambilan dan transformasi data penggajian
- Perhitungan komponen gaji
- Analitik penggajian berdasarkan ringkasan, departemen, periode, dan lembur
- Deteksi anomali penggajian menggunakan Isolation Forest
- Peramalan penggajian menggunakan metode Holt dan musiman
- API REST dan antarmuka baris perintah

**Teknologi:** Python, FastAPI, scikit-learn, statsmodels

Jalankan pengujian dari direktori komponen dengan python -m pytest tests/ -q. Jalankan API dengan uvicorn src.api:app --port 8300.

Dokumentasi: [payroll_intelligence/README.md](./payroll_intelligence/README.md)

### deep_learning/

Eksperimen jaringan saraf menggunakan multilayer perceptron (MLP) berbasis NumPy murni untuk kasus penggunaan SDM, yang dievaluasi dengan membandingkannya terhadap model dasar klasik.

- MLP dengan propagasi balik dan penghentian dini, tanpa PyTorch atau TensorFlow
- Kontrak fitur pergantian karyawan yang selaras dengan HR Intelligence API
- Model dasar Logistic Regression dan kelas mayoritas sebagai pembanding
- Pencatatan eksperimen dalam format JSON, antarmuka baris perintah, dan API

**Teknologi:** Python, NumPy, FastAPI

Jalankan pengujian dari direktori komponen dengan python -m pytest tests/ -q. Bandingkan model dengan python -m src.cli compare.

Dokumentasi: [deep_learning/README.md](./deep_learning/README.md)

### ai_hr_assistant/

Asisten AI untuk kebutuhan SDM yang dibangun menggunakan model bahasa besar (LLM).

Fokus utama meliputi LLM, Retrieval-Augmented Generation (RAG), basis pengetahuan, embeddings, pencarian vektor, dan perangkat AI.

Asisten dapat menjawab pertanyaan tentang kebijakan dan prosedur menggunakan basis pengetahuan dengan pengambilan informasi serta sitasi. Asisten juga dapat memanggil kelima kasus penggunaan pembelajaran mesin—pergantian karyawan, ketidakhadiran, kinerja, anomali penggajian, dan analisis masa kerja—melalui HR Intelligence API.

**Teknologi:** Python, FastAPI, LLM, RAG

Dokumentasi: [ai_hr_assistant/README.md](./ai_hr_assistant/README.md)

### mlops/

Perangkat untuk mengelola siklus hidup pembelajaran mesin yang mencakup:

- Registri model dengan metadata model sebagai sumber acuan utama
- Validasi model sebagai gerbang kesiapan penerapan
- Deteksi pergeseran data menggunakan Population Stability Index (PSI) dan metrik berformat Prometheus
- API REST dan antarmuka baris perintah untuk validasi
- Docker Compose, alur kerja CI GitHub Actions, aturan peringatan, dan daftar periksa pemantauan

**Teknologi:** Python, FastAPI, Docker, CI/CD

Jalankan pengujian dari direktori komponen dengan python -m pytest tests/ -q. Jalankan python -m src.cli untuk memvalidasi semua model sebagai bagian dari gerbang CI.

Dokumentasi: [mlops/README.md](./mlops/README.md)

---

## Struktur Repositori

Struktur berikut menampilkan berkas dan direktori utama. Setiap komponen dapat memiliki berkas tambahan.

- **data/** — kumpulan data SDM contoh dan sintetis
- **requirements/base.txt** — definisi dependensi Python bersama
- **scripts/** — pembuatan kumpulan data, pembuatan panduan, dan utilitas verifikasi
- **notebooks/** — eksperimen pelatihan model, evaluasi, keadilan model, dan uji signifikansi
- **models/** — artefak model berversi dan laporan evaluasi
- **hr_intelligence_api/** — API inferensi pembelajaran mesin
- **hr_application/** — aplikasi HRIS berbasis Laravel
- **hr_dashboard/** — dasbor berbasis React
- **payroll_intelligence/** — layanan analitik penggajian
- **deep_learning/** — eksperimen jaringan saraf
- **ai_hr_assistant/** — asisten SDM dan sumber pengetahuannya
- **mlops/** — validasi model, pemantauan, dan perangkat CI

Berkas requirements/base.txt menjadi sumber acuan utama dependensi Python bersama. Berkas .python-version di direktori utama menetapkan Python 3.14 untuk modul Python.

---

## Model Pembelajaran Mesin Saat Ini

HR Intelligence API menyediakan **lima kasus penggunaan pembelajaran mesin**. Semuanya tersedia melalui API dan dapat dipanggil oleh aplikasi SDM serta asisten AI.

| No. | Kasus penggunaan | Endpoint | Model | Versi | Metrik utama |
|---|---|---|---|---|---|
| 1 | Pergantian karyawan | POST /predict | Ensemble Soft Voting (Logistic Regression + Random Forest) | v2_mitigated | ROC-AUC 0.6993 |
| 2 | Ketidakhadiran | POST /predict/absensi | Logistic Regression | v1 | ROC-AUC 0.8287 |
| 3 | Kinerja | POST /predict/kinerja | Ordinal Logistic Regression | v1 | Macro-F1 0.6357 |
| 4 | Anomali penggajian | POST /predict/payroll | Isolation Forest | v1 | PR-AUC 0.8709 |
| 5 | Analisis masa kerja hingga keluar | POST /predict/survival | Cox Proportional Hazards | v1 | C-index 0.8144 |

Tersedia tiga artefak model pergantian karyawan: v1, v2, dan v2_mitigated. Model v2_mitigated menggunakan pembobotan ulang dan menjadi model bawaan API.

Model pembelajaran mendalam untuk prediksi pergantian karyawan (MLP NumPy) juga tersedia di direktori deep_learning/ sebagai pembanding. Pada kumpulan data saat ini, model dasar klasik memberikan hasil yang lebih baik.

Informasi rekayasa fitur, evaluasi, metadata, audit keadilan model, dan uji signifikansi tersedia di:

- [models/README.md](./models/README.md)
- [MODEL_RESULTS.md](./MODEL_RESULTS.md)

## Kasus Penggunaan Pembelajaran Mesin SDM Lainnya

Pembelajaran mesin dapat digunakan untuk kebutuhan SDM selain prediksi pergantian karyawan. Kasus penggunaan lain, termasuk rekrutmen, pemrosesan bahasa alami, identifikasi karyawan berpotensi tinggi, dan segmentasi tenaga kerja, dijelaskan beserta data dan referensinya di [USE_CASES.md](./USE_CASES.md).

Perbandingan model secara terperinci dan hasil audit keadilan tersedia di [MODEL_RESULTS.md](./MODEL_RESULTS.md).

---

## Memulai

Klon repositori dan masuk ke direktorinya:

    git clone <repository-url>
    cd hr-intelligence-system

Setiap komponen memiliki lingkungan dan petunjuk penyiapan tersendiri. Versi runtime yang diperlukan:

- **Python 3.14** untuk modul Python, ditetapkan dalam .python-version dan diperiksa oleh CI serta Dockerfile
- **Node.js 24** untuk hr_dashboard
- **PHP 8.4** untuk hr_application

Lihat bagian 3 pada PANDUAN_PENGGUNAAN.md untuk petunjuk khusus setiap komponen.

Mulailah dari dokumentasi komponen yang ingin dikembangkan:

- [hr_application/README.md](./hr_application/README.md)
- [hr_intelligence_api/README.md](./hr_intelligence_api/README.md)
- [hr_dashboard/README.md](./hr_dashboard/README.md)
- [payroll_intelligence/README.md](./payroll_intelligence/README.md)
- [deep_learning/README.md](./deep_learning/README.md)
- [ai_hr_assistant/README.md](./ai_hr_assistant/README.md)
- [mlops/README.md](./mlops/README.md)

## Menjalankan dengan Docker

Jalankan layanan inti sekaligus menggunakan Docker Compose:

    HR_API_KEY=<secret> docker compose up --build

| Layanan | URL | Fungsi |
|---|---|---|
| hr-api | http://localhost:8000 | HR Intelligence API dan prediksi model |
| payroll-api | http://localhost:8300 | Analitik penggajian dan skor anomali |
| hr-app | http://localhost:8080 | HRIS Laravel untuk kehadiran, cuti, dan penggajian |
| dashboard | http://localhost:8081 | Dasbor React |
| assistant | http://localhost:8100 | Asisten AI SDM |

Aplikasi SDM dikonfigurasi agar dapat mengakses HR API dan Payroll API melalui jaringan internal Compose. Atur variabel lingkungan Laravel APP_KEY dengan nilai yang valid, misalnya APP_KEY=base64:....

Dasbor menggunakan Nginx sebagai proksi terbalik. Jika diperlukan, atur alamat layanan API melalui HR_API_UPSTREAM dan PAYROLL_API_UPSTREAM. Lihat hr_dashboard/nginx.conf.template; nilai bawaan telah disesuaikan untuk Docker Compose.

## Demo dan Penerapan Gratis

Untuk pilihan penerapan di Render, Cloudflare Pages, Koyeb, Oracle Cloud, dan Hugging Face Hub, lihat:

- [deploy/DEPLOY.md](./deploy/DEPLOY.md) — panduan penerapan dan render.yaml
- [hf/space/](./hf/space) — halaman peragaan statis untuk Hugging Face Spaces
- [scripts/upload_to_hf.py](./scripts/upload_to_hf.py) — mengunggah model dan kumpulan data ke Hugging Face Hub

Secara ringkas, model dan kumpulan data dapat dihosting di Hugging Face Hub; dasbor dapat dijalankan di Cloudflare Pages dalam mode demo tanpa backend atau di Render; API Python dan Laravel dapat dijalankan sebagai layanan web Render; sedangkan seluruh layanan yang selalu aktif dapat dijalankan di Oracle Cloud Always Free melalui deploy/oracle-cloud/.

Panduan kontribusi tersedia di [CONTRIBUTE.md](./CONTRIBUTE.md).

---

## Filosofi Proyek

Proyek ini tidak hanya bertujuan membangun satu model pembelajaran mesin. Tujuannya adalah mengeksplorasi cara membangun sistem menyeluruh:

    Data
     ↓
    Sains Data
     ↓
    Pembelajaran Mesin
     ↓
    Model
     ↓
    API
     ↓
    Aplikasi
     ↓
    Dasbor
     ↓
    AI
     ↓
    MLOps
     ↓
    Produksi

Model tidak seharusnya berhenti sebagai eksperimen di notebook. Model perlu dikembangkan menjadi komponen yang dapat diintegrasikan dengan aplikasi praktis.

> **Bangun → Pelajari → Bagikan → Tingkatkan**

---

## Lisensi

### Kode Sumber

Kode sumber proyek ini dilisensikan di bawah **GNU Affero General Public License v3.0 only (AGPL-3.0-only)**. Baca teks lengkapnya di [LICENSE](./LICENSE). Lisensi ini berlaku untuk kode proyek, termasuk ai_hr_assistant/, kecuali jika suatu berkas atau komponen secara eksplisit menyatakan lisensi yang berbeda.

AGPL-3.0-only mengizinkan penggunaan, modifikasi, dan distribusi, termasuk untuk tujuan komersial. Distribusi versi yang telah dimodifikasi atau penyediaan perangkat lunak yang tercakup melalui jaringan dapat menimbulkan kewajiban untuk menyediakan kode sumber terkait. Baca teks lisensi untuk memahami ketentuan lengkapnya.

### Dokumentasi

Dokumentasi yang dibuat khusus untuk proyek ini—seperti panduan, tutorial, diagram, dan materi penjelasan—dilisensikan di bawah **Creative Commons Attribution 4.0 International (CC BY 4.0)** sebagaimana dijelaskan dalam [DOCUMENTATION_LICENSE.md](./DOCUMENTATION_LICENSE.md). Materi pihak ketiga dan cuplikan kode perangkat lunak tidak otomatis tercakup oleh lisensi dokumentasi ini.

### Model, Kumpulan Data, dan Komponen Pihak Ketiga

Lisensi perangkat lunak utama **tidak otomatis berlaku** untuk model AI, bobot model, tokenizer, kumpulan data, dependensi, atau aset pihak ketiga. Periksa lisensi dan pemberitahuan yang menyertai setiap artefak sebelum menggunakan, memodifikasi, atau mendistribusikannya kembali. Lihat [models/README.md](./models/README.md), [koleksi model di Hugging Face](https://huggingface.co/fcreatenew/hr-intelligence-models/tree/main), dan [THIRD_PARTY_NOTICES.md](./THIRD_PARTY_NOTICES.md).

### Versi Sebelumnya dan Atribusi

Perubahan lisensi pada versi saat ini tidak mencabut izin MIT yang telah diberikan kepada penerima versi atau salinan terdahulu. Pemberitahuan hak cipta dan lisensi yang berlaku untuk komponen pihak ketiga harus tetap dipertahankan.

Jika menemukan berkas dengan pemberitahuan lisensi atau kepemilikan yang bertentangan, silakan buat issue sebelum menggunakan kembali atau mendistribusikannya.

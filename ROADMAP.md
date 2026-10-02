# Roadmap HR Intelligence System

Dokumen ini adalah peta pengembangan yang **aktif dipelihara**. Setiap kali sebuah
fase selesai atau berubah, dokumen ini ikut diperbarui — termasuk saat ada
pekerjaan yang dibatalkan.

> **Catatan kejujuran.** Roadmap ini sebelumnya dirujuk dari `README.md` tetapi
> filenya belum pernah ada, sehingga pembaca tidak punya gambaran urutan
> pekerjaan. Dokumen ini dibuat untuk menutup celah itu. Status di sini
> mencerminkan kondisi kode yang benar-benar ada, bukan rencana.

Selisih antara status di dokumen ini dan kode di `main` dirinci di
[`GAP_AUDIT.md`](GAP_AUDIT.md) — termasuk pekerjaan yang masih menunggu PR dan
celah yang belum dimulai.

Legenda status:

| Simbol | Arti |
|---|---|
| ✅ | Selesai dan teruji |
| 🚧 | Sedang dikerjakan |
| ⬜ | Belum dimulai |

---

# 1. Kondisi Saat Ini

Sistem berjalan sebagai **arsitektur modular**: satu API ML pusat, satu aplikasi
HRIS (Laravel), satu dashboard, dan empat modul pendukung.

| Komponen | Peran | Status |
|---|---|---|
| `hr_intelligence_api` | Inference ML (5 use case) + auth + batch | ✅ |
| `hr_application` | HRIS (Laravel) + integrasi ML | 🚧 integrasi ✅, modul HRIS ⬜ |
| `hr_dashboard` | Visualisasi & analitik (React) | ✅ |
| `ai_hr_assistant` | Asisten HR (LLM + RAG) | ✅ |
| `payroll_intelligence` | Analitik, anomali, forecast payroll | ✅ |
| `deep_learning` | Eksperimen neural network (MLP NumPy) | ✅ |
| `mlops` | Registry, validasi, drift, CI | ✅ |

Model ML yang dilayani API:

| Use case | Model | Versi | Metrik |
|---|---|---|---|
| Turnover | Soft Voting (LR + RF) | v2 | ROC-AUC 0,6993 |
| Absensi | Logistic Regression | v1 | ROC-AUC 0,8287 |
| Kinerja | Logistic Regression (ordinal) | v1 | macro-F1 0,6357 |
| Payroll anomaly | Isolation Forest | v1 | PR-AUC 0,8709 |
| Survival | Cox Proportional Hazards | v1 | C-index 0,8144 |

---

# 2. Keputusan Arsitektur

## `hr_application` tetap Laravel

Keputusan: **mempertahankan Laravel/PHP** untuk aplikasi HRIS, tidak pindah ke
Node/Go/Python.

Alasan:

1. **Rencana produk adalah HRIS penuh.** Begitu butuh UI admin, autentikasi,
   otorisasi berbasis peran, antrian job, penjadwalan, dan ORM — Laravel sudah
   menyediakan semuanya. Berpindah framework berarti membangun ulang fondasi
   yang justru akan segera dibutuhkan.
2. **Tidak ada UI untuk diselamatkan saat ini.** Migrasi tidak akan menghemat
   pekerjaan frontend karena frontend-nya belum ada.
3. **Logika integrasi ML sudah teruji.** Bagian tersulit (pemetaan fitur,
   penanganan timeout/retry/rate-limit, normalisasi hasil) sudah berjalan dan
   punya 744 baris test.
4. **Tidak ada masalah performa yang perlu dipecahkan.** Beban kerjanya
   terjadwal (batch), bukan high-QPS — argumen utama pindah ke Go tidak berlaku.

Konsekuensi yang diterima: PHP menjadi satu-satunya runtime di repo yang bukan
Python/Node, sehingga menambah satu toolchain deploy.

## Modul ML tetap di Python

Model ML, analitik, dan asisten tetap Python. Laravel **memanggil** API, tidak
menjalankan model. Batas ini dijaga agar kontrak fitur tidak terduplikasi.

---

# 3. Peta Fase

```text
Fase 0  Fondasi ML + API                 ✅ selesai
Fase 1  Integrasi HRIS ↔ ML              ✅ selesai
Fase 2  HRIS inti (Employee Management)  ✅ selesai
Fase 3  Operasional HR (Attendance/Leave) ✅ selesai
Fase 4  Payroll run                      ✅ selesai
Fase 5  Pelaporan & administrasi         ⬜
Fase 6  Hardening produksi               ⬜
```

---

# 4. Fase 0 — Fondasi ML + API ✅

- ✅ Model turnover v1 (Logistic Regression) + v2 (ensemble)
- ✅ Tiga use case tambahan: absensi, kinerja, anomali payroll
- ✅ Survival analysis (Cox PH)
- ✅ Audit fairness + uji signifikansi + mitigasi bias
- ✅ `hr_intelligence_api`: 5 use case, batch, model info, health, `X-API-Key`
- ✅ `mlops`: registry, validasi, drift (PSI), metrics
- ✅ `payroll_intelligence`, `deep_learning`, `ai_hr_assistant`, `hr_dashboard`

Kriteria selesai: seluruh model dapat dipanggil lewat API dan teruji. **Tercapai.**

---

# 5. Fase 1 — Integrasi HRIS ↔ ML ✅

- ✅ `HrIntelligenceService` — klien HTTP dengan timeout, retry, pemetaan error
- ✅ `EmployeeFeatureMapper` — record karyawan → input model (per use case)
- ✅ `HrIntelligenceResultNormalizer` — normalisasi hasil ke skema seragam
- ✅ Tabel `hr_intelligence_predictions` — penyimpanan hasil dengan versi model
- ✅ Command + job batch untuk kelima use case
- ✅ Penjadwalan bulanan/mingguan/kuartalan
- ✅ Endpoint JSON admin di bawah gate `manage-hr-intelligence`

Kriteria selesai: prediksi dapat dijalankan terjadwal dan hasilnya tersimpan.
**Tercapai.**

---

# 6. Fase 2 — HRIS Inti (Employee Management) ✅

Fase ini mengubah `hr_application` dari "kerangka integrasi" menjadi aplikasi
yang benar-benar dapat dipakai staf HR.

## 2.1 Employee Management ✅

- ✅ Model `Employee` + tabel + atribut ML
- ✅ CRUD karyawan (create, read, update, delete)
- ✅ Validasi input (`StoreEmployeeRequest`, `UpdateEmployeeRequest`)
- ✅ Otorisasi berbasis peran (`EmployeePolicy`: hapus khusus `admin`)
- ✅ Daftar dengan pencarian, filter departemen, dan paginasi
- ✅ Deteksi NIK/email duplikat dengan pesan yang jelas
- ✅ Halaman Blade (bukan JSON saja) agar dapat dipakai tanpa klien API
- ✅ Detail karyawan + riwayat prediksi ML per karyawan
- ✅ Kolom ML kosong tetap `null` (bukan 0) agar tidak menyesatkan model
- ⬜ Riwayat perubahan (audit trail) karyawan
- ⬜ Import/export CSV

## 2.2 Autentikasi ✅

- ✅ Halaman login/logout
- ✅ Seeder akun admin awal (+ 3 karyawan contoh)
- ✅ Tamu diarahkan ke `/login` (bukan error)

## 2.3 Employee History ⬜

- ⬜ Riwayat jabatan/departemen/gaji
- ⬜ Riwayat mutasi & promosi
- ⬜ Linimasa per karyawan

**Kriteria selesai Fase 2:** staf HR dapat login, menambah/mengubah/mencari
karyawan, dan melihat riwayat perubahannya — tanpa menyentuh database langsung.

---

# 7. Fase 3 — Operasional HR ✅

## 3.1 Attendance ✅

- ✅ Pencatatan kehadiran harian (hadir, terlambat, izin, sakit, cuti, alpha)
- ✅ Satu catatan per karyawan per tanggal (dijaga di level DB + validasi)
- ✅ Jam masuk/keluar, menit terlambat, menit lembur, catatan
- ✅ Rekap per periode: kehadiran, keterlambatan, ketidakhadiran, cuti
- ✅ **Integrasi ML**: `riwayat_absen_90hari` dan `hari_absen_setahun` kini
  dihitung dari tabel `attendances` (`AttendanceMetricsService`), bukan lagi
  diisi manual — kolom manualnya dihapus dari form
- ⬜ Impor mesin absensi / fingerprint
- ⬜ Perhitungan lembur otomatis dari jam masuk/keluar

**Definisi fitur (penting agar konsisten dengan pelatihan model):**
`riwayat_absen_90hari` = jumlah hari izin + sakit + alpha dalam 90 hari
terakhir. Cuti **tidak** dihitung karena sudah direncanakan dan disetujui.
`hari_absen_setahun` = perhitungan yang sama pada rentang 365 hari.

## 3.2 Leave (Cuti) ✅

- ✅ Pengajuan cuti (tahunan, sakit, melahirkan, menikah, duka, lainnya)
- ✅ Alur persetujuan/penolakan oleh `admin` & `hr-manager`
- ✅ Saldo cuti per karyawan per tahun (jatah, terpakai, tersisa)
- ✅ Jumlah hari dihitung di server dari rentang tanggal (bukan input klien)
- ✅ Persetujuan menulis saldo + catatan absensi dalam satu transaksi
- ✅ Persetujuan ditolak bila saldo tidak mencukupi
- ✅ Pengajuan yang sudah diputuskan tidak bisa diputuskan ulang
- ⬜ Kalender cuti tim
- ⬜ Kuota per jenis cuti selain tahunan

**Keputusan penting:** hanya cuti `tahunan` yang memotong saldo. Cuti
melahirkan/duka/menikah tidak, karena jatahnya terpisah dan biasanya diatur
regulasi — bukan bagian dari jatah 12 hari.

**Kriteria selesai Fase 3:** ✅ data kehadiran dan cuti tercatat di HRIS dan
mengalir otomatis menjadi fitur model, bukan diisi manual.

---

# 8. Fase 4 — Payroll Run ✅

- ✅ Periode payroll + komponen gaji (tabel `payroll_periods` & `payroll_items`)
- ✅ Perhitungan lembur, potongan, total (dari absensi, bukan input manual)
- ✅ Ekspor komponen payroll ke CSV
- ✅ Integrasi ke `payroll_intelligence` untuk skor anomali

Catatan: hasil perhitungan disimpan per periode (bukan ditulis ulang ke
`employees`) agar setiap bulan dapat ditelusuri dan dihitung ulang. Rumus total
gaji identik dengan `payroll_intelligence` dan fitur model anomali payroll.

---

# 9. Fase 5 — Pelaporan & Administrasi ⬜

- ⬜ Laporan headcount, turnover, biaya per departemen
- ⬜ Ekspor (CSV/PDF)
- ✅ Manajemen pengguna & peran dari UI
- ✅ Halaman hasil prediksi ML di UI (bukan JSON lagi)

---

# 10. Fase 6 — Hardening Produksi ⬜

- ✅ CI root (`.github/workflows/ci.yml`) mencakup kelima modul + gate validasi model
- ⬜ Halaman login + rate limiting
- ⬜ Audit trail menyeluruh
- ⬜ Backup & retensi data
- ⬜ Dockerfile untuk `hr_application` & `hr_dashboard`
- ⬜ Model monitoring & drift alert otomatis (memanfaatkan `mlops`)
- ⬜ Retraining terjadwal + rollback model
- ⬜ Validasi ulang pada data nyata (saat ini data tambahan masih sintetis)

---

# 11. Utang Teknis yang Diketahui

Daftar ini jujur menyebut hal yang belum beres, agar tidak hilang dari pandangan.

| # | Utang | Dampak | Rencana |
|---|---|---|---|
| 1 | ~~Belum ada halaman login~~ | Sudah selesai di Fase 2.2 | Selesai |
| 2 | Data absensi/kinerja/payroll/survival sintetis | Metrik terlalu optimistis | Fase 6 |
| 3 | `v2_mitigated` belum jadi default | Model produksi masih kurang setara | Fase 6 |
| 4 | Audit fairness masih in-sample | Belum dapat dijadikan dasar keputusan | Fase 6 |
| 5 | Belum ada Dockerfile/CI `hr_application` | Deploy belum terstandar | Fase 6 |
| 6 | Halaman prediksi ML masih JSON | Belum ramah pengguna non-teknis | Fase 5 |
| 7 | Tidak ada audit trail karyawan | Perubahan data tidak tertelusur | Fase 2.1 |
| 8 | `deep_learning` MLP belum dipakai produksi | Eksperimen saja | Tidak diprioritaskan |
| 9 | Belum ada impor mesin absensi | Absensi masih diisi manual | Fase 5 |
| 10 | ~~Lembur belum dihitung dari jam masuk/keluar~~ | Sudah dihitung dari `overtime_minutes` absensi per periode | Selesai |
| 11 | Kalender cuti tim belum ada | HR belum bisa melihat tumpang tindih cuti | Fase 5 |
| 12 | Daftar nilai kategorikal sempat tidak sinkron | Form menawarkan nilai yang tidak dikenal pipeline, sehingga mapper diam-diam mengubahnya | Selesai — `HrCategories` jadi sumber tunggal |

---

# 12. Prinsip yang Dipegang

1. **Data dulu, model kemudian.** Model tanpa data yang benar tidak berarti.
2. **Kontrak fitur tunggal.** Feature engineering (transformasi menjadi vektor
   model) hanya di `hr_intelligence_api`. HRIS boleh menghitung *agregat domain*
   yang menjadi input mentah (mis. jumlah hari tidak hadir dari tabel absensi),
   tetapi tidak boleh meniru one-hot encoding, penskalaan, atau bobot model.
   Aturan praktisnya: HRIS menghitung fakta, API mengubahnya menjadi fitur.
3. **Jujur soal metrik.** Selisih yang tidak signifikan tidak diklaim sebagai
   peningkatan; model yang lebih setara diprioritaskan untuk keputusan nyata.
4. **Modul terpisah, kontrak jelas.** Setiap komponen punya test sendiri.
5. **Dokumentasi mengikuti kode.** Status di dokumen diperbarui bersamaan dengan
   pekerjaan, bukan belakangan.

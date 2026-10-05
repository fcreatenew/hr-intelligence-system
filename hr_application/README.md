# HR Application (HRIS)

Aplikasi **HRIS (Human Resource Information System)** berbasis **Laravel / PHP**.
Ini aplikasi bisnis yang dipakai staf HR sehari-hari, sekaligus **konsumen**
layanan ML: `hr_intelligence_api` (prediksi) dan `payroll_intelligence`
(skor anomali payroll).

Status modul ada di [`../ROADMAP.md`](../ROADMAP.md); katalog use case ML di
[`../USE_CASES.md`](../USE_CASES.md).

## Cara Menjalankan

```bash
cd hr_application
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed      # akun demo + karyawan, hari libur, absensi contoh
php artisan serve --port=8080   # 8000 dipakai hr_intelligence_api
```

Jalankan **scheduler** dan **queue worker** agar prediksi batch terjadwal
benar-benar tereksekusi (bukan hanya terdaftar):

```bash
php artisan schedule:work
php artisan queue:work --tries=3
```

Akun demo dari seeder: `test@example.com` / `password` (peran `admin`).

> Port default Laravel adalah 8000, sama dengan `hr_intelligence_api`. Jalankan
> aplikasi ini di port lain (mis. `8080`) agar keduanya bisa hidup bersamaan.

Peran pengguna (`App\Support\UserRoles`): `admin` dan `hr-manager`.

## Modul HRIS

### Employee Management

CRUD karyawan lengkap dengan halaman web (Blade):

| Route | Fungsi |
|---|---|
| `GET /employees` | Daftar + pencarian (nama/NIK/email) + filter departemen & status + paginasi |
| `GET /employees/create` | Form tambah karyawan |
| `POST /employees` | Simpan karyawan baru |
| `GET /employees/{id}` | Detail karyawan + 20 prediksi ML terakhir + linimasa riwayat |
| `GET /employees/{id}/edit` | Form ubah |
| `PUT /employees/{id}` | Simpan perubahan (mencatat riwayat) |
| `POST /employees/{id}/terminate` | Catat pemutusan hubungan kerja (tanggal + alasan) |
| `DELETE /employees/{id}` | Hapus (khusus `admin`) |

Hak akses diatur `App\Policies\EmployeePolicy`:

* `admin` dan `hr-manager` — lihat, tambah, ubah, terminasi
* hanya `admin` — hapus (destruktif, memutus riwayat)

Validasi memakai `StoreEmployeeRequest` / `UpdateEmployeeRequest`: NIK & email
unik, rentang nilai atribut ML dijaga (mis. kepuasan kerja 1–5, periode `YYYY-MM`).

Catatan penting: **kolom ML yang dikosongkan dibiarkan `null`**, bukan diisi 0.
Nilai default ditentukan mapper saat prediksi dijalankan, sehingga angka nol
tidak salah ditafsirkan sebagai data nyata.

### Employee History (Linimasa)

Perubahan penting karyawan dicatat sebagai *event* terstruktur di
`employee_histories` (`App\Models\EmployeeHistory`): mutasi departemen,
perubahan jabatan, perubahan gaji, perubahan status, dan terminasi. Berbeda
dengan `AuditLog` yang mencatat setiap perubahan atribut secara generik, tabel
ini menyajikan riwayat yang sudah diberi makna sehingga dapat dibaca sebagai
linimasa per karyawan di halaman detail.

### Autentikasi & Profil

| Route | Fungsi |
|---|---|
| `GET /login`, `POST /login` | Masuk (rate limit `throttle:login`, 5/menit per email+IP) |
| `POST /logout` | Keluar |
| `GET/POST /forgot-password` | Minta tautan reset kata sandi |
| `GET /reset-password/{token}`, `POST /reset-password` | Atur ulang kata sandi |
| `GET/PUT /profile` | Ubah profil sendiri |

Tamu yang membuka route terlindungi diarahkan ke `/login`.

### Manajemen Pengguna

CRUD pengguna + kirim ulang tautan reset (khusus `admin`) lewat
`UserController` (`/users`). Nilai peran dibatasi `UserRoles::ALL`.

### Attendance (Absensi)

Pencatatan kehadiran harian dengan rekap per periode.

| Route | Fungsi |
|---|---|
| `GET /attendances` | Daftar + filter rentang tanggal, karyawan, status |
| `GET /attendances/create` | Form catat absensi |
| `POST /attendances` | Simpan (satu catatan per karyawan per tanggal) |
| `GET /attendances/{id}/edit` | Form ubah |
| `PUT /attendances/{id}` | Simpan perubahan |
| `DELETE /attendances/{id}` | Hapus (khusus `admin`) |
| `GET /attendances/recap` | Rekap per karyawan: hadir, terlambat, tidak hadir, cuti |
| `GET /attendances/import/template` | Unduh template CSV impor |
| `POST /attendances/import` | Impor massal dari CSV (mesin absensi/fingerprint) |

Status: `hadir`, `terlambat`, `izin`, `sakit`, `cuti`, `alpha`. Impor massal
memakai `AttendanceImportService`/`AttendanceImportParser`, mendukung
*update* baris yang sudah ada, dan melaporkan baris yang dilewati.

### Leave (Cuti)

Pengajuan cuti dengan alur persetujuan dan saldo per karyawan.

| Route | Fungsi |
|---|---|
| `GET /leave-requests` | Daftar pengajuan (pending ditampilkan lebih dulu) |
| `GET /leave-requests/create` | Form pengajuan |
| `POST /leave-requests` | Simpan pengajuan |
| `GET /leave-requests/{id}` | Detail + form keputusan |
| `POST /leave-requests/{id}/approve` | Setujui |
| `POST /leave-requests/{id}/reject` | Tolak |
| `DELETE /leave-requests/{id}` | Hapus (khusus `admin`) |
| `GET /leave-requests/balances` | Saldo cuti semua karyawan |
| `GET /leave-requests/calendar` | Kalender cuti tim (grid bulanan, tumpang tindih terlihat) |

Jenis cuti (`LeaveRequest::TYPES`): `tahunan`, `sakit`, `melahirkan`,
`menikah`, `duka`, `lainnya`.

Persetujuan menulis tiga hal dalam satu transaksi: status pengajuan, saldo
cuti, dan catatan absensi harian bertipe `cuti`. Bila saldo tidak mencukupi,
persetujuan ditolak dan tidak ada yang tersimpan separuh jalan.

Hanya cuti `tahunan` yang memotong saldo. Cuti melahirkan/menikah/duka tidak,
karena jatahnya terpisah dari jatah 12 hari.

### Payroll

Penggajian bulanan berbasis periode. Setiap periode (YYYY-MM) menyimpan hasil
perhitungan per karyawan sehingga dapat ditelusuri dan dihitung ulang — angka
payroll tidak lagi ditulis langsung ke tabel `employees`.

| Route | Fungsi |
|---|---|
| `GET /payroll-periods` | Daftar periode + form buka periode baru |
| `POST /payroll-periods` | Buka periode (draft) |
| `GET /payroll-periods/{id}` | Detail: ringkasan komponen + baris per karyawan |
| `POST /payroll-periods/{id}/calculate` | Hitung ulang seluruh baris |
| `POST /payroll-periods/{id}/finalize` | Finalisasi (khusus `admin`, membekukan angka) |
| `GET /payroll-periods/{id}/export` | Ekspor komponen payroll ke CSV |
| `GET /payroll-periods/{id}/items/{item}/slip` | Slip gaji per karyawan |
| `DELETE /payroll-periods/{id}` | Hapus periode (khusus `admin`, bukan periode final) |

Rumus perhitungan (`App\Services\PayrollCalculationService`):

```
lembur_jam   = total menit lembur absensi periode ÷ 60 (dibulatkan ke bawah)
biaya_lembur = lembur_jam × tarif_lembur
potongan     = (hari alpha × gaji_pokok ÷ hari_kerja_per_bulan) + potongan tetap
total_gaji   = gaji_pokok + tunjangan + biaya_lembur − potongan
```

`hari_kerja_per_bulan` (default 22) dan `tarif_lembur` diatur lewat env
`PAYROLL_WORKING_DAYS_PER_MONTH` dan `PAYROLL_DEFAULT_OVERTIME_RATE`. Rumus
`total_gaji` sengaja identik dengan `payroll_intelligence` dan fitur model
anomali payroll di HR Intelligence API, sehingga angka HRIS dan model memakai
definisi yang sama. Cuti disetujui tidak dipotong; hanya `alpha` yang dipotong.

Setelah dihitung, baris payroll dapat diberi skor anomali oleh **Payroll
Intelligence** (opsional). Isi `PAYROLL_INTELLIGENCE_BASE_URL` (mis.
`http://localhost:8300`) untuk mengaktifkan. Bila kosong atau layanan mati,
perhitungan tetap berjalan tanpa skor — payroll tidak boleh gagal karena
layanan analitik.

### Reporting

| Route | Fungsi |
|---|---|
| `GET /reports` | Laporan headcount, turnover, dan biaya per departemen |
| `GET /reports/export` | Ekspor rekap ke CSV (streaming, hemat memori) |

Rasio turnover dihitung dari karyawan yang keluar dalam rentang periode
(memakai `termination_date` bila ada) dibagi total karyawan per departemen.

### Audit Trail

Semua perubahan pada karyawan, absensi, cuti, dan payroll tercatat otomatis di
`audit_logs` (event, nilai lama/baru, IP, user agent) lewat observer. Halaman
`GET /admin/audit-logs` menampilkan jejak tersebut (khusus `admin`).

### Fitur ML dihitung dari data, bukan diisi manual

`riwayat_absen_90hari` dan `hari_absen_setahun` sebelumnya adalah kolom yang
diisi manual di form karyawan. Sekarang keduanya dihitung dari tabel
`attendances` oleh `App\Services\AttendanceMetricsService`, sehingga tidak bisa
basi atau salah isi. Form karyawan hanya menampilkan nilainya sebagai keterangan.

Definisinya: jumlah hari **izin + sakit + alpha** dalam 90 hari (atau 365 hari)
terakhir. **Cuti tidak dihitung** karena sudah direncanakan dan disetujui —
memasukkannya akan membuat karyawan yang berlibur tampak seperti sering absen.

## Integrasi HR Intelligence

Aplikasi ini adalah konsumen dari **HR Intelligence API** (FastAPI). Selain
turnover, tersedia prediksi absensi, kinerja, anomali payroll, dan survival
attrition.

Konfigurasi (`.env`):

```env
HR_INTELLIGENCE_BASE_URL=http://localhost:8000
HR_INTELLIGENCE_API_KEY=
HR_INTELLIGENCE_TIMEOUT=10
HR_INTELLIGENCE_RETRY_TIMES=3
HR_INTELLIGENCE_RETENTION_DAYS=365
```

Endpoint tiap use case diatur di `config/hr_intelligence.php` (`use_cases`).

```php
use App\Facades\HrIntelligence;

HrIntelligence::predictUseCase('absensi', $employeeData);
HrIntelligence::predictUseCaseBatch('payroll', $employeeList);
HrIntelligence::useCaseInfo('survival');
```

Prediksi batch berjalan lewat `RunUseCaseBatchPredictionJob` (turnover:
`RunTurnoverBatchPredictionJob`), dijadwalkan di `routes/console.php`, dan bisa
dijalankan manual:

```bash
php artisan hr-intelligence:predict absensi
php artisan hr-intelligence:predict payroll --sync
php artisan hr-intelligence:predict-turnover
```

Jadwal batch (lihat `routes/console.php`):

| Use case | Jadwal |
|---|---|
| Turnover | Bulanan, tanggal 1 pukul 02:00 |
| Absensi | Mingguan, Senin pukul 03:00 |
| Payroll | Bulanan, tanggal 1 pukul 04:00 |
| Kinerja | Kuartalan |
| Survival | Bulanan, tanggal 1 pukul 05:00 |

Hasil prediksi disimpan di tabel `hr_intelligence_predictions` dan dapat
diakses lewat halaman admin `/admin/hr-intelligence` (dengan rate limit
`throttle:admin-api`, 30/menit per pengguna), atau per karyawan di halaman
detail karyawan. Prediksi lama dibersihkan sesuai
`HR_INTELLIGENCE_RETENTION_DAYS`.

## Test

```bash
php artisan test
```

## Rencana Modul Proses Bisnis HR

HRIS inti (Employee, Employee History, Attendance, Leave, Payroll, Reporting,
Audit Trail, Manajemen Pengguna) sudah berjalan. Modul berikutnya adalah
**proses bisnis HR** yang melengkapi siklus karyawan end-to-end — semuanya
bersifat aditif di atas fondasi yang ada (route + policy + migrasi + test),
mengikuti konvensi modul saat ini.

| # | Modul | Isi utama | Menyambung ke ML | Status |
|---|---|---|---|---|
| 1 | **Recruitment / ATS** | Lowongan, kandidat, jadwal interview, offer | Resume screening & person-job fit (`USE_CASES.md` #9) | ⬜ rencana |
| 2 | **Onboarding / Offboarding** | Checklist onboarding, aset, exit interview | Turnover & survival (#1, #2) | 🔶 sebagian (`terminate` sudah ada) |
| 3 | **Performance Management** | Goal/OKR, siklus review, appraisal | Prediksi kinerja & HiPo (#4, #5) | ⬜ rencana |
| 4 | **Training & Development** | Katalog pelatihan, enrollment, sertifikasi | Job/training recommendation (#10) | ⬜ rencana |
| 5 | **Compensation & Benefits** | Tunjangan, BPJS, PPh21, THR | Payroll anomaly & forecasting (#6, #7) | ⬜ rencana |
| 6 | **Time & Attendance lanjutan** | Shift, persetujuan lembur, akrual cuti otomatis | Absensi (#3) | 🔶 sebagian (impor & rekap sudah ada) |
| 7 | **Employee Self-Service** | Portal peran `employee`: ajukan cuti, lihat slip | — | ⬜ rencana |
| 8 | **Document Management** | Kontrak, e-sign, retensi dokumen | — | ⬜ rencana |
| 9 | **Compliance & Statutory Reporting** | Laporan BPJS/PPh21 & regulasi ketenagakerjaan | — | ⬜ rencana |

Urutan fase, kriteria selesai, dan utang teknis (backup & retensi, drift alert,
retraining terjadwal, validasi pada data nyata) ada di
[`../ROADMAP.md`](../ROADMAP.md) (Fase 5–6).

## Lisensi

Aplikasi ini bagian dari **HR Intelligence System**. Lihat
[`../README.md`](../README.md) dan [`../LICENSE`](../LICENSE) untuk keterangan
lisensi proyek.

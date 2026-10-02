<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Cara Menjalankan

```bash
cd hr_application
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed      # membuat akun demo + 3 karyawan contoh
php artisan serve --port=8080   # 8000 dipakai hr_intelligence_api
```

Akun demo dari seeder: `test@example.com` / `password` (peran `admin`).

> Port default Laravel adalah 8000, sama dengan `hr_intelligence_api`. Jalankan
> aplikasi ini di port lain (mis. `8080`) agar keduanya bisa hidup bersamaan.

## Modul HRIS

### Employee Management

CRUD karyawan lengkap dengan halaman web (Blade):

| Route | Fungsi |
|---|---|
| `GET /employees` | Daftar + pencarian (nama/NIK/email) + filter departemen & status + paginasi |
| `GET /employees/create` | Form tambah karyawan |
| `POST /employees` | Simpan karyawan baru |
| `GET /employees/{id}` | Detail karyawan + riwayat prediksi ML |
| `GET /employees/{id}/edit` | Form ubah |
| `PUT /employees/{id}` | Simpan perubahan |
| `DELETE /employees/{id}` | Hapus (khusus `admin`) |

Hak akses diatur `App\Policies\EmployeePolicy`:

* `admin` dan `hr-manager` — lihat, tambah, ubah
* hanya `admin` — hapus (destruktif, memutus riwayat)

Validasi memakai `StoreEmployeeRequest` / `UpdateEmployeeRequest`: NIK & email
unik, rentang nilai atribut ML dijaga (mis. kepuasan kerja 1–5, periode `YYYY-MM`).

Catatan penting: **kolom ML yang dikosongkan dibiarkan `null`**, bukan diisi 0.
Nilai default ditentukan mapper saat prediksi dijalankan, sehingga angka nol
tidak salah ditafsirkan sebagai data nyata.

### Autentikasi

Halaman login/logout sederhana (`Auth\LoginController`). Tamu yang membuka
route terlindungi diarahkan ke `/login`.

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

Status: `hadir`, `terlambat`, `izin`, `sakit`, `cuti`, `alpha`.

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
| `DELETE /payroll-periods/{id}` | Hapus periode (khusus `admin`, bukan periode final) |

Rumus perhitungan (`App\Services\PayrollCalculationService`):

```
lembur_jam  = total menit lembur absensi periode ÷ 60 (dibulatkan ke bawah)
biaya_lembur = lembur_jam × tarif_lembur
potongan    = (hari alpha × gaji_pokok ÷ 22) + potongan tetap karyawan
total_gaji  = gaji_pokok + tunjangan + biaya_lembur − potongan
```

Rumus `total_gaji` sengaja identik dengan `payroll_intelligence` dan fitur model
anomali payroll di HR Intelligence API, sehingga angka HRIS dan model memakai
definisi yang sama. Cuti disetujui tidak dipotong; hanya `alpha` yang dipotong.

Setelah dihitung, baris payroll dapat diberi skor anomali oleh **Payroll
Intelligence** (opsional). Isi `PAYROLL_INTELLIGENCE_BASE_URL` (mis.
`http://localhost:8300`) untuk mengaktifkan. Bila kosong atau layanan mati,
perhitungan tetap berjalan tanpa skor — payroll tidak boleh gagal karena
layanan analitik.

### Fitur ML dihitung dari data, bukan diisi manual

`riwayat_absen_90hari` dan `hari_absen_setahun` sebelumnya adalah kolom yang
diisi manual di form karyawan. Sekarang keduanya dihitung dari tabel
`attendances` oleh `App\Services\AttendanceMetricsService`, sehingga tidak bisa
basi atau salah isi. Form karyawan hanya menampilkan nilainya sebagai keterangan.

Definisinya: jumlah hari **izin + sakit + alpha** dalam 90 hari (atau 365 hari)
terakhir. **Cuti tidak dihitung** karena sudah direncanakan dan disetujui —
memasukkannya akan membuat karyawan yang berlibur tampak seperti sering absen.

### Integrasi HR Intelligence

Aplikasi ini adalah konsumen dari **HR Intelligence API** (FastAPI). Selain
turnover, tersedia prediksi absensi, kinerja, anomali payroll, dan survival
attrition.

Konfigurasi (`.env`):

```env
HR_INTELLIGENCE_BASE_URL=http://localhost:8000
HR_INTELLIGENCE_API_KEY=
```

Endpoint tiap use case diatur di `config/hr_intelligence.php` (`use_cases`).

```php
use App\Facades\HrIntelligence;

HrIntelligence::predictUseCase('absensi', $employeeData);
HrIntelligence::predictUseCaseBatch('payroll', $employeeList);
HrIntelligence::useCaseInfo('survival');
```

Prediksi batch berjalan lewat `RunUseCaseBatchPredictionJob`, dijadwalkan di
`routes/console.php`, dan bisa dijalankan manual:

```bash
php artisan hr-intelligence:predict absensi
php artisan hr-intelligence:predict payroll --sync
```

Hasil prediksi disimpan di tabel `hr_intelligence_predictions` dan dapat diakses
lewat route admin `admin/hr-intelligence/{useCase}/predictions`, atau per
karyawan di halaman detail karyawan.

## Test

```bash
php artisan test
```

## Modul Berikutnya

Urutan pengembangan modul HRIS selanjutnya ada di
[`../ROADMAP.md`](../ROADMAP.md) (Attendance, Leave, Payroll, Reporting).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

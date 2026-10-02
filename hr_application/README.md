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

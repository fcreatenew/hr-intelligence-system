# HR Application

Aplikasi Human Resources berbasis **Laravel 13** untuk mengelola proses bisnis dan operasional HR.

Aplikasi ini menangani pengelolaan data karyawan, absensi, payroll, dashboard HR, reporting, serta integrasi dengan layanan analitik dan Machine Learning.

---

## 1. Technology Stack

| Component        | Technology          |
| ---------------- | ------------------- |
| Framework        | Laravel 13          |
| PHP              | 8.4                 |
| Database         | PostgreSQL          |
| Frontend         | Blade / JavaScript  |
| API Client       | Laravel HTTP Client |
| Cache            | Redis               |
| Queue            | Redis               |
| Web Server       | Nginx               |
| Containerization | Docker              |

---

## 2. Requirements

Pastikan environment sudah memiliki:

```text
PHP >= 8.3
Composer
Node.js
NPM
PostgreSQL
Redis
```

Direkomendasikan:

```text
PHP 8.4
Laravel 13
PostgreSQL
Redis
```

---

## 3. Installation

Install dependency Laravel:

```bash
composer install
```

Install frontend dependency:

```bash
npm install
```

Copy environment:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

---

## 4. Environment Configuration

Contoh konfigurasi:

```env
APP_NAME="HR Application"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hr_database
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### HR Intelligence API

Jika menggunakan layanan Machine Learning:

```env
HR_INTELLIGENCE_API_URL=http://127.0.0.1:8000
HR_INTELLIGENCE_API_KEY=
```

API key harus disimpan di `.env` dan tidak boleh ditulis langsung di source code.

---

## 5. Database

Jalankan migration:

```bash
php artisan migrate
```

Jika membutuhkan seed data:

```bash
php artisan db:seed
```

Atau:

```bash
php artisan migrate --seed
```

---

## 6. Running Application

Jalankan Laravel:

```bash
php artisan serve
```

Aplikasi dapat diakses melalui:

```text
http://127.0.0.1:8000
```

Untuk development frontend:

```bash
npm run dev
```

Untuk production build:

```bash
npm run build
```

---

## 7. Application Modules

Modul utama aplikasi:

### Employee Management

Mengelola:

* data karyawan
* identitas karyawan
* status kerja
* departemen
* jabatan
* riwayat karyawan

### Attendance

Mengelola:

* absensi
* jadwal kerja
* kehadiran
* keterlambatan
* lembur
* rekap absensi

### Payroll

Mengelola:

* payroll
* gaji
* tunjangan
* potongan
* lembur
* konfigurasi payroll
* generate payroll
* payroll report

### HR Analytics

Mengelola:

* employee analytics
* turnover analysis
* employee risk
* department analysis
* HR metrics

### Reporting

Menghasilkan:

* HR report
* employee report
* attendance report
* payroll report
* analytics report

---

## 8. Machine Learning Integration

Aplikasi dapat menggunakan HR Intelligence API untuk melakukan prediksi Machine Learning.

Contoh request:

```text
Laravel
   ↓
POST /predict
   ↓
HR Intelligence API
   ↓
Prediction
   ↓
Laravel
```

Contoh data:

```json
{
    "employee_id": 1001,
    "usia": 30,
    "masa_kerja_bulan": 48,
    "jam_lembur_bulanan": 25,
    "gaji_bulanan": 7500000,
    "tanggal_masuk": "2021-01-10",
    "tanggal_promosi": "2024-01-10",
    "departemen": "IT",
    "status_kerja": "tetap",
    "pendidikan": "S1"
}
```

Contoh hasil:

```json
{
    "employee_id": 1001,
    "probabilitas_keluar": 0.68,
    "prediksi_keluar": 1,
    "level_risiko": "Tinggi"
}
```

---

## 9. API Client

Gunakan Laravel HTTP Client untuk komunikasi dengan API.

Contoh konfigurasi pada `config/services.php`:

```php
'hr_intelligence' => [
    'url' => env('HR_INTELLIGENCE_API_URL'),
    'api_key' => env('HR_INTELLIGENCE_API_KEY'),
],
```

Contoh request:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-API-Key' => config('services.hr_intelligence.api_key'),
])
    ->timeout(10)
    ->post(
        config('services.hr_intelligence.url') . '/predict',
        $employeeData
    );

$result = $response->json();
```

Untuk menjaga maintainability, komunikasi dengan API sebaiknya ditempatkan pada service khusus.

Contoh:

```text
app/
└── Services/
    └── HrIntelligence/
        ├── HrIntelligenceClient.php
        └── TurnoverPredictionService.php
```

---

## 10. Turnover Prediction

Turnover prediction digunakan untuk memperkirakan kemungkinan seorang karyawan keluar.

Contoh hasil:

```text
Probability : 0.68
Prediction  : 1
Risk Level  : Tinggi
```

Risk level:

```text
< 0.25
Rendah

0.25 - < 0.50
Sedang

>= 0.50
Tinggi
```

Laravel menggunakan hasil dari API dan tidak menjalankan file model Machine Learning secara langsung.

---

## 11. Batch Prediction

Untuk melakukan prediction terhadap banyak karyawan, aplikasi dapat menggunakan endpoint batch.

```text
POST /predict/batch
```

Contoh penggunaan:

```text
Employee 1
Employee 2
Employee 3
...
Employee N
```

Batch prediction cocok untuk:

* periodic analysis
* HR dashboard
* turnover monitoring
* scheduled prediction
* refresh employee risk

Untuk proses yang membutuhkan waktu lama, gunakan Laravel Queue.

---

## 12. Queue

Proses asynchronous dapat menggunakan Laravel Queue.

Contoh:

```text
Request
   ↓
Laravel
   ↓
Dispatch Job
   ↓
Redis Queue
   ↓
Process Job
```

Contoh job:

```text
GenerateTurnoverPrediction
```

Queue dapat digunakan untuk:

* batch prediction
* generate payroll
* report generation
* data processing
* scheduled analysis

---

## 13. Authentication & Authorization

Authentication digunakan untuk mengatur akses pengguna.

Contoh role:

```text
Admin
HR
Manager
Employee
```

Authorization digunakan untuk menentukan fitur yang dapat diakses masing-masing role.

Contoh:

```text
Admin
- User Management
- Employee Management
- Configuration

HR
- Employee
- Attendance
- Payroll
- Analytics
- Reports

Manager
- Team
- Analytics
- Reports

Employee
- Profile
- Personal Information
```

---

## 14. Security

### Environment Variables

Secret dan credential harus disimpan pada `.env`.

Jangan:

```php
$apiKey = 'secret-key';
```

Gunakan:

```php
env('HR_INTELLIGENCE_API_KEY')
```

atau konfigurasi melalui:

```php
config('services.hr_intelligence.api_key')
```

### HTTPS

Production harus menggunakan HTTPS.

### Validation

Semua input dari user harus divalidasi sebelum diproses.

### API Authentication

Request ke HR Intelligence API harus menggunakan API key yang disimpan secara aman.

---

## 15. Error Handling

Aplikasi harus menangani kondisi seperti:

```text
Connection Timeout
API Unavailable
HTTP 401
HTTP 404
HTTP 429
HTTP 500
Invalid Response
```

User sebaiknya mendapatkan pesan yang mudah dipahami.

Contoh:

```text
Layanan analitik sedang tidak tersedia.
Silakan coba kembali beberapa saat lagi.
```

Detail teknis disimpan pada application log.

---

## 16. Logging

Logging digunakan untuk debugging dan monitoring.

Contoh:

```text
Prediction request started
Prediction request completed
Prediction API timeout
Prediction API unavailable
```

Jangan mencatat:

```text
API key
Password
Access token
Secret
```

ke dalam log.

---

## 17. Testing

Jalankan seluruh test:

```bash
php artisan test
```

Test dapat dibagi menjadi:

```text
tests/
├── Feature/
└── Unit/
```

Contoh:

```text
Feature
├── AuthenticationTest
├── EmployeeTest
└── TurnoverPredictionTest

Unit
└── Services
    └── TurnoverPredictionServiceTest
```

Untuk testing integrasi API, Laravel HTTP Client dapat menggunakan `Http::fake()`.

---

## 18. Development Workflow

Workflow pengembangan:

```text
Develop
   ↓
Migration
   ↓
Business Logic
   ↓
Validation
   ↓
Service
   ↓
Controller
   ↓
UI
   ↓
Testing
```

Untuk fitur Machine Learning:

```text
Employee Data
   ↓
Laravel Service
   ↓
HR Intelligence API
   ↓
Prediction
   ↓
Save Result
   ↓
Dashboard
```

---

## 19. Code Organization

Gunakan prinsip **Separation of Concerns**.

Controller:

```text
Request
   ↓
Controller
   ↓
Service
```

Business logic sebaiknya tidak menumpuk di Controller.

Contoh:

```text
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
│
├── Models/
│
└── Services/
```

Controller bertanggung jawab terhadap HTTP request/response.

Service bertanggung jawab terhadap business logic.

Model bertanggung jawab terhadap representasi dan akses data.

---

## 20. Performance

Untuk meningkatkan performa:

* gunakan database indexing
* gunakan eager loading
* gunakan pagination
* gunakan caching
* gunakan Redis
* gunakan queue untuk proses berat
* hindari query berulang
* gunakan batch processing untuk data besar

Untuk proses asynchronous:

```text
Laravel Queue
    ↓
Redis
    ↓
Worker
```

Worker dapat dijalankan dengan:

```bash
php artisan queue:work
```

---

## 21. Production

Sebelum production:

```bash
composer install --no-dev --optimize-autoloader
```

Cache configuration:

```bash
php artisan config:cache
```

Cache routes:

```bash
php artisan route:cache
```

Cache views:

```bash
php artisan view:cache
```

Build frontend:

```bash
npm run build
```

Pastikan:

```env
APP_ENV=production
APP_DEBUG=false
```

---

## 22. Docker

Aplikasi dapat dijalankan menggunakan Docker.

Contoh service:

```text
Laravel
PostgreSQL
Redis
Nginx
```

Development:

```text
Browser
   ↓
Nginx
   ↓
Laravel
   ├── PostgreSQL
   └── Redis
```

---

## 23. Maintenance

Beberapa hal yang perlu diperhatikan:

* migration harus terdokumentasi
* perubahan database harus melalui migration
* business logic tidak diletakkan seluruhnya di controller
* API credential tidak disimpan di repository
* test harus diperbarui ketika fitur berubah
* dependency harus diperbarui secara berkala
* log production harus dimonitor

---

## 24. Development Status

```text
Application
├── Authentication       [Planned]
├── User Management      [Planned]
├── Employee Management  [Planned]
├── Attendance           [Planned]
├── Payroll              [Planned]
├── HR Analytics         [Planned]
├── Turnover Prediction  [Planned]
└── Reporting            [Planned]
```

---

## License

Private / Proprietary.

Hak penggunaan, distribusi, modifikasi, dan akses terhadap source code mengikuti kebijakan pemilik proyek.

© 2026 All Rights Reserved.
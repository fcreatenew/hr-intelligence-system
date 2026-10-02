# HR Dashboard

Dashboard untuk **visualisasi, monitoring, dan analisis data Human Resources (HR)** dalam HR Intelligence System.

Project ini bertanggung jawab menyajikan data HR dalam bentuk dashboard yang mudah dipahami sehingga dapat digunakan untuk melihat kondisi workforce, turnover, attendance, payroll, dan berbagai HR KPI.

---

# Tujuan

HR Dashboard menjadi layer **visualisasi dan analytics** dalam HR Intelligence System.

Arsitektur sederhananya:

```text
HR Application
       │
       │
       ▼
 HR Intelligence API
       │
       ▼
   HR Dashboard
       │
       ├── Workforce Analytics
       ├── Turnover Analytics
       ├── Attendance Analytics
       ├── Payroll Analytics
       └── HR KPI
```

Dashboard tidak bertanggung jawab terhadap proses bisnis utama maupun training model Machine Learning.

---

# Fitur

## Workforce Analytics

Menampilkan informasi workforce seperti:

* Total employee
* Employee berdasarkan department
* Employee berdasarkan status kerja
* Distribusi usia
* Masa kerja
* Employee growth
* Workforce composition

---

## Turnover Analytics

Menampilkan analisis employee turnover.

Contoh:

* Turnover rate
* Turnover berdasarkan department
* Turnover berdasarkan status kerja
* Turnover berdasarkan masa kerja
* Turnover berdasarkan usia
* Employee turnover prediction
* Employee risk distribution

---

## Attendance Analytics

Analisis data kehadiran karyawan.

Contoh:

* Attendance rate
* Absence
* Late attendance
* Overtime
* Attendance berdasarkan department
* Tren attendance

---

## Payroll Analytics

Menampilkan informasi terkait payroll.

Contoh:

* Total payroll
* Payroll berdasarkan department
* Salary distribution
* Overtime cost
* Allowance
* Deduction
* Payroll trend

---

## HR KPI

Dashboard KPI untuk membantu monitoring HR.

Contoh:

```text
Employee Count
Turnover Rate
Attendance Rate
Average Salary
Overtime Hours
Overtime Cost
Employee Risk
```

---

# Struktur Project

```text
hr_dashboard/
│
├── README.md
├── index.html
├── package.json
├── vite.config.js
│
└── src/
    ├── components/     # PageHead, KpiCard, Section, DataTable, BarList, Badge, charts
    ├── layouts/        # AppLayout (masthead + navigasi)
    ├── pages/          # satu file per halaman
    ├── services/       # api.js (klien API), fixtures.js (data contoh)
    ├── hooks/          # useAsyncData (loading/error/refetch)
    ├── styles/         # global.css (token desain)
    ├── utils/          # format.js (angka, rupiah, persen, warna grafik)
    └── main.jsx
```

Struktur di dalam `src/` dapat berkembang mengikuti kebutuhan aplikasi dashboard.

---

# Halaman

| Route | Isi |
| --- | --- |
| `/` | Ringkasan operasional: KPI utama, komposisi workforce, sinyal anomali |
| `/workforce` | Komposisi, pertumbuhan, sebaran usia, status kerja |
| `/turnover` | Turnover rate, sebaran risiko, analisis masa kerja, metrik model |
| `/attendance` | Kehadiran, absen, keterlambatan, lembur, tren bulanan |
| `/payroll` | Biaya payroll, per departemen, anomali, proyeksi |
| `/models` | HR KPI dan status serta metrik lima model ML |

---

# Integrasi

HR Dashboard mengambil data dari service lain melalui API.

Contoh arsitektur:

```text
┌──────────────────────┐
│   HR Application     │
│      Laravel         │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ HR Intelligence API  │
│       FastAPI        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│    HR Dashboard      │
│    Visualization     │
└──────────────────────┘
```

Dashboard tidak melakukan training model Machine Learning.

Model prediction disediakan oleh:

```text
hr_intelligence_api/
```

---

# Teknologi

Teknologi frontend dapat menggunakan:

* JavaScript / TypeScript
* React
* Vite
* Charting Library
* REST API

Teknologi dapat berkembang mengikuti kebutuhan project.

---

# Prinsip Pengembangan

Dashboard dikembangkan dengan prinsip:

* Component-based architecture
* Reusable components
* API-driven data
* Responsive interface
* Separation of concerns
* Maintainable frontend architecture
* Data visualization yang mudah dipahami

---

# Pengembangan

Masuk ke directory project:

```bash
cd hr_dashboard
```

Install dependency:

```bash
npm install
```

Jalankan development server:

```bash
npm run dev
```

Secara default dashboard berjalan dalam **mode demo**: memakai data contoh di
`src/services/fixtures.js`, sehingga halaman tidak pernah kosong walau backend
belum dinyalakan. Untuk memakai API sungguhan:

```bash
cp .env.example .env
# ubah VITE_DATA_MODE=live
npm run dev
```

Proxy dev (lihat `vite.config.js`):

```text
/api/*          → http://127.0.0.1:8000   (HR Intelligence API)
/payroll-api/*  → http://127.0.0.1:8300   (Payroll Intelligence API)
```

Prefix `/payroll-api` dipakai agar tidak bentrok dengan route SPA `/payroll`.

Build produksi:

```bash
npm run build
npm run preview
```

Lint dan test:

```bash
npm run lint   # ESLint 9 (flat config, eslint.config.js)
npm test       # Vitest + Testing Library (jsdom)
```

---

# Project Scope

Project ini berfokus pada:

```text
Data
 ↓
API
 ↓
Analytics
 ↓
Visualization
 ↓
Dashboard
```

Sedangkan proses:

```text
Data Processing
Machine Learning
Payroll Calculation
Business Process
```

ditangani oleh project masing-masing.

---

## License

Private / Proprietary.

Hak penggunaan, distribusi, modifikasi, dan akses terhadap source code mengikuti kebijakan pemilik proyek.

© 2026 All Rights Reserved.
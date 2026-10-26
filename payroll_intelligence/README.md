# Payroll Intelligence

Project untuk membangun **analitik, intelligence, dan Machine Learning terkait payroll** dalam HR Intelligence System.

Project ini berfokus pada bagaimana data payroll dapat digunakan bukan hanya untuk menghitung gaji, tetapi juga untuk menghasilkan insight, mendeteksi anomali, melakukan analisis biaya, dan membuat forecasting.

---

# Tujuan

Payroll Intelligence menjadi layer khusus untuk **Payroll Analytics dan Intelligence**.

```text
Payroll Data
     │
     ▼
Data Processing
     │
     ▼
Payroll Analytics
     │
     ├── Salary Analysis
     ├── Overtime Analysis
     ├── Payroll Analytics
     ├── Anomaly Detection
     └── Forecasting
```

---

# Fitur

## Payroll Analytics

Analisis data payroll berdasarkan:

* Department
* Employee
* Periode
* Salary component
* Overtime
* Allowance
* Deduction

Contoh analisis:

```text
Total Payroll
Average Salary
Payroll per Department
Payroll Growth
Payroll Distribution
```

---

## Salary Analysis

Analisis struktur kompensasi karyawan.

Contoh:

* Salary distribution
* Average salary
* Median salary
* Salary berdasarkan department
* Salary berdasarkan employee type
* Salary comparison

---

## Overtime Analysis

Analisis biaya dan penggunaan overtime.

Contoh:

* Total overtime hours
* Overtime cost
* Overtime berdasarkan department
* Employee dengan overtime tinggi
* Overtime trend

---

## Payroll Anomaly Detection

Mendeteksi pola payroll yang tidak biasa.

Contoh:

* Salary anomaly
* Overtime anomaly
* Sudden payroll increase
* Unusual deduction
* Unusual payroll pattern

Pendekatan dapat menggunakan:

* Statistical analysis
* Rule-based detection
* Machine Learning
* Anomaly detection algorithms

---

## Payroll Forecasting

Melakukan forecasting terhadap kebutuhan payroll.

Contoh:

```text
Historical Payroll
       │
       ▼
Time Series Analysis
       │
       ▼
Forecast
       │
       ▼
Future Payroll
```

Forecast dapat digunakan untuk membantu perencanaan biaya payroll.

---

# Struktur Project

```text
payroll_intelligence/
│
├── README.md
├── requirements.txt
├── .env.example
│
├── src/
│   ├── config.py                 # path data, ambang anomali, horizon forecast
│   ├── ingestion/loader.py       # muat & validasi data payroll
│   ├── processing/transform.py   # pembersihan + kolom turunan
│   ├── calculations/payroll.py   # rumus komponen gaji
│   ├── analytics/
│   │   ├── payroll_analytics.py  # ringkasan, per departemen/periode
│   │   └── anomaly.py            # deteksi anomali + alasan
│   ├── forecasting/
│   │   ├── forecast.py           # model Holt / seasonal
│   │   └── service.py            # orkestrasi proyeksi
│   ├── reports/report.py         # laporan ringkas
│   ├── api.py                    # FastAPI (port 8300)
│   └── cli.py                    # CLI analitik
│
└── tests/test_payroll_intelligence.py
```

Struktur dapat berkembang sesuai kebutuhan implementasi.

---

# API

```bash
cd payroll_intelligence && uvicorn src.api:app --port 8300
```

Endpoint: `/health`, `/summary`, `/departments`, `/periods`, `/overtime`,
`/components`, `/anomalies`, `/forecast/payroll`, `/forecast/overtime`.

---

# Notebook

Notebook digunakan untuk eksplorasi dan eksperimen.

Contoh:

```text
notebooks/
├── 01_exploration.ipynb
├── 02_payroll_analysis.ipynb
└── 03_payroll_forecasting.ipynb
```

Notebook digunakan untuk:

* Exploratory Data Analysis
* Experiment
* Visualization
* Model development
* Evaluation

Logic yang sudah stabil dapat dipindahkan ke `src/`.

---

# Integrasi

Payroll Intelligence dapat mengambil data dari HR Application.

```text
┌──────────────────────┐
│   HR Application     │
│       Laravel        │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Payroll Intelligence │
│       Python         │
└──────────┬───────────┘
           │
           ├── Analytics
           ├── Anomaly Detection
           └── Forecasting
```

---

# Teknologi

* Python
* Pandas
* NumPy
* Scikit-learn
* Statistical Analysis
* Time Series
* Jupyter Notebook

Library tambahan dapat digunakan sesuai kebutuhan project.

---

# Prinsip Pengembangan

* Reproducible analysis
* Modular Python code
* Separation between notebook dan production code
* Testable calculation logic
* Clear data pipeline
* Explainable analytics
* Versioned experiments

---

# Pengembangan

Masuk ke directory:

```bash
cd payroll_intelligence
```

Buat virtual environment:

```bash
python -m venv .venv
```

Aktifkan environment:

```bash
source .venv/bin/activate
```

Install dependency:

```bash
pip install -r requirements.txt
```

Jalankan Jupyter:

```bash
jupyter notebook
```

---

## Lisensi

Kode sumber `payroll_intelligence/` ditujukan untuk GNU AGPL-3.0-only pada rancangan migrasi ini. Lihat [LICENSE di root repository](../LICENSE). Dokumentasi buatan proyek mengikuti CC BY 4.0 sesuai [DOCUMENTATION_LICENSE.md](../DOCUMENTATION_LICENSE.md). Dependency, model, dataset, dan materi pihak ketiga tetap mengikuti lisensi masing-masing.

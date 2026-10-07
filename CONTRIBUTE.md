# Contributing to HR Intelligence System

Terima kasih atas ketertarikan untuk berkontribusi pada **HR Intelligence System**.

Project ini dikembangkan sebagai project open-source untuk mengeksplorasi dan membangun sistem yang menggabungkan:

* Software Engineering
* Data Science
* Machine Learning
* Artificial Intelligence
* HR Analytics
* API Development
* MLOps

Kontribusi dapat berupa code, bug fix, documentation, testing, architecture improvement, dataset improvement, maupun ide pengembangan.

---

# 1. Cara Berkontribusi

Ada beberapa bentuk kontribusi yang dapat dilakukan:

### Code

* Laravel
* Python
* FastAPI
* Machine Learning
* AI
* Data Processing
* Frontend
* Dashboard
* MLOps

### Documentation

* memperbaiki README
* membuat tutorial
* memperjelas dokumentasi API
* memperbaiki contoh penggunaan
* menambahkan dokumentasi arsitektur

### Testing

* unit test
* feature test
* API test
* ML evaluation
* integration test
* bug reproduction

### Ideas

* mengusulkan fitur baru
* improvement architecture
* model improvement
* analytics improvement
* AI use case

---

# 2. Sebelum Memulai

Pastikan sudah memahami teknologi yang digunakan pada bagian yang ingin dikembangkan.

Contoh:

```text
Laravel
→ PHP + Laravel

ML API
→ Python + FastAPI

Machine Learning
→ Python + pandas + scikit-learn

Database
→ PostgreSQL

Infrastructure
→ Docker / Redis
```

Tidak perlu menguasai seluruh project untuk berkontribusi.

Contributor dapat fokus pada area tertentu.

---

# 3. Development Environment

Project menggunakan beberapa environment.

### Laravel

```text
PHP 8.4
Laravel 13
Composer
Node.js
NPM
PostgreSQL
Redis
```

### Machine Learning API

```text
Python
FastAPI
scikit-learn
pandas
NumPy
```

### Infrastructure

```text
Docker
PostgreSQL
Redis
```

Untuk modul Python, versi kanonik ada di `requirements/base.txt` (sumber tunggal).
`<modul>/requirements.txt` memakai `-r ../requirements/base.txt` dan hanya
menambah dependensi khusus modul. Runtime yang dipakai adalah **Python 3.14**
(dipin di `.python-version`, ditegakkan di CI dan Dockerfile) — buat venv dengan
versi itu sebelum memasang dependensi. Untuk Laravel, gunakan `composer.json` /
`composer.lock`.

---

# 4. Fork Repository

Jika menggunakan GitHub:

```bash
git clone <repository-url>
cd hr-intelligence-system
```

Jika menggunakan fork:

```bash
git remote add upstream <original-repository-url>
```

Periksa remote:

```bash
git remote -v
```

---

# 5. Branch Strategy

Jangan melakukan development langsung pada branch utama.

Gunakan branch sesuai jenis pekerjaan.

### Feature

```text
feature/<name>
```

Contoh:

```text
feature/turnover-dashboard
feature/employee-risk
feature/batch-prediction
```

### Bug Fix

```text
fix/<name>
```

Contoh:

```text
fix/prediction-timeout
fix/employee-validation
```

### Documentation

```text
docs/<name>
```

Contoh:

```text
docs/api-usage
docs/ml-training
```

### Refactoring

```text
refactor/<name>
```

Contoh:

```text
refactor/prediction-service
```

---

# 6. Membuat Branch

Sebelum membuat branch baru, pastikan branch utama sudah diperbarui:

```bash
git checkout main
git pull upstream main
```

Kemudian:

```bash
git checkout -b feature/nama-feature
```

Contoh:

```bash
git checkout -b feature/turnover-dashboard
```

---

# 7. Development

Kerjakan perubahan sesuai scope branch.

Contoh:

```text
feature/turnover-dashboard
```

sebaiknya fokus pada:

```text
Turnover Dashboard
```

Hindari mencampurkan perubahan yang tidak berhubungan seperti:

```text
Turnover Dashboard
+
Payroll refactor
+
Database migration unrelated
+
README redesign
```

dalam satu pull request.

Perubahan yang terfokus lebih mudah direview.

---

# 8. Code Quality

Kode yang ditambahkan harus mengikuti style dan pola yang sudah digunakan pada project.

### Laravel

Perhatikan:

* naming convention
* validation
* service layer
* database query
* authorization
* error handling

Hindari menempatkan business logic yang kompleks langsung di Controller.

Gunakan Service ketika logic mulai kompleks.

Contoh:

```text
Controller
    ↓
Service
    ↓
Repository / Model
    ↓
Database
```

### Python

Perhatikan:

* type hints
* function naming
* modularity
* error handling
* reproducibility

Contoh:

```python
def predict_turnover(employee_data: dict) -> dict:
    ...
```

---

# 9. Machine Learning Contribution

Contribution terhadap Machine Learning harus memperhatikan reproducibility.

Jika mengubah:

* feature engineering
* preprocessing
* algorithm
* hyperparameter
* threshold
* evaluation method

jelaskan perubahan tersebut.

Contoh:

```text
Before
ROC-AUC: 0.69

After
ROC-AUC: 0.72
```

Namun jangan hanya melihat satu metric.

Pertimbangkan:

* ROC-AUC
* PR-AUC
* Precision
* Recall
* F1
* confusion matrix
* threshold
* class distribution

Jika threshold berubah, jelaskan alasannya dan dampaknya terhadap precision/recall.

---

# 10. Model Versioning

Jangan mengganti model production secara diam-diam.

Model harus memiliki version.

Contoh:

```text
turnover/v1
turnover/v2
turnover/v3
```

Jika membuat model baru:

```text
models/
└── turnover/
    ├── v1/
    └── v2/
```

Jangan menghapus model sebelumnya hanya karena model baru dibuat.

Model metadata juga harus diperbarui.

---

# 11. Dataset dan Privacy

Project ini berkaitan dengan Human Resources sehingga **data privacy adalah hal yang sangat penting**.

### Jangan Commit Data Pribadi

Dilarang memasukkan data seperti:

```text
NIK
Nama lengkap
Alamat
Nomor telepon
Nomor rekening
Email pribadi
Data payroll asli
Data absensi individu yang sensitif
Credential
API key
Password
Access token
```

ke repository.

### Dataset Open Source

Gunakan:

```text
Synthetic Dataset
```

atau dataset yang memang memiliki izin untuk digunakan dan didistribusikan.

Jangan menggunakan data perusahaan atau data karyawan nyata untuk contribution tanpa izin yang sesuai.

---

# 12. Environment Variables

Jangan commit:

```text
.env
```

Gunakan:

```text
.env.example
```

Contoh:

```env
APP_NAME=
APP_KEY=

DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

HR_INTELLIGENCE_BASE_URL=
HR_INTELLIGENCE_API_KEY=
```

Credential asli harus tetap berada di environment masing-masing contributor.

---

# 13. Testing

Setiap perubahan sebaiknya memiliki test yang sesuai.

### Laravel

```bash
php artisan test
```

### Python

Jika project menggunakan pytest:

```bash
pytest
```

Pastikan test yang sudah ada tetap berjalan.

---

# 14. API Testing

Jika mengubah endpoint API, test minimal harus mencakup:

```text
Valid Request
Invalid Request
Authentication
Unauthorized Request
Error Response
Expected Response
```

Untuk prediction:

```text
Valid Employee
Missing Feature
Invalid Data Type
Invalid API Key
Batch Request
Batch Size Limit
```

---

# 15. Commit Convention

Gunakan commit message yang jelas.

Format yang direkomendasikan:

```text
<type>: <description>
```

Contoh:

```text
feat: add turnover risk dashboard
fix: handle prediction API timeout
docs: update FastAPI integration guide
test: add turnover prediction tests
refactor: simplify prediction service
chore: update dependencies
```

Jenis commit:

```text
feat
fix
docs
test
refactor
chore
```

Hindari commit seperti:

```text
update
fix
changes
test
baru
coba
final
final2
fix lagi
```

---

# 16. Pull Request

Setelah development selesai:

```bash
git status
git add .
git commit -m "feat: add turnover risk dashboard"
git push origin feature/turnover-dashboard
```

Kemudian buat Pull Request ke:

```text
main
```

---

# 17. Pull Request Description

Gunakan format berikut:

```markdown
## Summary

Jelaskan perubahan yang dibuat.

## Changes

- Add turnover risk dashboard
- Add prediction summary
- Add risk distribution
- Add tests

## Testing

- php artisan test
- Manual API testing

## Related Issue

Closes #123
```

---

# 18. Pull Request Checklist

Sebelum membuat Pull Request:

```text
[ ] Branch dibuat dari main terbaru
[ ] Scope perubahan jelas
[ ] Code sudah diperiksa
[ ] Test sudah dijalankan
[ ] Dokumentasi diperbarui jika diperlukan
[ ] Tidak ada credential
[ ] Tidak ada data pribadi
[ ] Tidak ada dataset sensitif
[ ] Tidak ada file .env
[ ] Commit message jelas
[ ] Tidak ada perubahan unrelated
```

---

# 19. Code Review

Pull Request akan direview berdasarkan:

### Correctness

Apakah perubahan bekerja sesuai tujuan?

### Maintainability

Apakah kode mudah dipahami dan dikembangkan?

### Security

Apakah perubahan memperkenalkan risiko keamanan?

### Testing

Apakah perubahan sudah memiliki test yang sesuai?

### Performance

Apakah perubahan memiliki dampak terhadap performa?

### Architecture

Apakah perubahan sesuai dengan struktur dan responsibility masing-masing module?

---

# 20. Issue

Jika menemukan bug, buat Issue dengan informasi:

```text
Title

Environment

Steps to reproduce

Expected behavior

Actual behavior

Error message

Screenshot / log jika diperlukan
```

Contoh:

```text
Bug: Turnover prediction returns 500 for missing promotion date
```

---

# 21. Feature Request

Untuk mengusulkan fitur:

```text
Feature:
Turnover Risk Dashboard

Problem:
HR membutuhkan cara untuk melihat distribusi risiko turnover.

Proposed Solution:
Menambahkan dashboard yang menampilkan:

- total employees
- low risk
- medium risk
- high risk
- department distribution
```

Jelaskan masalah terlebih dahulu sebelum solusi.

---

# 22. Discussion

Tidak semua perubahan harus langsung dibuat menjadi Pull Request.

Untuk perubahan besar seperti:

* perubahan arsitektur
* penggantian ML algorithm
* perubahan database besar
* penambahan AI system
* perubahan API contract

lebih baik didiskusikan terlebih dahulu melalui Issue atau Discussion.

---

# 23. Breaking Changes

Jika perubahan dapat memengaruhi contributor atau aplikasi yang sudah ada, jelaskan sebagai:

```text
BREAKING CHANGE
```

Contoh:

```text
BREAKING CHANGE:

Endpoint /predict sekarang membutuhkan field pendidikan.
```

Sertakan migration atau upgrade guide jika diperlukan.

---

# 24. Documentation

Jika fitur baru ditambahkan, pertimbangkan untuk memperbarui:

```text
README.md
API documentation
Architecture documentation
Example
Testing documentation
```

Dokumentasi adalah bagian dari contribution, bukan pekerjaan tambahan yang tidak penting.

---

# 25. Contributor Scope

Contributor bebas memilih area sesuai kemampuan.

Contoh:

```text
Beginner
├── Documentation
├── Typo Fix
├── Test
└── Small UI Improvement

Intermediate
├── Laravel Feature
├── FastAPI Endpoint
├── Database
└── Dashboard

Advanced
├── ML Pipeline
├── Model Optimization
├── MLOps
├── Architecture
└── AI Assistant
```

Tidak harus menguasai semua teknologi.

---

# 26. Development Principles

Project mengikuti beberapa prinsip:

### Separation of Concerns

Setiap komponen memiliki responsibility yang jelas.

### Reproducibility

Eksperimen Machine Learning harus dapat direproduksi.

### Security by Default

Credential dan data sensitif tidak boleh masuk repository.

### Test Before Merge

Perubahan penting harus memiliki test.

### Documentation Matters

Fitur yang sulit dipahami harus memiliki dokumentasi.

### Small and Focused Changes

Pull Request sebaiknya memiliki scope yang jelas.

---

# 27. Code of Conduct

Semua contributor diharapkan:

* menghormati contributor lain
* memberikan feedback secara konstruktif
* tidak melakukan harassment
* tidak melakukan diskriminasi
* fokus pada masalah teknis
* menghargai perbedaan pendapat

Tujuan contribution adalah membangun project bersama sekaligus menciptakan lingkungan belajar yang baik.

---

# 28. Security Issues

Jangan membuat public issue untuk vulnerability yang dapat membahayakan sistem.

Untuk masalah keamanan, gunakan security contact yang tercantum pada repository.

Jangan memasukkan:

```text
API key
Password
Token
Private data
Database credentials
```

ke dalam Issue atau Pull Request.

---

# 29. License

Contribution terhadap project ini mengikuti license yang digunakan oleh repository.

Pastikan memahami license sebelum melakukan contribution.

---

# 30. Thank You

Setiap contribution sangat dihargai.

Tidak harus berupa fitur besar.

Contribution seperti:

```text
documentation
bug fix
test
code review
issue
architecture discussion
feature idea
```

juga membantu project berkembang.

**Happy coding and happy learning! 🚀**

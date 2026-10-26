# Deep Learning

Project untuk eksperimen dan pengembangan **Deep Learning** dalam HR Intelligence System.

Project ini menjadi tempat untuk mempelajari dan mengimplementasikan neural network serta berbagai pendekatan Deep Learning yang dapat digunakan untuk permasalahan HR.

---

# Tujuan

Membangun pemahaman dan eksperimen Deep Learning dari dasar hingga implementasi.

```text
Data
 ↓
Preprocessing
 ↓
Neural Network
 ↓
Training
 ↓
Evaluation
 ↓
Experiment
 ↓
Model
```

---

# Fokus

## Neural Network

Eksperimen dasar:

* Perceptron
* Fully Connected Neural Network
* Activation Function
* Loss Function
* Optimizer
* Backpropagation
* Training Loop

---

## Employee Prediction

Deep Learning dapat digunakan untuk eksperimen prediksi seperti:

* Employee Turnover
* Employee Risk
* Workforce Classification
* Employee Segmentation

Model Deep Learning dapat dibandingkan dengan model Machine Learning klasik.

---

## Computer Vision

Eksperimen Computer Vision yang relevan dengan HR.

Contoh:

* Document Image Classification
* Employee Document Processing
* Image Classification
* Image Recognition

---

## OCR

Eksperimen Optical Character Recognition untuk membaca dokumen.

Contoh:

```text
Document
   ↓
Image Processing
   ↓
OCR
   ↓
Extracted Text
   ↓
Structured Data
```

---

## Document Understanding

Menggabungkan:

* OCR
* NLP
* Deep Learning
* Document Classification
* Information Extraction

Tujuannya adalah mengubah dokumen HR menjadi informasi terstruktur.

---

# Struktur Project

```text
deep_learning/
│
├── README.md
├── requirements.txt
├── .env.example
│
├── src/
│   ├── config.py              # konfigurasi (env) & parameter arsitektur
│   ├── data/dataset.py        # muat data + kontrak fitur turnover
│   ├── models/mlp.py          # MLP murni NumPy (ReLU/softmax + backprop)
│   ├── training/trainer.py    # loop pelatihan + early stopping
│   ├── evaluation/
│   │   ├── metrics.py         # akurasi, F1, ROC AUC (manual)
│   │   └── baseline.py        # logistic regression & majority class
│   ├── experiments/tracker.py # pencatatan eksperimen (JSON)
│   ├── api.py                 # FastAPI penyaji hasil eksperimen
│   └── cli.py                 # CLI: train / baseline / compare / experiments
│
└── tests/test_deep_learning.py
```

Struktur dapat berkembang mengikuti eksperimen.

---

# Implementasi

Modul ini sengaja **tidak bergantung pada PyTorch/TensorFlow** agar eksperimen
dapat dijalankan di lingkungan minimal. Jaringan dibangun dari NumPy:

- **MLP** dengan aktivasi ReLU, dropout opsional, dan output sigmoid (biner)
  atau softmax (multikelas).
- **Backpropagation** penuh dengan mini-batch SGD dan **early stopping**.
- **Kontrak fitur turnover** dipakai ulang dari `hr_intelligence_api`
  (`buat_fitur`), dengan `tanggal_acuan = 2025-01-01` — sama seperti training
  produksi. Ada implementasi cadangan yang setara bila API tidak dapat diimpor.
- **Standarisasi** memakai statistik data train saja untuk mencegah kebocoran.

## Hasil pembanding (baseline)

Deep Learning tidak otomatis lebih baik. Pada dataset turnover saat ini,
baseline klasik masih unggul pada ROC AUC:

```text
Model                    ROC AUC        F1    Recall  Accuracy
MLP (deep learning)       0.6868    0.5799    0.6712    0.6450
Logistic Regression       0.7219    0.6203    0.6712    0.7000
Majority class            0.5000    0.0000    0.0000    0.6350
```

Angka ini adalah alasan modul ini ada: menyediakan titik acuan agar klaim
"Deep Learning lebih baik" dapat diuji, bukan diasumsikan. Dataset kecil
(~1.000 baris) dengan fitur tabular sederhana memang condong menguntungkan
model linear.

## Menjalankan eksperimen

```bash
cd deep_learning

python -m src.cli train                 # latih MLP, catat eksperimen
python -m src.cli baseline              # baseline klasik
python -m src.cli compare               # bandingkan MLP vs baseline
python -m src.cli experiments           # tampilkan run terbaik
python -m src.cli train --hidden 128,64 --epochs 300
```

Setiap run dicatat ke `experiments/runs/<timestamp>_<nama>.json` (tidak
di-commit) sehingga konfigurasi dapat dibandingkan.

## API

```bash
cd deep_learning && uvicorn src.api:app --port 8400
```

Endpoint: `/health`, `/metrics`, `/baseline`, `/experiments`.

---

# Notebook

Notebook (opsional) dipakai untuk eksplorasi dan visualisasi di luar alur CLI:

* Learning
* Experiment
* Visualization
* Model prototyping
* Evaluation

Contoh alur:

```text
Notebook
   ↓
Experiment
   ↓
Evaluation
   ↓
Best Approach
   ↓
Production Implementation
```

Implementasi production dapat dipindahkan ke project atau service yang sesuai setelah model stabil.

---

# Teknologi

Teknologi yang dapat digunakan:

* Python
* PyTorch
* TensorFlow
* NumPy
* Pandas
* Scikit-learn
* OpenCV
* OCR Framework

Pemilihan framework disesuaikan dengan eksperimen.

---

# Prinsip Pengembangan

* Experiment-driven development
* Reproducible training
* Versioned experiments
* Clear evaluation
* Model comparison
* Explainability jika memungkinkan
* Separation antara experiment dan production

---

# Pengembangan

Masuk ke directory:

```bash
cd deep_learning
```

Buat environment:

```bash
python -m venv .venv
```

Aktifkan:

```bash
source .venv/bin/activate
```

Install dependency sesuai project:

```bash
pip install -r requirements.txt
```

Jalankan Jupyter:

```bash
jupyter notebook
```

---

<div align="center">

**Deep Learning**

Kode sumber ditujukan untuk GNU AGPL-3.0-only; dokumentasi buatan proyek mengikuti CC BY 4.0. Lihat lisensi di root repository.

</div>

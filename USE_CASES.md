# Use Case Machine Learning untuk HR

Dokumen ini menjawab dua pertanyaan:

1. **Selain prediksi turnover, model ML apa lagi yang relevan untuk HR?**
2. **Referensi & data apa yang bisa dipakai untuk masing-masing kasus?**

Turnover hanyalah satu titik di sepanjang **employee lifecycle**. Hampir setiap
tahap punya masalah yang bisa didekati dengan Machine Learning.

```text
Rekrutmen → Onboarding → Kinerja → Pengembangan → Engagement
          → Retensi (turnover) → Payroll → Kepatuhan → Offboarding
```

> **Cara memakai dokumen ini.** Pilih satu use case, cek kolom "Data yang
> dibutuhkan" — kalau datanya belum ada, itu pekerjaan pertama (data engineering),
> bukan modeling. Model tanpa data tidak ada artinya.

---

# Ringkasan Use Case

| # | Use case | Jenis ML | Komponen di repo | Status |
|---|----------|----------|------------------|--------|
| 1 | Employee Turnover Prediction | Klasifikasi biner | `hr_intelligence_api` | ✅ v1 & v2 (+ v2_mitigated) |
| 2 | Attrition Survival Analysis | Survival analysis | `hr_intelligence_api` | ✅ `survival_attrition/v1` + API |
| 3 | Absenteeism Prediction | Klasifikasi / regresi | `hr_intelligence_api` | ✅ `02_absenteeism.ipynb` |
| 4 | Performance Rating Prediction | Klasifikasi ordinal | `hr_intelligence_api` | ✅ `03_performance_prediction.ipynb` |
| 5 | High-Potential (HiPo) / Promotion Readiness | Klasifikasi | `hr_intelligence_api` | rencana |
| 6 | Payroll Anomaly Detection | Anomaly detection | `payroll_intelligence` | ✅ `04_payroll_anomaly_detection.ipynb` |
| 7 | Payroll / Headcount Forecasting | Time series | `payroll_intelligence` | ✅ `/forecast/*` (Holt) |
| 8 | Overtime & Cost Analytics | Regresi / clustering | `payroll_intelligence` | sebagian (`/overtime`) |
| 9 | Resume Screening & Person-Job Fit | NLP / ranking | `ai_hr_assistant` | rencana |
| 10 | Job / Training Recommendation | Recommender system | `ai_hr_assistant` | rencana |
| 11 | Employee Review Sentiment & Topic | NLP | `ai_hr_assistant` | rencana |
| 12 | HR Knowledge Assistant (RAG) | LLM + retrieval | `ai_hr_assistant` | ✅ hybrid lexical + semantik |
| 13 | Document Understanding / OCR | Computer vision + NLP | `deep_learning` | rencana |
| 14 | Attrition Driver Discovery | Explainability / causal | `mlops` + API | sebagian (permutation importance) |
| 15 | Workforce Segmentation | Clustering | `hr_dashboard` | rencana |
| 16 | Fairness & Bias Audit | Model evaluation | `mlops` | ✅ audit + mitigasi |

## Artifact yang sudah tersedia

```text
models/
├── v1/                        # turnover — Logistic Regression
├── v2/                        # turnover — Soft Voting ensemble (default API)
├── v2_mitigated/              # turnover — v2 + reweighting (lebih adil)
├── absenteeism/v1/            # absensi — model + metadata
├── performance/v1/            # kinerja — model + metadata
├── payroll_anomaly/v1/        # anomali payroll — IsolationForest
├── survival_attrition/v1/     # survival — Cox PH
├── fairness_report.json       # audit bias turnover v1 vs v2
├── mitigation_report.json     # hasil mitigasi bias
└── significance_report.json   # uji DeLong/bootstrap v1 vs v2
```

## Temuan audit fairness (ringkas)

Audit `05_fairness_audit_turnover.ipynb` menunjukkan **v2 lebih tidak setara**
daripada v1 pada kelompok `status_kerja` dan `pendidikan` (impact ratio v2 0,45–0,48
vs v1 0,58–0,62). Artinya memilih model hanya dari ROC-AUC dapat memperburuk
keadilan. Lihat `models/fairness_report.json`.

Tindak lanjut `08_bias_mitigation_turnover.ipynb`: **reweighting** menaikkan
impact ratio `status_kerja` 0,47→0,82 dan `pendidikan` 0,48→0,83 (2/4 atribut
lolos four-fifths) dengan penurunan AUC OOF yang kecil (0,697→0,669).

---

# 1. Employee Turnover Prediction

Sudah diimplementasikan. Dua versi tersedia:

- **v1** — Logistic Regression (`notebooks/train_model.ipynb`).
- **v2** — perbandingan 7 metode + ensemble (`notebooks/train_model_alternatives.ipynb`).

**Pengembangan lanjutan:**

- Tambah sinyal yang jauh lebih kuat tapi belum ada di data: rating kinerja,
  skor engagement, jarak tempuh, jumlah cuti, riwayat mutasi, manajer langsung.
- Fitur relatif: gaji dibanding median departemen/jabatan (hitung di dalam
  `Pipeline` agar tidak bocor).
- Uji bias per kelompok (usia, gender, departemen) sebelum dipakai produksi.

**Referensi & data:**

- IBM HR Analytics Employee Attrition dataset — <https://www.kaggle.com/datasets/pavansanagapati/advisory-dataset-employee-attrition>
- Saradhi & Palshikar, *Employee churn prediction*, Expert Systems with Applications (2011).
- Fallucchi et al., *Predicting Employee Attrition Using Machine Learning Techniques*, Computers (MDPI, 2021).

---

# 2. Attrition Survival Analysis

**Pertanyaan bisnis:** bukan *"apakah"* karyawan keluar, tapi *"kapan"* — dan
faktor apa yang mempercepat/memperlambat.

**Jenis ML:** survival analysis (time-to-event).

- Target: durasi bekerja + indikator kejadian (censored).
- Metode: Cox Proportional Hazards, Random Survival Forest, Gradient Boosting
  Survival (XGBoost `survival:cox`, scikit-survival).

**Data yang dibutuhkan:** `tanggal_masuk`, `tanggal_keluar`, status keluar,
plus fitur kovariat. **Belum ada di dataset saat ini** — perlu kolom tanggal keluar.

**Output:** kurva survival per segmen, hazard ratio per fitur, perkiraan
risiko keluar dalam N bulan ke depan.

**Referensi:**

- Cox, *Regression Models and Life-Tables*, JRSS-B (1972).
- scikit-survival — <https://scikit-survival.readthedocs.io/>
- Rekomendasi ini juga muncul di `notebooks/train_model.ipynb` (bagian Rekomendasi Lanjutan).

---

# 3. Absenteeism Prediction

**Pertanyaan bisnis:** karyawan mana yang berisiko absen tinggi / sering tidak
hadir, supaya bisa diintervensi lebih awal.

**Jenis ML:** klasifikasi (absen melebihi ambang) atau regresi (jumlah jam absen).

**Data yang dibutuhkan:** riwayat absensi, alasan absen, jadwal shift, lembur,
departemen, usia, jarak tempuh, beban kerja.

**Metode:** Logistic Regression, Random Forest, XGBoost; untuk data deret waktu
per karyawan, gunakan fitur rolling (rata-rata absen 30/90 hari terakhir).

**Referensi & data:**

- UCI *Absenteeism at Work* dataset — <https://archive.ics.uci.edu/dataset/445/absenteeism+at+work>
- Literatur HR analytics tentang *absence management* (mis. Chartered Institute of Personnel and Development, CIPD).

---

# 4. Performance Rating Prediction

**Pertanyaan bisnis:** memprediksi rating kinerja periode berikutnya untuk
perencanaan coaching, bonus, dan suksesi.

**Jenis ML:** regresi (skor kontinu) atau klasifikasi ordinal (bucket rating).

**Data:** rating historis, KPI/OKR, output penjualan, kehadiran, pelatihan,
masa kerja, penilaian 360°.

**Metode:** regresi linear ter-regularisasi (Lasso/Ridge), Gradient Boosting,
ordinal regression. Perhatikan **leakage**: jangan pakai informasi yang baru
diketahui setelah periode evaluasi.

**Referensi:**

- *People analytics* / HR analytics literature (mis. Ben Waber, *People Analytics*, 2013).
- IBM HR Analytics dataset juga punya kolom `PerformanceRating` untuk eksperimen.

---

# 5. High-Potential (HiPo) & Promotion Readiness

**Pertanyaan bisnis:** siapa yang layak masuk talent pool / siap promosi?

**Jenis ML:** klasifikasi biner/ordinal (dipromosikan dalam 12–24 bulan?).

**Data:** riwayat promosi, rating kinerja, kompetensi, mobilitas, mentor,
pelatihan, keterlibatan proyek.

**Metode:** Gradient Boosting, Logistic Regression; sangat penting audit bias
(lihat use case #16).

**Referensi:**

- Bian et al., *Person-Job Fit: Adapting the Right Talent for the Right Job with Joint Representation Learning*, ACM TMIS (2019).
- Literatur *talent management* & *succession planning*.

---

# 6. Payroll Anomaly Detection

**Pertanyaan bisnis:** mendeteksi pembayaran yang tidak wajar (salah input,
duplikat, lembur ekstrem, potongan janggal, potensi fraud).

**Jenis ML:** anomaly detection (unsupervised) + rule-based.

**Metode:**

- Rule-based: batas IQR, aturan bisnis (mis. lembur > X jam).
- Statistik: z-score, robust Mahalanobis.
- ML: Isolation Forest, Local Outlier Factor, One-Class SVM, Autoencoder.

**Data:** komponen payroll per karyawan per periode (gaji pokok, tunjangan,
lembur, potongan, pajak), data historis beberapa periode.

**Referensi:**

- Liu et al., *Isolation Forest*, ICDM (2008).
- Pedregosa et al., *scikit-learn* (2011) — modul `sklearn.ensemble.IsolationForest`.
- Literatur *financial fraud detection* (mis. Bolton & Hand, *Statistical Fraud Detection: A Review*, 2002).

---

# 7. Payroll / Headcount Forecasting

**Pertanyaan bisnis:** berapa biaya payroll dan kebutuhan headcount beberapa
bulan/kuartal ke depan?

**Jenis ML:** time series forecasting (univariate & hierarchical).

**Metode:** ARIMA/SARIMAX, Exponential Smoothing, **Prophet**, gradient boosting
dengan fitur kalender, atau hierarchical reconciliation untuk total → departemen.

**Data:** seri waktu payroll bulanan per departemen, headcount, attrition rate,
rencana rekrutmen, kalender (THR, bonus tahunan).

**Referensi:**

- Taylor & Letham, *Forecasting at Scale*, The American Statistician (2018) — Prophet.
- Hyndman & Athanasopoulos, *Forecasting: Principles and Practice* (buku online gratis: <https://otexts.com/fpp3/>).

---

# 8. Overtime & Cost Analytics

**Pertanyaan bisnis:** siapa/departemen mana yang lembur berlebihan, dan
bagaimana hubungannya dengan biaya, kelelahan, dan turnover?

**Jenis ML:** regresi (biaya lembur), clustering (pola departemen), korelasi.

**Data:** jam lembur per karyawan/periode, tarif, departemen, proyek.

**Metode:** regresi ter-regularisasi, K-Means untuk segmentasi beban kerja,
analisis kausal sederhana (DiD) bila ada perubahan kebijakan.

**Referensi:** analitik ketenagakerjaan / *workforce analytics* (SHRM, CIPD).

---

# 9. Resume Screening & Person-Job Fit

**Pertanyaan bisnis:** membantu menyaring & memeringkat kandidat terhadap
kebutuhan jabatan.

**Jenis ML:** NLP (parsing, embedding, ranking/similarity).

**Metode:** ekstraksi entitas (NER) untuk skill/pendidikan/pengalaman,
sentence embeddings (SBERT), learning-to-rank, similarity ke job description.

**⚠️ Risiko tinggi:** alat rekrutmen otomatis termasuk **high-risk AI** menurut
EU AI Act dan diatur NYC Local Law 144. Wajib audit bias dan transparansi.

**Referensi:**

- Dastin (Reuters, 2018), *Amazon scraps secret AI recruiting tool that showed bias against women*.
- Bian et al. (2019) — Person-Job Fit.
- Reimers & Gurevych, *Sentence-BERT* (EMNLP 2019).
- NYC Local Law 144 (2023) — <https://www.nyc.gov/site/dca/about/automated-employment-decision-tools.page>
- EU AI Act (Regulation 2024/1689), Annex III (employment = high-risk).

---

# 10. Job / Training Recommendation

**Pertanyaan bisnis:** merekomendasikan lowongan internal, mentor, atau kursus
yang relevan untuk pengembangan karyawan.

**Jenis ML:** recommender system (collaborative filtering, content-based, hybrid).

**Metode:** matrix factorization, item-item similarity, embedding-based retrieval.

**Data:** riwayat pelatihan, riwayat mutasi/promosi, skill, rating kursus.

**Referensi:**

- Koren et al., *Matrix Factorization Techniques for Recommender Systems*, IEEE Computer (2009).
- Literatur *learning & development analytics*.

---

# 11. Employee Review Sentiment & Topic

**Pertanyaan bisnis:** memantau sentimen dan isu dominan dari survei karyawan
atau ulasan publik (mis. Glassdoor).

**Jenis ML:** NLP klasifikasi sentimen + topic modeling.

**Metode:** TF-IDF + Logistic Regression, transformer fine-tuning (IndoBERT untuk
bahasa Indonesia), BERTopic/LDA untuk topik.

**Data:** hasil survei engagement (teks bebas), ulasan karyawan.

**Referensi:**

- Devlin et al., *BERT* (NAACL 2019).
- Grootendorst, *BERTopic* (2022).
- Literatur *employee engagement* (Gallup Q12).

---

# 12. HR Knowledge Assistant (RAG)

**Pertanyaan bisnis:** menjawab pertanyaan kebijakan HR (cuti, reimbursement,
prosedur) dengan bahasa natural.

**Jenis ML:** LLM + Retrieval-Augmented Generation.

**Metode:** embedding dokumen → vector store → retrieval → LLM menjawab dengan
sitasi sumber. Lihat `ai_hr_assistant/README.md`.

**Data:** kebijakan, prosedur, regulasi, FAQ (knowledge base).

**Referensi:**

- Lewis et al., *Retrieval-Augmented Generation for Knowledge-Intensive NLP Tasks*, NeurIPS (2020).
- Dokumentasi LangChain / LlamaIndex.

---

# 13. Document Understanding / OCR

**Pertanyaan bisnis:** mengubah dokumen HR (KTP, ijazah, kontrak, slip gaji)
menjadi data terstruktur.

**Jenis ML:** computer vision + NLP.

**Metode:** OCR (Tesseract, PaddleOCR), LayoutLM/LayoutLMv3 untuk memahami tata
letak, ekstraksi field terstruktur.

**Data:** scan dokumen berlabel (field yang ingin diekstrak).

**Referensi:**

- Xu et al., *LayoutLM: Pre-training of Text and Layout for Document Image Understanding*, KDD (2020).
- Smith, *Tesseract OCR engine* (2007).
- Li et al., *TrOCR* (2021).

---

# 14. Attrition Driver Discovery

**Pertanyaan bisnis:** *apa penyebab* turnover, bukan sekadar memprediksi.

**Jenis ML:** explainability + inferensi kausal.

**Metode:** permutation importance, SHAP, Partial Dependence; untuk klaim kausal
gunakan DAG + DoWhy / causal forest (jangan menyamakan korelasi dengan sebab).

**Data:** sama dengan turnover, idealnya ada data eksperimen/intervensi.

**Referensi:**

- Lundberg & Lee, *A Unified Approach to Interpreting Model Predictions* (SHAP), NeurIPS (2017).
- Pearl, *Causality* (2009).
- Sharma & Kiciman, *DoWhy* (Microsoft Research).

---

# 15. Workforce Segmentation

**Pertanyaan bisnis:** menemukan kelompok karyawan dengan karakteristik serupa
untuk kebijakan yang lebih tepat sasaran.

**Jenis ML:** clustering.

**Metode:** K-Means, Gaussian Mixture, DBSCAN, hierarchical clustering +
reduksi dimensi (PCA/UMAP).

**Data:** fitur demografis, kinerja, kompensasi, engagement.

**Referensi:** literatur *people segmentation* & *workforce analytics*.

---

# 16. Fairness & Bias Audit (WAJIB untuk use case HR)

Setiap model HR harus diaudit sebelum dipakai untuk keputusan yang berdampak
pada orang.

**Yang harus diukur:**

- Performa per kelompok (usia, gender, departemen, pendidikan).
- Metrik fairness: demographic parity, equal opportunity, equalized odds.
- Dampak keputusan: rasio seleksi (four-fifths rule).

**Regulasi & referensi:**

- Barocas, Hardt, Narayanan — *Fairness and Machine Learning* (buku gratis: <https://fairmlbook.org/>).
- EEOC *Uniform Guidelines on Employee Selection Procedures* (four-fifths rule).
- NYC Local Law 144 — audit bias tahunan untuk alat keputusan kerja.
- EU AI Act (Regulation 2024/1689) — sistem AI di bidang ketenagakerjaan = high-risk.
- UU PDP (Indonesia, UU No. 27/2022) untuk perlindungan data pribadi karyawan.

---

# Metode ML: Kapan Dipakai

| Masalah | Metode yang cocok |
|---------|-------------------|
| Target ya/tidak | Logistic Regression, Random Forest, Gradient Boosting, SVM |
| Target kontinu | Linear/Ridge/Lasso, Gradient Boosting, regresi kuantil |
| Target waktu-ke-kejadian | Cox PH, Random Survival Forest, GBSA |
| Tidak ada label (cari keanehan) | Isolation Forest, LOF, One-Class SVM, Autoencoder |
| Deret waktu | ARIMA/SARIMAX, ETS, Prophet, gradient boosting + fitur kalender |
| Kelompok alami | K-Means, GMM, DBSCAN, hierarchical |
| Teks | TF-IDF + linear, transformer (IndoBERT), embeddings |
| Rekomendasi | matrix factorization, content-based, hybrid |
| Ingin tahu "kenapa" | permutation importance, SHAP, PDP; kausal: DAG + DoWhy |

---

# Referensi Umum (Buku & Sumber Belajar)

- Géron, *Hands-On Machine Learning with Scikit-Learn, Keras & TensorFlow* (O'Reilly).
- Hastie, Tibshirani, Friedman, *The Elements of Statistical Learning* (gratis: <https://hastie.su.domains/ElemStatLearn/>).
- James et al., *An Introduction to Statistical Learning* (gratis: <https://www.statlearning.com/>).
- Hyndman & Athanasopoulos, *Forecasting: Principles and Practice* (<https://otexts.com/fpp3/>).
- Barocas, Hardt, Narayanan, *Fairness and Machine Learning* (<https://fairmlbook.org/>).
- Provost & Fawcett, *Data Science for Business*.
- scikit-learn user guide — <https://scikit-learn.org/stable/user_guide.html>
- Google People + AI Guidebook — <https://pair.withgoogle.com/guidebook/>

---

# Catatan

- Beberapa tautan dataset/literatur dapat berubah; verifikasi sebelum dipakai.
- Dataset HR asli bersifat **sensitif**. Jangan menyimpan data pribadi karyawan
  di repository; gunakan data anonim/sintetis untuk eksperimen (lihat
  `data/hr_turnover.csv` yang sudah anonim).
- Model HR dipakai sebagai **alat bantu keputusan**, bukan pengganti keputusan
  manusia. Lihat use case #16 sebelum menaruh model apa pun di jalur keputusan.

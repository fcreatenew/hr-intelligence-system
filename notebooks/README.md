# Notebook

Folder ini berisi notebook training untuk model Machine Learning HR Intelligence System.

```text
notebooks/
├── train_model.ipynb                    # v1 — Logistic Regression (baseline)
├── train_model_alternatives.ipynb       # v2 — perbandingan metode alternatif
├── 02_absenteeism.ipynb                 # use case #3 — prediksi absen tinggi
├── 03_performance_prediction.ipynb      # use case #4 — prediksi kinerja
├── 04_payroll_anomaly_detection.ipynb   # use case #6 — anomali payroll
├── 05_fairness_audit_turnover.ipynb     # use case #16 — audit bias model turnover
├── 06_survival_attrition.ipynb          # use case #2 — survival analysis attrition
├── 07_significance_test.ipynb           # v1 vs v2 — DeLong & bootstrap
└── 08_bias_mitigation_turnover.ipynb    # use case #16 — mitigasi bias turnover
```

---

## `train_model.ipynb` (v1)

Pipeline training awal untuk **Employee Turnover Prediction**.

- Model: **Logistic Regression** (tuned `C = 0.00603`).
- Alur: load & audit → EDA → feature engineering → split stratified → pipeline
  preprocessing → perbandingan model (CV) → tuning → pemilihan threshold (OOF) →
  evaluasi holdout → interpretasi → simpan artifact.
- Output: `models/v1/`.

## `train_model_alternatives.ipynb` (v2)

Notebook ini menjawab pertanyaan: *"selain Logistic Regression, metode apa lagi
yang bisa dipakai — dan apakah lebih baik?"*

- Kandidat: Logistic Regression, Random Forest, Hist Gradient Boosting,
  XGBoost, LightGBM, CatBoost, SVM (RBF), dan **Soft Voting ensemble**.
- Split, fitur, dan metrik **sama persis** dengan v1 supaya perbandingan adil.
- Metrik tambahan: **Brier score** + kurva kalibrasi.
- Interpretasi: permutation importance + **SHAP**.
- Output: `models/v2/`.

Ringkasan hasil (ROC-AUC CV, data train):

| Metode                      | ROC-AUC CV |
| --------------------------- | ---------: |
| Soft Voting (ensemble)      |     0,7085 |
| Random Forest (tuned)       |     0,7050 |
| Logistic Regression (tuned) |     0,6971 |

**Kesimpulan:** selisih antar metode kecil (±0,04) dan lebih kecil dari lebar
confidence interval. Untuk dataset kecil dengan sinyal yang sebagian besar
monoton, model linear ter-regularisasi sangat kompetitif. Lihat
`models/README.md` untuk detail lengkap.

---

## `02_absenteeism.ipynb` (use case #3)

Model HR kedua: memprediksi **absen tinggi** (masalah kehadiran).

- Data: `data/hr_absenteeism.csv` (**sintetis**, dibangkitkan oleh
  `scripts/generate_absenteeism_data.py`).
- Alur: EDA → pipeline (imputer + scaler + one-hot) → perbandingan 6–7 model (CV)
  → tuning → threshold dari OOF → evaluasi holdout → permutation importance.
- Output: `models/absenteeism/v1/`.

Kolom yang dikecualikan sebagai fitur (kebocoran): `employee_id`,
`hari_absen_setahun`, `alasan_dominan`, `tanggal_masuk`.

## `05_fairness_audit_turnover.ipynb` (use case #16)

Audit **keadilan/bias** model turnover v1 vs v2.

- Kelompok uji: kelompok usia, departemen, status kerja, pendidikan.
- Metrik: selection rate, TPR/FPR, demographic parity, equal opportunity,
  equalized odds, impact ratio (aturan four-fifths).
- Output: `models/fairness_report.json`.

> Audit bersifat *in-sample* dan dataset tidak punya atribut gender/etnis —
> keterbatasan ini harus ditutup sebelum model dipakai untuk keputusan nyata.
> Tindak lanjut mitigasinya ada di `08_bias_mitigation_turnover.ipynb`.

## `03_performance_prediction.ipynb` (use case #4)

Memprediksi kategori kinerja (Rendah/Sedang/Tinggi) dari `data/hr_performance.csv`
(sintetis). Target **ordinal**, jadi metrik utama macro-F1 dan **MAE ordinal**
(bukan akurasi saja). Output: `models/performance/v1/`.

## `04_payroll_anomaly_detection.ipynb` (use case #6)

Deteksi anomali payroll dari `data/hr_payroll.csv` (sintetis, label buatan ~4%).
Membandingkan aturan z-score, `IsolationForest`, `LocalOutlierFactor`, dan
`RandomForest` (supervised). Metrik utama PR-AUC & precision@k.
Output: `models/payroll_anomaly/v1/`.

> Anomali sintetis sengaja dibuat mencolok, sehingga metrik tampak sangat tinggi;
> pada data nyata angkanya akan jauh lebih rendah.

## `06_survival_attrition.ipynb` (use case #2)

Survival analysis attrition dari `data/hr_attrition_survival.csv` (sintetis):
Kaplan–Meier, uji log-rank, dan Cox Proportional Hazards (`lifelines`).
Menghasilkan hazard ratio dan C-index. Output: `models/survival_attrition/v1/`.

## `07_significance_test.ipynb`

Uji apakah v2 benar-benar lebih baik dari v1: **DeLong test** + **bootstrap
berpasangan** pada test set. Output: `models/significance_report.json`.

## `08_bias_mitigation_turnover.ipynb` (use case #16)

Mitigasi bias v2 dengan **reweighting Kamiran–Calders** dan **threshold per
kelompok**, lalu mengukur ulang fairness (OOF). Output:
`models/v2_mitigated/` dan `models/mitigation_report.json`.

---

## Menjalankan notebook

Jalankan dari **root repo** (notebook mencari `data/*.csv` relatif ke cwd):

```bash
python -m venv .venv && source .venv/bin/activate
pip install pandas numpy scikit-learn scipy joblib matplotlib seaborn \
            xgboost lightgbm catboost shap jupyter
jupyter notebook notebooks/train_model_alternatives.ipynb
```

> Notebook akan menulis ulang artifact di `models/`. Jangan jalankan tanpa
> sengaja bila tidak ingin menimpa model yang dipakai API.

---

## Notebook berikutnya yang direncanakan

Katalog use case ML lain untuk HR (beserta data & metode yang dibutuhkan) ada di
[`../USE_CASES.md`](../USE_CASES.md). Kandidat notebook lanjutan antara lain:

- `03_performance_prediction.ipynb`
- `04_payroll_anomaly_detection.ipynb`
- `06_survival_attrition.ipynb`
- `09_resume_screening.ipynb`

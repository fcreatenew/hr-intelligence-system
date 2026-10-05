# Laporan Hasil Model

Dokumen ini merangkum hasil **model lama (v1)** dan **model baru (v2)** untuk
Employee Turnover Prediction, plus model tambahan (absensi) dan audit keadilan.

- Data: `data/hr_turnover.csv` (1.000 baris, rasio positif 0,356)
- Split: 75% train / 25% test (stratified, `random_state = 42`)
- Threshold: 0,33 (dipilih dari prediksi out-of-fold, bukan dari test set)

> **Status default API saat ini:** `MODEL_VERSION=v2_mitigated` (v2 + reweighting,
> threshold 0,31). Dokumen ini mencatat hasil v1/v2 sekaligus mitigasi; angka
> v1/v2 di bawah tetap berlaku untuk perbandingan.

---

## 1. Ringkasan Eksekutif

| Aspek | v1 (lama) | v2 (baru) |
|---|---|---|
| Algoritma | Logistic Regression | **Soft Voting ensemble** (LR + Random Forest) |
| ROC-AUC (test) | 0,6929 | **0,6993** |
| PR-AUC (test) | **0,6235** | 0,6223 |
| Recall | 0,6479 | **0,6761** |
| F1 | 0,538 | **0,5581** |
| Brier | — | 0,2029 |
| Threshold | 0,33 | 0,33 |
| Status API | tersedia (rollback) | tersedia (rollback) |

> Default API = `v2_mitigated` (v2 + reweighting), bukan v1/v2. Lihat bagian 7.

**Kesimpulan singkat:** v2 menang tipis pada ROC-AUC/F1, tetapi **selisihnya
tidak signifikan** (lihat bagian 3) dan v2 **lebih buruk pada keadilan**
(bagian 5). Artinya "model baru" di sini adalah alternatif yang layak, bukan
peningkatan yang terbukti.

---

## 2. Metrik Holdout Test (lengkap)

| Metric | v1 | v2 | Arah |
|---|---:|---:|---|
| ROC-AUC | 0,6929 | 0,6993 | v2 +0,0064 |
| ROC-AUC 95% CI | [0,6136, 0,7724] | [0,6217, 0,7739] | tumpang tindih |
| PR-AUC | **0,6235** | 0,6223 | v1 +0,0012 |
| Brier | — | 0,2029 | v2 saja |
| Accuracy | 0,605 | 0,620 | v2 +0,015 |
| Precision | 0,460 | 0,4752 | v2 +0,0152 |
| Recall | 0,6479 | 0,6761 | v2 +0,0282 |
| F1 | 0,538 | 0,5581 | v2 +0,0201 |

ROC-AUC CV (data train) untuk tuning: v1 = 0,6971, v2 = 0,7085.

---

## 3. Apakah v2 benar-benar lebih baik?

**Belum bisa disimpulkan ya.** Alasannya:

1. **Selisih 0,0064 jauh lebih kecil dari lebar confidence interval** (±0,15).
   Interval kepercayaan v1 dan v2 saling tumpang tindih hampir seluruhnya.
2. **PR-AUC v1 justru sedikit lebih tinggi** — untuk kelas tidak seimbang,
   PR-AUC sering lebih relevan daripada ROC-AUC.
3. Dataset kecil (250 baris test), sehingga noise besar.

Untuk membuktikan v2 lebih baik, perlu **uji statistik** (mis. DeLong test untuk
ROC-AUC atau bootstrap berpasangan) dan data lebih besar.

---

## 4. Perbandingan Semua Metode (CV, data train)

| Metode | ROC-AUC CV | PR-AUC CV |
|---|---:|---:|
| Soft Voting (ensemble) | 0,7085 | 0,6495 |
| Random Forest (tuned) | 0,7050 | 0,6166 |
| Logistic Regression (tuned) | 0,6971 | 0,6290 |
| Logistic Regression (default) | 0,6900 | 0,6290 |
| SVM (RBF) | 0,6704 | 0,6132 |
| CatBoost | 0,6697 | 0,6103 |
| LightGBM | 0,6529 | 0,5815 |
| XGBoost | 0,6510 | 0,5871 |
| Hist Gradient Boosting | 0,6509 | 0,5893 |
| Dummy (prior) | 0,5000 | 0,3563 |

Pelajaran: **boosting tidak unggul** di dataset kecil ini. Model linear
ter-regularisasi sangat kompetitif karena sinyalnya sebagian besar monoton
(`jam_lembur_bulanan` adalah pendorong utama menurut permutation importance).

---

## 5. Audit Keadilan (Fairness)

Sumber: `models/fairness_report.json` (`notebooks/05_fairness_audit_turnover.ipynb`).
Impact ratio = min/max selection rate antar kelompok; ambang four-fifths = 0,80.

| Atribut | Impact ratio v1 | Impact ratio v2 | Lolos 80%? |
|---|---:|---:|---|
| kelompok usia | 0,757 | 0,749 | tidak (keduanya) |
| departemen | 0,838 | 0,731 | v1 ya, v2 tidak |
| status kerja | 0,616 | **0,452** | tidak (keduanya) |
| pendidikan | 0,579 | **0,479** | tidak (keduanya) |

**Temuan:** memilih model hanya dari ROC-AUC **memperburuk ketidaksetaraan**.
v2 menandai kelompok `status_kerja` (mis. Freelance vs Tetap) dan `pendidikan`
secara tidak proporsional.

> Audit ini *in-sample* dan dataset tidak punya atribut gender/etnis — harus
> diulang dengan data held-out dan atribut sensitif lengkap sebelum produksi.

**Rekomendasi mitigasi:** reweighting kelas per kelompok, threshold per kelompok,
kalibrasi, atau kembali ke v1 (yang lebih setara) bila keadilan diprioritaskan.

---

## 6. Uji Signifikansi v1 vs v2

Sumber: `models/significance_report.json` (`notebooks/07_significance_test.ipynb`).

| Uji | Hasil | Arti |
|---|---|---|
| DeLong z | −0,4638 | AUC v1 & v2 tidak beda |
| DeLong p | **0,6428** | jauh di atas 0,05 |
| Bootstrap Δ ROC-AUC | +0,0064 | selisih rata-rata |
| Bootstrap 95% CI | **[−0,0214, +0,0342]** | memuat 0 |
| Bootstrap p | 0,6596 | tidak signifikan |

**Kesimpulan:** secara statistik, **v2 tidak terbukti lebih baik** dari v1.
Selisih 0,0064 berada di dalam noise. Untuk produksi, model yang lebih sederhana
dan lebih adil (v1) tetap pilihan yang lebih aman sampai ada data lebih besar.

---

## 7. Mitigasi Bias (Fairness)

Sumber: `models/mitigation_report.json` (`notebooks/08_bias_mitigation_turnover.ipynb`).
Diukur pada prediksi **out-of-fold**.

| Strategi | Atribut lolos 80% | Impact ratio terendah | ROC-AUC OOF |
|---|---|---:|---:|
| A. Baseline v2 | 0/4 | 0,473 | 0,697 |
| B. **Reweighting** | **2/4** | 0,616 | 0,669 |
| C. Threshold per kelompok | 1/4 | 0,444 | 0,697 |

Impact ratio per atribut yang dimitigasi (reweighting):

| Atribut | Baseline v2 | Reweighting |
|---|---:|---:|
| status_kerja | 0,473 | **0,822** ✅ |
| pendidikan | 0,479 | **0,829** ✅ |

**Kesimpulan:** reweighting memperbaiki dua atribut yang paling tidak setara
dengan biaya AUC OOF hanya ~0,03. Model termitigasi ada di `models/v2_mitigated/`
dan **sudah menjadi default API** (`MODEL_VERSION=v2_mitigated`).

---

## 8. Model Tambahan — Absenteeism (use case #3)

`notebooks/02_absenteeism.ipynb` → `models/absenteeism/v1/` (data **sintetis**).

| Metric | Nilai |
|---|---:|
| Model | Logistic Regression |
| ROC-AUC | 0,8287 |
| PR-AUC | 0,4974 |
| Brier | 0,1148 |
| Precision | 0,3548 |
| Recall | 0,6600 |
| F1 | 0,4615 |
| Threshold | 0,1929 |

## 9. Model Tambahan — Performance Rating (use case #4)

`notebooks/03_performance_prediction.ipynb` → `models/performance/v1/` (sintetis).
Target **ordinal** (Rendah/Sedang/Tinggi).

| Metric | Nilai |
|---|---:|
| Model | Logistic Regression |
| Accuracy | 0,6971 |
| Macro-F1 | 0,6357 |
| Cohen's kappa | 0,4570 |
| **MAE ordinal** | 0,3086 |

MAE ordinal 0,31 (skala 1–3) artinya prediksi rata-rata meleset kurang dari
satu tingkat — cukup baik untuk target ordinal.

## 10. Model Tambahan — Payroll Anomaly (use case #6)

`notebooks/04_payroll_anomaly_detection.ipynb` → `models/payroll_anomaly/v1/`.

| Metode | PR-AUC |
|---|---:|
| Random Forest (supervised, test) | 1,000 |
| LocalOutlierFactor | 0,9887 |
| Aturan z-score | 0,9056 |
| Isolation Forest | 0,8709 |

> Anomali sintetis sengaja dibuat mencolok → metrik sangat tinggi. Pada data
> nyata, PR-AUC akan jauh lebih rendah. Isolation Forest dipilih untuk artifact
> karena tidak butuh label.

## 11. Model Tambahan — Survival Attrition (use case #2)

`notebooks/06_survival_attrition.ipynb` → `models/survival_attrition/v1/`.

| Metric | Nilai |
|---|---:|
| Model | Cox Proportional Hazards |
| C-index | 0,8144 |
| Median survival | 12 bulan |
| Log-rank (departemen) | p < 0,001 |
| Log-rank (pendidikan) | p = 0,697 |

Faktor yang menaikkan risiko keluar (HR > 1): `bulan_sejak_promosi`,
`jam_lembur_bulanan`, `jarak_km`. Faktor protektif (HR < 1): `kepuasan_kerja`.

---

## 12. Reproduksi

```bash
# data sintetis (deterministik)
python scripts/generate_absenteeism_data.py
python scripts/generate_performance_data.py
python scripts/generate_payroll_data.py
python scripts/generate_attrition_survival_data.py

# training / analisis
jupyter notebook notebooks/train_model_alternatives.ipynb      # v2
jupyter notebook notebooks/07_significance_test.ipynb          # signifikansi
jupyter notebook notebooks/08_bias_mitigation_turnover.ipynb   # mitigasi bias
jupyter notebook notebooks/02_absenteeism.ipynb                # absensi
jupyter notebook notebooks/03_performance_prediction.ipynb     # kinerja
jupyter notebook notebooks/04_payroll_anomaly_detection.ipynb  # anomali payroll
jupyter notebook notebooks/06_survival_attrition.ipynb         # survival
```

Versi model yang dilayani API diatur lewat env `MODEL_VERSION`
(default `v2_mitigated`):

```bash
cd hr_intelligence_api
MODEL_VERSION=v2 python -m uvicorn app.main:app   # bandingkan dengan v2
MODEL_VERSION=v1 python -m uvicorn app.main:app   # rollback ke v1
```

---

## 13. Keterbatasan & Langkah Lanjut

- Tambah sinyal yang lebih kuat (rating kinerja, engagement, manajer langsung).
- Uji signifikansi sudah dilakukan → v2 belum terbukti lebih baik.
- Mitigasi bias sudah dicoba → masih 2/4 atribut lolos; lanjutkan ke
  kalibrasi/threshold sadar-keadilan.
- Semua data tambahan masih **sintetis**; validasi ulang pada data nyata.
- Model absensi/kinerja/payroll/survival sudah diekspos API (v1).

# Gap Audit — HR Intelligence System

Dokumen ini membandingkan **kondisi kode yang benar-benar ada** dengan
[`ROADMAP.md`](ROADMAP.md). Tujuannya bukan menambah rencana baru, melainkan
menutup celah antara "sudah dikerjakan" dan "sudah ada di `main`", lalu
menyebut hal yang benar-benar belum dimulai.

> **Basis audit.** `main` pada commit `0ffc8d1` (setelah PR #11 di-merge).
> Pekerjaan yang masih berupa PR terbuka **tidak** dihitung sebagai selesai di
> `main`, tetapi dicatat terpisah karena PR-nya sudah hijau dan menunggu merge.

Legenda: ✅ ada & teruji · 🟡 ada di PR terbuka (hijau, belum merge) · ⬜ belum ada.

---

# 1. Ringkasan

| Area | Di `main` | Menunggu merge | Benar-benar belum |
|---|---|---|---|
| Fase 0 — Fondasi ML + API | ✅ | — | — |
| Fase 1 — Integrasi HRIS ↔ ML | ✅ | — | — |
| Fase 2 — Employee Management | ✅ | — | Fase 2.3 (riwayat) |
| Fase 3 — Attendance & Leave | ✅ | — | impor absensi |
| Fase 4 — Payroll Run | ⬜ | 🟡 PR #12 | integrasi `payroll_intelligence` |
| Fase 5 — Pelaporan & Administrasi | ⬜ | 🟡 PR #15 (laporan + CSV), #19 (UI prediksi ML), #20 (manajemen pengguna) | — |
| Fase 6 — Hardening Produksi | ⬜ | 🟡 PR #10, #13, #14, #18 | monitoring drift, retraining |
| CI semua modul | ✅ | — | — |

---

# 2. Sudah ada di `main` (terverifikasi)

- **Fase 0** — 5 use case ML + survival, `mlops` (registry, validasi, drift),
  `payroll_intelligence`, `deep_learning`, `ai_hr_assistant`, `hr_dashboard`.
- **Fase 1** — `HrIntelligenceService`, `EmployeeFeatureMapper`,
  `HrIntelligenceResultNormalizer`, tabel `hr_intelligence_predictions`,
  command/job batch, penjadwalan, endpoint admin di gate `manage-hr-intelligence`.
- **Fase 2.1/2.2** — model `Employee` + atribut ML, CRUD, validasi, `EmployeePolicy`,
  pencarian/filter/paginasi, halaman Blade, login/logout, seeder.
- **Fase 3.1/3.2** — `Attendance` (satu baris per karyawan per tanggal, dijaga
  di level DB), rekap periode + integrasi ML, `LeaveRequest`/`LeaveBalance`,
  alur persetujuan, saldo cuti per tahun.

Catatan: sejak PR #11 di-merge, `main` kini memiliki CI (8 job) yang menjaga
test tetap hijau.

---

# 3. Menunggu merge (PR terbuka, semua CI hijau)

Semua PR di bawah ini sudah melewati CI 8/8 job pada head terakhir.

| PR | Branch | Isi | Menutup celah |
|---|---|---|---|
| #12 | `feat/payroll-run` | Fase 4 payroll run berbasis periode | Fase 4 |
| #13 | `feat/audit-trail-docker` | Jejak audit otomatis + Dockerfile & docker-compose | audit trail, Docker HRIS/dashboard |
| #14 | `feat/reproducible-training` | CLI training reproducible + gate validasi hasil training | retraining |
| #15 | `feat/reporting-v2mitigated` | Laporan HR + ekspor CSV (Fase 5) & default `v2_mitigated` (Fase 6) | Fase 5 (sebagian), default model |
| #10 | `feat/security-hardening-assistant-dashboard` | Auth API assistant & gerbang API key dashboard | hardening auth |
| #17 | `docs/gap-audit` | Dokumen audit ini | — (dokumentasi) |
| #18 | `feat/rate-limiting` | Rate limiting login & endpoint admin HR Intelligence | §4.1 rate limiting |
| #19 | `feat/ml-prediction-ui` | Halaman HTML hasil prediksi ML | §4.5 UI prediksi ML |
| #20 | `feat/user-role-management` | Manajemen pengguna & peran dari UI | §4.5 manajemen user |

**Sudah merge.** #11 (`feat/ci-github-actions`) sudah masuk `main` (`0ffc8d1`)
dan membawa CI 8 job. #16 (`fix/ci-foundation`) juga sudah masuk `main` karena
di-merge ke branch #11 dan karenanya ikut terbawa — isinya identik dengan #11.

---

# 4. Celah yang benar-benar belum ada

Diurutkan berdasarkan risiko.

## 4.1 Rate limiting belum ada — risiko tertinggi ⬜ → 🟡 (PR #18)

Tidak ditemukan `throttle` maupun `RateLimiter` di `hr_application` (routes
maupun `app/`). Akibatnya:

- **Halaman login** dapat di-brute force tanpa pembatasan.
- Endpoint admin JSON (di gate `manage-hr-intelligence`) tidak dibatasi laju.

Perbaikan: PR #18 menambah limiter `login` (5/menit per email+IP) dan
`admin-api` (30/menit per pengguna). Setelah #18 merge, celah ini tertutup.

## 4.2 Audit trail ⬜ → 🟡

Belum ada kode audit di `main`; hanya ada di PR #13. Roadmap menandainya
"Fase 2.1" (utang #7).

## 4.3 Docker untuk `hr_application` & `hr_dashboard` ⬜ → 🟡

`main` hanya punya Docker untuk API/mlops (`mlops/docker/api`, `mlops/docker/hr-api`).
Dockerfile HRIS & dashboard hanya ada di PR #13 (utang #5).

## 4.4 Model monitoring / drift alert & retraining terjadwal ⬜

Fase 6. `mlops` punya drift (PSI) dan validasi, tetapi **belum ada alert otomatis**
maupun retraining terjadwal + rollback model. PR #14 menyiapkan training
reproducible sebagai fondasi, tetapi bukan penjadwalan/alert.

## 4.5 Fase 5 yang belum tersentuh ⬜

- Manajemen pengguna & peran dari UI — ✅ dikerjakan di PR #20.
- Halaman hasil prediksi ML di UI — ✅ dikerjakan di PR #19 (utang #6).
- Kalender cuti tim — ✅ dikerjakan (grid bulanan cuti disetujui, utang #11).
- Impor mesin absensi/fingerprint — utang #9.
- Kuota cuti per jenis selain tahunan.

## 4.6 Fase 2.3 Employee History ⬜

Riwayat jabatan/departemen/gaji, mutasi & promosi, linimasa per karyawan.

## 4.6b Fase 7 — modul proses bisnis HR 🔶 sebagian

Empat modul Fase 7 kini tersedia di `hr_application` (route + policy +
migrasi + form request + test):

- **Recruitment / ATS** — lowongan + kandidat; terima kandidat membuat record
  `employees` (`RecruitmentService`) sehingga mengalir ke fitur ML.
- **Performance Management** — goal/OKR + review per periode; kategori A–D
  diturunkan dari rating di server.
- **Compensation & Benefits** — komponen tunjangan/potongan/BPJS/THR/bonus/PPh21.
- **Document Management** — metadata dokumen + deteksi kedaluwarsa ≤ 30 hari.
- **Compliance & Statutory Reporting** — draft laporan BPJS/PPh21 (estimasi)
  dari baris payroll, dengan tarif di `config/statutory.php`.

Belum tersentuh: jadwal interview/offer & resume screening (butuh model
`USE_CASES.md` #9), checklist onboarding & aset, rekomendasi pelatihan,
Employee Self-Service (peran `employee`), dan e-sign dokumen.

## 4.7 Kualitas data & metrik ⬜

- Data absensi/kinerja/payroll/survival masih **sintetis** → metrik terlalu
  optimistis (utang #2).
- **Audit fairness masih in-sample** (utang #4).
- Lembur belum dihitung dari jam masuk/keluar; `overtime_minutes` masih manual
  (utang #10).

---

# 5. Utang teknis lain

| # | Utang | Catatan |
|---|---|---|
| 1 | `mlops/ci/github-actions/ci.yml` usang | Dihapus oleh PR #11; pastikan tidak membingungkan setelah merge. |
| 2 | `npm audit` melaporkan 8 vulnerability | Dev-deps dashboard; prioritas rendah, belum ditangani. |
| 3 | Cakupan test Python tipis | `hr_intelligence_api` 2 file test, modul lain 1 file test; CI hijau tapi cakupan belum diukur. |
| 4 | `main` belum punya CI | Selama #11 belum merge, regresi apa pun lolos tanpa terdeteksi. |

---

# 6. Rekomendasi urutan

1. **Merge #11 → `main`** (CI + toolchain), lalu tutup #16 sebagai *superseded*.
   Ini menghentikan risiko §5.4 dan membuat PR berikutnya hijau otomatis.
2. **Rebase #12 di atas `main`** lalu merge (Fase 4, nilai tinggi).
3. Merge **#13** (audit trail + Docker), **#15** (laporan + `v2_mitigated`),
   **#10** (security), **#14** (training).
4. Kerjakan celah nyata: Fase 5 (UI prediksi ML, manajemen user, kalender
   cuti) dan Fase 6 (monitoring drift + retraining terjadwal). Rate limiting
   sudah ditangani di #18.

---

# 7. Cara memperbarui dokumen ini

Audit ini berlaku untuk `main` = `8cc45df`. Setelah PR di §3 ter-merge,
perbarui §2–§3 dan hapus item yang sudah masuk `main`; jangan biarkan dokumen
menyebut status basi (prinsip "dokumentasi mengikuti kode").

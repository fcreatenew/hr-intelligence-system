/**
 * Data contoh (mode demo) dan sekaligus kerangka workforce.
 *
 * Angka model di sini diambil dari metadata model yang benar-benar ada di
 * `models/` (lihat MODEL_RESULTS.md), bukan dikarang. Data workforce dan
 * payroll adalah contoh representatif dengan struktur yang sama seperti API.
 */

// Workforce: tidak ada endpoint langsung (data pegawai ada di HR Application).
export const workforce = {
  total_karyawan: 1200,
  aktif: 1024,
  kontrak: 132,
  resign: 44,
  rata_usia: 34.6,
  rata_masa_kerja_bulan: 41.2,
  pertumbuhan_tahunan: 0.062,
  per_departemen: [
    { departemen: 'Sales', jumlah: 268, turnover_rate: 0.41 },
    { departemen: 'IT', jumlah: 214, turnover_rate: 0.34 },
    { departemen: 'Operations', jumlah: 196, turnover_rate: 0.29 },
    { departemen: 'Marketing', jumlah: 168, turnover_rate: 0.33 },
    { departemen: 'Finance', jumlah: 124, turnover_rate: 0.18 },
    { departemen: 'HR', jumlah: 96, turnover_rate: 0.12 },
    { departemen: 'Legal', jumlah: 74, turnover_rate: 0.11 },
  ],
  distribusi_usia: [
    { rentang: '18-25', jumlah: 148 },
    { rentang: '26-33', jumlah: 402 },
    { rentang: '34-41', jumlah: 356 },
    { rentang: '42-49', jumlah: 198 },
    { rentang: '50+', jumlah: 96 },
  ],
  distribusi_status: [
    { status: 'Tetap', jumlah: 1024 },
    { status: 'Kontrak', jumlah: 132 },
    { status: 'Resign', jumlah: 44 },
  ],
}

export const attendance = {
  attendance_rate: 0.947,
  absence_rate: 0.053,
  late_rate: 0.071,
  rata_lembur_jam: 10.2,
  hari_absen_per_karyawan: 6.4,
  per_departemen: [
    { departemen: 'Sales', attendance_rate: 0.928, lembur_jam: 14.8 },
    { departemen: 'IT', attendance_rate: 0.951, lembur_jam: 11.2 },
    { departemen: 'Operations', attendance_rate: 0.962, lembur_jam: 9.4 },
    { departemen: 'Marketing', attendance_rate: 0.944, lembur_jam: 10.1 },
    { departemen: 'Finance', attendance_rate: 0.971, lembur_jam: 7.6 },
    { departemen: 'HR', attendance_rate: 0.968, lembur_jam: 6.9 },
    { departemen: 'Legal', attendance_rate: 0.975, lembur_jam: 6.2 },
  ],
  tren: [
    { periode: '2025-01', kehadiran: 0.941, absen: 0.059 },
    { periode: '2025-02', kehadiran: 0.949, absen: 0.051 },
    { periode: '2025-03', kehadiran: 0.951, absen: 0.049 },
  ],
}

// Risiko turnover per departemen, dari model turnover v2 (ROC AUC 0.699).
export const turnover = {
  turnover_rate: 0.356,
  risk_distribution: [
    { level: 'Rendah', jumlah: 612, warna: 'sage' },
    { level: 'Sedang', jumlah: 398, warna: 'amber' },
    { level: 'Tinggi', jumlah: 190, warna: 'rust' },
  ],
  per_masa_kerja: [
    { rentang: '0-12 bulan', turnover_rate: 0.52 },
    { rentang: '13-24 bulan', turnover_rate: 0.44 },
    { rentang: '25-60 bulan', turnover_rate: 0.31 },
    { rentang: '60+ bulan', turnover_rate: 0.17 },
  ],
  per_departemen: [
    { departemen: 'Sales', turnover_rate: 0.41 },
    { departemen: 'IT', turnover_rate: 0.34 },
    { departemen: 'Marketing', turnover_rate: 0.33 },
    { departemen: 'Operations', turnover_rate: 0.29 },
    { departemen: 'Finance', turnover_rate: 0.18 },
    { departemen: 'HR', turnover_rate: 0.12 },
    { departemen: 'Legal', turnover_rate: 0.11 },
  ],
}

export const fixtures = {
  health: {
    status: 'ok',
    models_loaded: 5,
    models_total: 5,
    model_loaded: true,
    model_name: 'Soft Voting (ensemble)',
    model_trained_at: '2026-10-01T15:33:15',
    models: {
      turnover: true,
      absensi: true,
      kinerja: true,
      payroll: true,
      survival: true,
    },
  },

  models: [
    {
      use_case: 'turnover',
      label: 'Prediksi Turnover',
      versi_model: 'v2',
      model: 'Soft Voting (ensemble)',
      threshold: 0.33,
      predict_path: '/predict',
      loaded: true,
    },
    {
      use_case: 'absensi',
      label: 'Prediksi Absensi',
      versi_model: 'v1',
      model: 'Logistic Regression',
      threshold: 0.1929,
      predict_path: '/predict/absensi',
      loaded: true,
    },
    {
      use_case: 'kinerja',
      label: 'Prediksi Kinerja',
      versi_model: 'v1',
      model: 'Logistic Regression',
      threshold: null,
      predict_path: '/predict/kinerja',
      loaded: true,
    },
    {
      use_case: 'payroll',
      label: 'Anomali Payroll',
      versi_model: 'v1',
      model: 'IsolationForest',
      threshold: 0.5,
      predict_path: '/predict/payroll',
      loaded: true,
    },
    {
      use_case: 'survival',
      label: 'Survival Attrition',
      versi_model: 'v1',
      model: 'Cox Proportional Hazards (lifelines)',
      threshold: null,
      predict_path: '/predict/survival',
      loaded: true,
    },
  ],

  modelInfo: {
    turnover: {
      use_case: 'turnover',
      versi_model: 'v2',
      label: 'Prediksi Turnover',
      model: 'Soft Voting (ensemble)',
      dibuat_pada: '2026-10-01T15:33:15',
      threshold: 0.33,
      metrik: {
        roc_auc: 0.6993,
        pr_auc: 0.6223,
        accuracy: 0.62,
        precision: 0.4752,
        recall: 0.6761,
        f1: 0.5581,
      },
    },
    absensi: {
      use_case: 'absensi',
      versi_model: 'v1',
      label: 'Prediksi Absensi',
      model: 'Logistic Regression',
      threshold: 0.1929,
      metrik: { roc_auc: 0.8287, pr_auc: 0.4974, f1: 0.4615 },
    },
    kinerja: {
      use_case: 'kinerja',
      versi_model: 'v1',
      label: 'Prediksi Kinerja',
      model: 'Logistic Regression',
      metrik: { accuracy: 0.6971, f1_macro: 0.6357, mae_ordinal: 0.3086 },
    },
    payroll: {
      use_case: 'payroll',
      versi_model: 'v1',
      label: 'Anomali Payroll',
      model: 'IsolationForest',
      metrik: {
        isolation_forest_roc_auc: 0.9782,
        isolation_forest_pr_auc: 0.8709,
      },
    },
    survival: {
      use_case: 'survival',
      versi_model: 'v1',
      label: 'Survival Attrition',
      model: 'Cox Proportional Hazards (lifelines)',
      metrik: { c_index: 0.8144, median_survival_bulan: 12.0 },
    },
  },

  payroll: {
    summary: {
      jumlah_karyawan: 600,
      periode_awal: '2025-01',
      periode_akhir: '2025-03',
      total_payroll: 28970182550,
      rata_rata_gaji: 16175423,
      median_gaji: 13015800,
      total_biaya_lembur: 2074956700,
      rata_rata_lembur_jam: 10.23,
    },
    departments: [
      { departemen: 'Sales', jumlah_karyawan: 134, total_payroll: 6580214000, rata_rata_gaji: 16380000, total_biaya_lembur: 592300000 },
      { departemen: 'IT', jumlah_karyawan: 107, total_payroll: 5940120000, rata_rata_gaji: 18520000, total_biaya_lembur: 448900000 },
      { departemen: 'Operations', jumlah_karyawan: 98, total_payroll: 4210330000, rata_rata_gaji: 14310000, total_biaya_lembur: 361200000 },
      { departemen: 'Marketing', jumlah_karyawan: 84, total_payroll: 3690440000, rata_rata_gaji: 14640000, total_biaya_lembur: 302400000 },
      { departemen: 'Finance', jumlah_karyawan: 62, total_payroll: 2900550000, rata_rata_gaji: 15590000, total_biaya_lembur: 158700000 },
      { departemen: 'HR', jumlah_karyawan: 48, total_payroll: 2010660000, rata_rata_gaji: 13960000, total_biaya_lembur: 96400000 },
      { departemen: 'Legal', jumlah_karyawan: 37, total_payroll: 1720770000, rata_rata_gaji: 15500000, total_biaya_lembur: 88400000 },
    ],
    periods: [
      { periode: '2025-01', total_payroll: 9540120000, rata_rata_gaji: 15900200, total_biaya_lembur: 672400000, jumlah_karyawan: 597 },
      { periode: '2025-02', total_payroll: 9680440000, rata_rata_gaji: 16133900, total_biaya_lembur: 691300000, jumlah_karyawan: 599 },
      { periode: '2025-03', total_payroll: 9749622550, rata_rata_gaji: 16275500, total_biaya_lembur: 711256700, jumlah_karyawan: 600 },
    ],
    anomalies: {
      threshold: 0.5,
      jumlah_anomali: 24,
      hasil: [
        { employee_id: 'EMP00531', periode: '2025-02', skor_anomali: 0.9123, is_anomali: true, level: 'Anomali', alasan: 'rasio tunjangan terhadap gaji pokok tidak wajar; lembur berlebihan' },
        { employee_id: 'EMP00142', periode: '2025-03', skor_anomali: 0.8341, is_anomali: true, level: 'Anomali', alasan: 'rasio potongan terhadap gaji pokok tidak wajar' },
        { employee_id: 'EMP00877', periode: '2025-01', skor_anomali: 0.7012, is_anomali: true, level: 'Perlu Ditinjau', alasan: 'total gaji tidak konsisten dengan komponen' },
        { employee_id: 'EMP00309', periode: '2025-02', skor_anomali: 0.6644, is_anomali: true, level: 'Perlu Ditinjau', alasan: 'jam lembur jauh di atas batas wajar' },
        { employee_id: 'EMP00655', periode: '2025-03', skor_anomali: 0.5901, is_anomali: true, level: 'Perlu Ditinjau', alasan: 'potongan bernilai negatif' },
      ],
    },
    forecast: {
      model: 'holt-linear',
      periode: ['2025-04', '2025-05', '2025-06'],
      nilai: [9821340000, 9898210000, 9975080000],
      seasonal_period: null,
      residual_std: 41200000,
    },
  },
}

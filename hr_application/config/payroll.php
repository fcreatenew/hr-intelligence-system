<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payroll Intelligence (opsional)
    |--------------------------------------------------------------------------
    | Bila `base_url` diisi, payroll run akan memakai endpoint
    | POST /anomalies/score untuk menilai anomali tiap baris payroll.
    | Bila kosong, perhitungan tetap berjalan tanpa skor anomali.
    */
    'base_url' => env('PAYROLL_INTELLIGENCE_BASE_URL'),
    'timeout' => (int) env('PAYROLL_INTELLIGENCE_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Aturan perhitungan payroll
    |--------------------------------------------------------------------------
    | `working_days_per_month` dipakai untuk tarif harian potongan alpha.
    | `default_overtime_rate` dipakai bila karyawan belum punya tarif lembur.
    */
    'working_days_per_month' => (int) env('PAYROLL_WORKING_DAYS_PER_MONTH', 22),
    'default_overtime_rate' => (float) env('PAYROLL_DEFAULT_OVERTIME_RATE', 0),
];

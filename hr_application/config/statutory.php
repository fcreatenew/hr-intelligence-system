<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laporan statutori (estimasi)
    |--------------------------------------------------------------------------
    | Tarif dipakai `StatutoryReportService` untuk menghasilkan draft laporan
    | BPJS/PPh21 dari data payroll. Nilai ini **estimasi** dan dapat disesuaikan
    | lewat env agar sesuai kebijakan/regulasi terbaru. Ubah bila tarif berubah.
    */
    'bpjs_kesehatan_employee_rate' => (float) env('BPJS_KES_EMPLOYEE_RATE', 0.01),
    'bpjs_kesehatan_employer_rate' => (float) env('BPJS_KES_EMPLOYER_RATE', 0.04),
    'bpjs_jht_employee_rate' => (float) env('BPJS_JHT_EMPLOYEE_RATE', 0.02),
    'bpjs_jht_employer_rate' => (float) env('BPJS_JHT_EMPLOYER_RATE', 0.037),
    'pph21_rate' => (float) env('PPH21_RATE', 0.05),
];

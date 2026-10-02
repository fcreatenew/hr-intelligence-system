<?php

return [
    'base_url' => env('HR_INTELLIGENCE_BASE_URL', 'http://localhost:8000'),
    'api_key' => env('HR_INTELLIGENCE_API_KEY'),

    'timeout' => (int) env('HR_INTELLIGENCE_TIMEOUT', 10),
    'retry_times' => (int) env('HR_INTELLIGENCE_RETRY_TIMES', 3),
    'retry_delay_ms' => (int) env('HR_INTELLIGENCE_RETRY_DELAY_MS', 300),

    'batch_chunk_size' => (int) env('HR_INTELLIGENCE_BATCH_CHUNK_SIZE', 500),

    'prediction_retention_days' => env('HR_INTELLIGENCE_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Use case model HR Intelligence API
    |--------------------------------------------------------------------------
    | Setiap use case punya endpoint prediksi tunggal & batch di API.
    | key = identifier yang dipakai di kode Laravel dan endpoint /model/<key>/info.
    */
    'use_cases' => [
        'turnover' => [
            'label' => 'Turnover karyawan',
            'path' => '/predict',
            'batch_path' => '/predict/batch',
        ],
        'absensi' => [
            'label' => 'Absensi tinggi',
            'path' => '/predict/absensi',
            'batch_path' => '/predict/absensi/batch',
        ],
        'kinerja' => [
            'label' => 'Kategori kinerja',
            'path' => '/predict/kinerja',
            'batch_path' => '/predict/kinerja/batch',
        ],
        'payroll' => [
            'label' => 'Anomali payroll',
            'path' => '/predict/payroll',
            'batch_path' => '/predict/payroll/batch',
        ],
        'survival' => [
            'label' => 'Survival attrition',
            'path' => '/predict/survival',
            'batch_path' => '/predict/survival/batch',
        ],
    ],
];

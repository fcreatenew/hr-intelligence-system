<?php

return [
    'base_url' => env('HR_INTELLIGENCE_BASE_URL', 'http://localhost:8000'),
    'api_key' => env('HR_INTELLIGENCE_API_KEY'),

    'timeout' => (int) env('HR_INTELLIGENCE_TIMEOUT', 10),
    'retry_times' => (int) env('HR_INTELLIGENCE_RETRY_TIMES', 3),
    'retry_delay_ms' => (int) env('HR_INTELLIGENCE_RETRY_DELAY_MS', 300),

    'batch_chunk_size' => (int) env('HR_INTELLIGENCE_BATCH_CHUNK_SIZE', 500),

    'prediction_retention_days' => env('HR_INTELLIGENCE_RETENTION_DAYS', 365),
];

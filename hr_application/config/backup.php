<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup database
    |--------------------------------------------------------------------------
    | Menentukan ke mana cadangan database ditulis dan berapa banyak yang
    | disimpan. Backup hanya dijalankan lewat command `db:backup`, tidak
    | otomatis mengirim data ke luar mesin.
    */
    'disk' => env('BACKUP_DISK', 'local'),

    'directory' => env('BACKUP_DIRECTORY', 'backups'),

    // Jumlah berkas cadangan terbaru yang dipertahankan; sisanya dihapus.
    'keep' => (int) env('BACKUP_KEEP', 14),

    // Batas waktu (detik) untuk mysqldump/pg_dump.
    'timeout' => (int) env('BACKUP_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Retensi data
    |--------------------------------------------------------------------------
    | Umur maksimum baris operasional. Prediksi ML sudah punya pengaturan
    | sendiri di `config/hr_intelligence.php`; audit log memakai nilai ini.
    */
    'audit_log_retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 730),

];

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hr-intelligence:predict-turnover')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping();

Schedule::command('hr-intelligence:predict', ['absensi'])
    ->weeklyOn(1, '03:00')
    ->withoutOverlapping();

Schedule::command('hr-intelligence:predict', ['payroll'])
    ->monthlyOn(1, '04:00')
    ->withoutOverlapping();

Schedule::command('hr-intelligence:predict', ['kinerja'])
    ->quarterly()
    ->withoutOverlapping();

Schedule::command('hr-intelligence:predict', ['survival'])
    ->monthlyOn(1, '05:00')
    ->withoutOverlapping();

// Backup harian + retensi data operasional. Backup hanya menulis ke disk
// aplikasi; pemindahan ke penyimpanan luar dilakukan terpisah (lihat
// BACKUP_DISK, mis. disk S3).
Schedule::command('db:backup')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('hr:prune')
    ->dailyAt('02:30')
    ->withoutOverlapping();

<?php

namespace App\Console\Commands;

use App\Jobs\RunTurnoverBatchPredictionJob;
use Illuminate\Console\Command;

class PredictTurnoverCommand extends Command
{
    protected $signature = 'hr-intelligence:predict-turnover
                            {--sync : Jalankan langsung tanpa lewat antrian (berguna untuk testing manual)}';

    protected $description = 'Jalankan prediksi risiko turnover untuk semua karyawan aktif lewat HR Intelligence API.';

    public function handle(): int
    {
        if ($this->option('sync')) {
            $this->info('Menjalankan prediksi turnover secara sinkron...');
            RunTurnoverBatchPredictionJob::dispatchSync();
        } else {
            $this->info('Mengantrikan job prediksi turnover...');
            RunTurnoverBatchPredictionJob::dispatch();
        }

        $this->info('Selesai. Cek log aplikasi untuk ringkasan hasil (sukses/gagal per chunk).');

        return self::SUCCESS;
    }
}

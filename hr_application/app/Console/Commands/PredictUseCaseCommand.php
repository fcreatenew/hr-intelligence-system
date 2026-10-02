<?php

namespace App\Console\Commands;

use App\Jobs\RunUseCaseBatchPredictionJob;
use App\Services\HrIntelligenceService;
use Illuminate\Console\Command;

class PredictUseCaseCommand extends Command
{
    protected $signature = 'hr-intelligence:predict
                            {use-case : turnover|absensi|kinerja|payroll|survival}
                            {--sync : Jalankan langsung tanpa lewat antrian}';

    protected $description = 'Jalankan prediksi batch HR Intelligence untuk use case tertentu lewat API.';

    public function handle(HrIntelligenceService $service): int
    {
        $useCase = (string) $this->argument('use-case');

        $tersedia = array_keys($service->useCases());

        if (! in_array($useCase, $tersedia, true)) {
            $this->error(sprintf(
                'Use case "%s" tidak dikenal. Pilihan: %s',
                $useCase,
                implode(', ', $tersedia),
            ));

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            $this->info("Menjalankan prediksi '{$useCase}' secara sinkron...");
            RunUseCaseBatchPredictionJob::dispatchSync($useCase);
        } else {
            $this->info("Mengantrikan job prediksi '{$useCase}'...");
            RunUseCaseBatchPredictionJob::dispatch($useCase);
        }

        $this->info('Selesai. Cek log aplikasi untuk ringkasan hasil (sukses/gagal per chunk).');

        return self::SUCCESS;
    }
}

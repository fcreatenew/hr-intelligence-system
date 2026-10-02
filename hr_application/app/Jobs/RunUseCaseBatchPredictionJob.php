<?php

namespace App\Jobs;

use App\Contracts\EmployeeFeatureMapper;
use App\Exceptions\HrIntelligenceException;
use App\Models\Employee;
use App\Models\HrIntelligencePrediction;
use App\Services\HrIntelligenceResultNormalizer;
use App\Services\HrIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Job generik untuk semua use case prediksi HR Intelligence.
 * Turnover tetap memakai RunTurnoverBatchPredictionJob (kompatibilitas).
 */
class RunUseCaseBatchPredictionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $useCase,
    ) {
    }

    public function handle(
        HrIntelligenceService $service,
        EmployeeFeatureMapper $mapper,
        HrIntelligenceResultNormalizer $normalizer,
    ): void {
        $startedAt = now();
        $totalSukses = 0;
        $totalGagal = 0;
        $chunkGagal = [];

        $batchPath = $service->useCases()[$this->useCase]['batch_path'] ?? null;

        if ($batchPath === null) {
            Log::error('Use case HR Intelligence tidak dikenal.', ['use_case' => $this->useCase]);

            return;
        }

        Employee::where('status', true)
            ->chunkById($service->maxBatchSize(), function (Collection $employees) use (
                $service, $mapper, $normalizer, &$totalSukses, &$totalGagal, &$chunkGagal, $startedAt
            ) {
                $payload = $mapper->mapManyForUseCase($this->useCase, $employees);

                try {
                    $hasil = $service->predictUseCaseBatch($this->useCase, $payload);
                    $this->simpanHasil(
                        $hasil['hasil'],
                        $hasil['model_version'] ?? 'unknown',
                        $normalizer,
                        $startedAt,
                    );
                    $totalSukses += count($hasil['hasil']);
                } catch (HrIntelligenceException $e) {
                    $employeeIds = collect($payload)->pluck('employee_id')->all();
                    $totalGagal += count($employeeIds);
                    $chunkGagal[] = [
                        'employee_ids' => $employeeIds,
                        'error_class' => get_class($e),
                        'error_message' => $e->getMessage(),
                    ];

                    Log::error('Satu chunk prediksi gagal, lanjut ke chunk berikutnya.', [
                        'use_case' => $this->useCase,
                        'jumlah_karyawan_di_chunk' => count($employeeIds),
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        Log::info('Batch prediksi use case selesai.', [
            'use_case' => $this->useCase,
            'total_sukses' => $totalSukses,
            'total_gagal' => $totalGagal,
            'jumlah_chunk_gagal' => count($chunkGagal),
            'durasi_detik' => now()->diffInSeconds($startedAt, true),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $hasil
     */
    private function simpanHasil(
        array $hasil,
        string $modelVersion,
        HrIntelligenceResultNormalizer $normalizer,
        \DateTimeInterface $predictedAt,
    ): void {
        $rows = array_map(function (array $r) use ($modelVersion, $normalizer, $predictedAt) {
            $normal = $normalizer->normalize($this->useCase, $r);

            return [
                'use_case' => $this->useCase,
                'employee_id' => $normal['employee_id'],
                'skor' => $normal['skor'],
                'label' => $normal['label'],
                'is_flag' => $normal['is_flag'],
                'detail' => json_encode($normal['detail']),
                'model_version' => $modelVersion,
                'predicted_at' => $predictedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $hasil);

        HrIntelligencePrediction::insert($rows);
    }
}

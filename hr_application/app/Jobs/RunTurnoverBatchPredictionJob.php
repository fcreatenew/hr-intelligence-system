<?php

namespace App\Jobs;

use App\Contracts\EmployeeFeatureMapper;
use App\Exceptions\HrIntelligenceException;
use App\Models\Employee; 
use App\Models\TurnoverPrediction;
use App\Services\HrIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RunTurnoverBatchPredictionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(HrIntelligenceService $service, EmployeeFeatureMapper $mapper): void
    {
        $startedAt = now();
        $totalSukses = 0;
        $totalGagal = 0;
        $chunkGagal = [];

        Employee::where('status', true)
            ->chunkById($service->maxBatchSize(), function (Collection $employees) use (
                $service, $mapper, &$totalSukses, &$totalGagal, &$chunkGagal, $startedAt
            ) {
                $payload = $mapper->mapMany($employees);

                try {
                    $hasil = $service->predictBatch($payload);
                    $this->simpanHasil($hasil['hasil'], $hasil['model_version'], $hasil['threshold'], $startedAt);
                    $totalSukses += count($hasil['hasil']);
                } catch (HrIntelligenceException $e) {
                    $employeeIds = collect($payload)->pluck('employee_id')->all();
                    $totalGagal += count($employeeIds);
                    $chunkGagal[] = [
                        'employee_ids' => $employeeIds,
                        'error_class' => get_class($e),
                        'error_message' => $e->getMessage(),
                    ];

                    Log::error('Satu chunk prediksi turnover gagal, lanjut ke chunk berikutnya.', [
                        'jumlah_karyawan_di_chunk' => count($employeeIds),
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        Log::info('Batch prediksi turnover selesai.', [
            'total_sukses' => $totalSukses,
            'total_gagal' => $totalGagal,
            'jumlah_chunk_gagal' => count($chunkGagal),
            'durasi_detik' => now()->diffInSeconds($startedAt, true),
        ]);

    }

    /**
     * @param  array<int, array<string, mixed>>  $hasil
     */
    private function simpanHasil(array $hasil, string $modelVersion, float $threshold, \DateTimeInterface $predictedAt): void
    {
        $rows = array_map(fn (array $r) => [
            'employee_id' => $r['employee_id'],
            'probabilitas_keluar' => $r['probabilitas_keluar'],
            'prediksi_keluar' => (bool) $r['prediksi_keluar'],
            'level_risiko' => $r['level_risiko'],
            'model_version' => $modelVersion,
            'threshold' => $threshold,
            'predicted_at' => $predictedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ], $hasil);

        TurnoverPrediction::insert($rows);
    }
}

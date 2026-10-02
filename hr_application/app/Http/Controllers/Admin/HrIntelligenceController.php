<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunUseCaseBatchPredictionJob;
use App\Models\HrIntelligencePrediction;
use App\Services\HrIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrIntelligenceController extends Controller
{
    public function __construct(
        private readonly HrIntelligenceService $service,
    ) {
    }

    /** Daftar use case beserta metadata modelnya dari API. */
    public function useCases(): JsonResponse
    {
        $data = [];

        foreach ($this->service->useCases() as $key => $config) {
            $info = null;

            try {
                $info = $this->service->useCaseInfo($key);
            } catch (\Throwable $e) {
                // Metadata bersifat informatif; kegagalan tidak boleh memblokir daftar.
            }

            $data[] = [
                'use_case' => $key,
                'label' => $config['label'],
                'predict_path' => $config['path'],
                'model' => $info['model'] ?? null,
                'versi_model' => $info['versi_model'] ?? null,
                'metrik' => $info['metrik'] ?? [],
            ];
        }

        return response()->json(['data' => $data]);
    }

    /** Jalankan prediksi batch untuk satu use case. */
    public function trigger(Request $request, string $useCase): JsonResponse
    {
        if (! array_key_exists($useCase, $this->service->useCases())) {
            return response()->json([
                'message' => "Use case '{$useCase}' tidak dikenal.",
            ], 404);
        }

        RunUseCaseBatchPredictionJob::dispatch($useCase);

        return response()->json([
            'message' => "Job prediksi '{$useCase}' telah diantrikan.",
        ], 202);
    }

    /** Hasil prediksi terbaru untuk satu use case. */
    public function index(string $useCase): JsonResponse
    {
        $predictions = HrIntelligencePrediction::query()
            ->forUseCase($useCase)
            ->latestSnapshot()
            ->orderByDesc('skor')
            ->get(['employee_id', 'skor', 'label', 'is_flag', 'model_version', 'predicted_at']);

        return response()->json(['data' => $predictions]);
    }
}

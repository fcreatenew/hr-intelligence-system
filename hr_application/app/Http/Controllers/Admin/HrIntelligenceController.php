<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunUseCaseBatchPredictionJob;
use App\Models\HrIntelligencePrediction;
use App\Services\HrIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
    public function trigger(Request $request, string $useCase): JsonResponse|RedirectResponse
    {
        if (! array_key_exists($useCase, $this->service->useCases())) {
            return response()->json([
                'message' => "Use case '{$useCase}' tidak dikenal.",
            ], 404);
        }

        RunUseCaseBatchPredictionJob::dispatch($useCase);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('hr-intelligence.use-case.predictions', $useCase)
                ->with('status', "Job prediksi '{$useCase}' telah diantrikan.");
        }

        return response()->json([
            'message' => "Job prediksi '{$useCase}' telah diantrikan.",
        ], 202);
    }

    /** Hasil prediksi terbaru untuk satu use case. */
    public function index(string $useCase): JsonResponse
    {
        return response()->json(['data' => $this->predictions($useCase)]);
    }

    /** Halaman HTML: daftar prediksi terbaru per use case (Fase 5). */
    public function showUseCases(): View
    {
        return view('hr-intelligence.use-cases', [
            'useCases' => $this->useCaseSummaries(),
        ]);
    }

    /** Halaman HTML: hasil prediksi terbaru satu use case. */
    public function showPredictions(string $useCase): View
    {
        $config = $this->service->useCases()[$useCase] ?? null;
        abort_if($config === null, 404);

        return view('hr-intelligence.predictions', [
            'useCase' => $useCase,
            'label' => $config['label'],
            'predictions' => $this->predictions($useCase),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, HrIntelligencePrediction> */
    private function predictions(string $useCase): \Illuminate\Support\Collection
    {
        return HrIntelligencePrediction::query()
            ->forUseCase($useCase)
            ->latestSnapshot()
            ->orderByDesc('skor')
            ->get(['employee_id', 'skor', 'label', 'is_flag', 'model_version', 'predicted_at']);
    }

    /**
     * Ringkasan per use case untuk halaman daftar: label, versi model, dan
     * jumlah hasil terbaru. Kegagalan metadata API tidak memblokir halaman.
     *
     * @return array<int, array<string, mixed>>
     */
    private function useCaseSummaries(): array
    {
        $summaries = [];

        foreach ($this->service->useCases() as $key => $config) {
            $versiModel = null;
            $metrik = [];

            try {
                $info = $this->service->useCaseInfo($key);
                $versiModel = $info['versi_model'] ?? null;
                $metrik = $info['metrik'] ?? [];
            } catch (\Throwable $e) {
                // Metadata bersifat informatif; kegagalan tidak boleh memblokir daftar.
            }

            $summaries[] = [
                'key' => $key,
                'label' => $config['label'],
                'versi_model' => $versiModel,
                'metrik' => $metrik,
                'total' => HrIntelligencePrediction::query()->forUseCase($key)->latestSnapshot()->count(),
                'flagged' => HrIntelligencePrediction::query()->forUseCase($key)->latestSnapshot()->where('is_flag', true)->count(),
                'terakhir' => HrIntelligencePrediction::query()->forUseCase($key)->max('predicted_at'),
            ];
        }

        return $summaries;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunTurnoverBatchPredictionJob;
use App\Models\TurnoverPrediction;
use Illuminate\Http\JsonResponse;

class TurnoverPredictionController extends Controller
{
    public function trigger(): JsonResponse
    {
        RunTurnoverBatchPredictionJob::dispatch();

        return response()->json([
            'message' => 'Job prediksi turnover telah diantrikan.',
        ], 202);
    }

    public function index(): JsonResponse
    {
        $predictions = TurnoverPrediction::query()
            ->latestSnapshot()
            ->orderByDesc('probabilitas_keluar')
            ->get(['employee_id', 'probabilitas_keluar', 'prediksi_keluar', 'level_risiko', 'predicted_at']);

        return response()->json(['data' => $predictions]);
    }
}

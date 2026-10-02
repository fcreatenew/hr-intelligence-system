<?php

use App\Http\Controllers\Admin\HrIntelligenceController;
use App\Http\Controllers\Admin\TurnoverPredictionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-hr-intelligence', 'throttle:admin-api'])
    ->prefix('admin/hr-intelligence')
    ->name('hr-intelligence.')
    ->group(function () {
        // Turnover (kompatibel dengan route lama).
        Route::post('/run', [TurnoverPredictionController::class, 'trigger'])->name('run');
        Route::get('/predictions', [TurnoverPredictionController::class, 'index'])->name('predictions');

        // Use case lain: absensi, kinerja, payroll, survival.
        Route::get('/use-cases', [HrIntelligenceController::class, 'useCases'])->name('use-cases');
        Route::post('/{useCase}/run', [HrIntelligenceController::class, 'trigger'])
            ->whereIn('useCase', ['absensi', 'kinerja', 'payroll', 'survival'])
            ->name('use-case.run');
        Route::get('/{useCase}/predictions', [HrIntelligenceController::class, 'index'])
            ->whereIn('useCase', ['absensi', 'kinerja', 'payroll', 'survival'])
            ->name('use-case.predictions');

        // Halaman HTML (Fase 5) — dipakai saat permintaan bukan JSON.
        Route::get('/', [HrIntelligenceController::class, 'showUseCases'])->name('dashboard');
        Route::get('/{useCase}', [HrIntelligenceController::class, 'showPredictions'])
            ->whereIn('useCase', ['absensi', 'kinerja', 'payroll', 'survival'])
            ->name('use-case.show');
    });

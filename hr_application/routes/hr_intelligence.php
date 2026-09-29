<?php

use App\Http\Controllers\Admin\TurnoverPredictionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-hr-intelligence'])
    ->prefix('admin/hr-intelligence')
    ->name('hr-intelligence.')
    ->group(function () {
        Route::post('/run', [TurnoverPredictionController::class, 'trigger'])->name('run');
        Route::get('/predictions', [TurnoverPredictionController::class, 'index'])->name('predictions');
    });

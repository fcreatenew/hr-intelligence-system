<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TurnoverPrediction extends Model
{
    protected $fillable = [
        'employee_id',
        'probabilitas_keluar',
        'prediksi_keluar',
        'level_risiko',
        'model_version',
        'threshold',
        'predicted_at',
    ];

    protected $casts = [
        'probabilitas_keluar' => 'float',
        'prediksi_keluar' => 'boolean',
        'threshold' => 'float',
        'predicted_at' => 'datetime',
    ];

    public static function latestForEmployee(string $employeeId): ?self
    {
        return static::where('employee_id', $employeeId)
            ->orderByDesc('predicted_at')
            ->first();
    }

    public function scopeLatestSnapshot(Builder $query): Builder
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('turnover_predictions')
                ->groupBy('employee_id');
        });
    }
}

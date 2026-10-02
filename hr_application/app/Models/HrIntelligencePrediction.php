<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HrIntelligencePrediction extends Model
{
    protected $fillable = [
        'use_case',
        'employee_id',
        'skor',
        'label',
        'is_flag',
        'detail',
        'model_version',
        'predicted_at',
    ];

    protected $casts = [
        'skor' => 'float',
        'is_flag' => 'boolean',
        'detail' => 'array',
        'predicted_at' => 'datetime',
    ];

    public function scopeForUseCase(Builder $query, string $useCase): Builder
    {
        return $query->where('use_case', $useCase);
    }

    public function scopeLatestSnapshot(Builder $query): Builder
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('hr_intelligence_predictions')
                ->groupBy('use_case', 'employee_id');
        });
    }
}

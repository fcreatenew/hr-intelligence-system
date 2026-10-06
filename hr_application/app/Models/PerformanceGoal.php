<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sasaran kinerja (goal/OKR) karyawan per periode.
 */
class PerformanceGoal extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'berjalan';

    public const STATUS_DONE = 'selesai';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'employee_id',
        'period',
        'title',
        'description',
        'weight',
        'target',
        'achievement',
        'status',
    ];

    protected $casts = [
        'weight' => 'integer',
        'target' => 'float',
        'achievement' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** Capaian 0..1+ dari target, atau null bila target belum diisi/nol. */
    public function progress(): ?float
    {
        if ($this->target === null || (float) $this->target === 0.0 || $this->achievement === null) {
            return null;
        }

        return round((float) $this->achievement / (float) $this->target, 4);
    }
}

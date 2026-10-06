<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Katalog pelatihan. Status mengikuti siklus: `draft` → `berjalan` → `selesai`,
 * atau `dibatalkan`. Hanya pelatihan `berjalan`/`selesai` yang dihitung sebagai
 * partisipasi karyawan.
 */
class Training extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const CATEGORIES = [
        'teknis',
        'kepemimpinan',
        'keselamatan',
        'kepatuhan',
        'soft_skill',
        'lainnya',
    ];

    public const MODES = ['online', 'offline', 'hybrid'];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_RUNNING = 'berjalan';

    public const STATUS_DONE = 'selesai';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_RUNNING,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'title',
        'category',
        'provider',
        'mode',
        'start_date',
        'end_date',
        'duration_hours',
        'cost',
        'quota',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'float',
        'duration_hours' => 'integer',
        'quota' => 'integer',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_RUNNING])
            ->where('start_date', '>=', now()->toDateString());
    }

    /** Pelatihan yang dihitung sebagai partisipasi (bukan draft/batal). */
    public function scopeCountable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_RUNNING, self::STATUS_DONE]);
    }

    public function isFull(): bool
    {
        if ($this->quota === null) {
            return false;
        }

        return $this->participants()
            ->where('status', '!=', TrainingParticipant::STATUS_CANCELLED)
            ->count() >= $this->quota;
    }
}

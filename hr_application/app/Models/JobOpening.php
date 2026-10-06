<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lowongan kerja (Recruitment / ATS). Status mengikuti siklus:
 * `draft` → `dibuka` → `ditutup`, atau `dibatalkan`.
 */
class JobOpening extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'dibuka';

    public const STATUS_CLOSED = 'ditutup';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public const EMPLOYMENT_TYPES = ['tetap', 'kontrak', 'magang', 'freelance'];

    protected $fillable = [
        'title',
        'department',
        'employment_type',
        'location',
        'description',
        'requirements',
        'quota',
        'status',
        'opened_at',
        'closed_at',
        'created_by',
    ];

    protected $casts = [
        'opened_at' => 'date',
        'closed_at' => 'date',
        'quota' => 'integer',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /** Jumlah kandidat yang sudah diterima untuk lowongan ini. */
    public function hiredCount(): int
    {
        return $this->candidates()->where('stage', Candidate::STAGE_HIRED)->count();
    }

    public function isFull(): bool
    {
        return $this->quota !== null && $this->hiredCount() >= $this->quota;
    }
}

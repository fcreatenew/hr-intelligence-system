<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kandidat pada sebuah lowongan beserta tahap seleksinya.
 */
class Candidate extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STAGE_APPLIED = 'melamar';

    public const STAGE_SCREENING = 'screening';

    public const STAGE_INTERVIEW = 'interview';

    public const STAGE_OFFER = 'offer';

    public const STAGE_HIRED = 'diterima';

    public const STAGE_REJECTED = 'ditolak';

    public const STAGE_WITHDRAWN = 'mengundurkan_diri';

    public const STAGES = [
        self::STAGE_APPLIED,
        self::STAGE_SCREENING,
        self::STAGE_INTERVIEW,
        self::STAGE_OFFER,
        self::STAGE_HIRED,
        self::STAGE_REJECTED,
        self::STAGE_WITHDRAWN,
    ];

    /** Tahap akhir: kandidat tidak lagi diproses. */
    public const FINAL_STAGES = [
        self::STAGE_HIRED,
        self::STAGE_REJECTED,
        self::STAGE_WITHDRAWN,
    ];

    protected $fillable = [
        'job_opening_id',
        'name',
        'email',
        'phone',
        'source',
        'stage',
        'expected_salary',
        'notes',
        'applied_at',
    ];

    protected $casts = [
        'expected_salary' => 'float',
        'applied_at' => 'date',
    ];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('stage', self::FINAL_STAGES);
    }

    public function isFinal(): bool
    {
        return in_array($this->stage, self::FINAL_STAGES, true);
    }
}

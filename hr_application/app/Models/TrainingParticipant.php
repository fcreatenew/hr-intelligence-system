<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pendaftaran karyawan pada sebuah pelatihan beserta hasilnya (skor,
 * sertifikasi). Satu karyawan hanya boleh terdaftar sekali per pelatihan
 * (dijaga unique index di database).
 */
class TrainingParticipant extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STATUS_REGISTERED = 'terdaftar';

    public const STATUS_COMPLETED = 'selesai';

    public const STATUS_FAILED = 'gagal';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_REGISTERED,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'training_id',
        'employee_id',
        'status',
        'score',
        'certified',
        'certificate_number',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'score' => 'integer',
        'certified' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Partisipasi yang dihitung untuk fitur ML (selesai/gagal tetap dihitung). */
    public function scopeCountable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    public function isDecided(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen karyawan (kontrak, sertifikat, identitas) beserta masa berlaku.
 * Status `kedaluwarsa` dapat ditandai otomatis lewat scope/atribut.
 */
class EmployeeDocument extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const CATEGORIES = ['kontrak', 'sertifikat', 'identitas', 'lainnya'];

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_EXPIRED = 'kedaluwarsa';

    public const STATUS_ARCHIVED = 'arsip';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'employee_id',
        'title',
        'category',
        'file_path',
        'issued_at',
        'expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** True bila dokumen kedaluwarsa ≤ 30 hari lagi (perlu diperbarui). */
    public function expiresSoon(int $days = 30): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isFuture()
            && abs($this->expires_at->diffInDays(now())) <= $days;
    }
}

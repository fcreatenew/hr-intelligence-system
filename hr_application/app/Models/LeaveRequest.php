<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const TYPES = ['tahunan', 'sakit', 'melahirkan', 'menikah', 'duka', 'lainnya'];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Jenis cuti yang memotong saldo cuti tahunan. Cuti di luar daftar ini
     * (mis. melahirkan, duka) tidak mengurangi jatah tahunan.
     */
    public const BALANCE_TYPES = ['tahunan'];

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decided_at' => 'datetime',
        'days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Jumlah hari kalender, inklusif. Dihitung di server agar tidak bergantung
     * pada input pengguna dan tetap konsisten meski form diubah.
     */
    public static function countDays(string $start, string $end): int
    {
        $start = CarbonImmutable::parse($start)->startOfDay();
        $end = CarbonImmutable::parse($end)->startOfDay();

        return $end->lessThan($start) ? 0 : (int) $start->diffInDays($end) + 1;
    }

    public function consumesBalance(): bool
    {
        return in_array($this->type, self::BALANCE_TYPES, true);
    }
}

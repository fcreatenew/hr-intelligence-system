<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;
    use RecordsAudit;

    /** Jatah cuti tahunan default per tahun. */
    public const DEFAULT_ENTITLED_DAYS = 12;

    /**
     * Jatah default per jenis cuti (hari/tahun). Cuti yang tidak terdaftar di
     * sini memakai `DEFAULT_ENTITLED_DAYS`.
     */
    public const DEFAULT_ENTITLEMENTS = [
        'tahunan' => 12,
        'sakit' => 30,
        'melahirkan' => 90,
        'menikah' => 3,
        'duka' => 3,
        'lainnya' => 0,
    ];

    protected $fillable = [
        'employee_id',
        'year',
        'type',
        'entitled_days',
        'used_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'integer',
        'used_days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, $this->entitled_days - $this->used_days);
    }

    public static function defaultEntitledDays(string $type): int
    {
        return self::DEFAULT_ENTITLEMENTS[$type] ?? self::DEFAULT_ENTITLED_DAYS;
    }

    /** Saldo default bila karyawan belum punya baris saldo jenis ini. */
    public static function defaultFor(Employee $employee, int $year, string $type = 'tahunan'): self
    {
        return new self([
            'employee_id' => $employee->id,
            'year' => $year,
            'type' => $type,
            'entitled_days' => self::defaultEntitledDays($type),
            'used_days' => 0,
        ]);
    }
}

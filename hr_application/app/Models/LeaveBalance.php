<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    /** Jatah cuti tahunan default per tahun. */
    public const DEFAULT_ENTITLED_DAYS = 12;

    protected $fillable = [
        'employee_id',
        'year',
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

    /** Saldo default bila karyawan belum punya baris saldo di tahun tersebut. */
    public static function defaultFor(Employee $employee, int $year): self
    {
        return new self([
            'employee_id' => $employee->id,
            'year' => $year,
            'entitled_days' => self::DEFAULT_ENTITLED_DAYS,
            'used_days' => 0,
        ]);
    }
}

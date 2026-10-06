<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Komponen kompensasi karyawan di luar gaji pokok: tunjangan, potongan,
 * BPJS, THR, bonus, atau PPh21. Menyambung ke analitik payroll.
 */
class CompensationComponent extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const TYPE_ALLOWANCE = 'tunjangan';

    public const TYPE_DEDUCTION = 'potongan';

    public const TYPE_BPJS = 'bpjs';

    public const TYPE_THR = 'thr';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_TAX = 'pph21';

    public const TYPES = [
        self::TYPE_ALLOWANCE,
        self::TYPE_DEDUCTION,
        self::TYPE_BPJS,
        self::TYPE_THR,
        self::TYPE_BONUS,
        self::TYPE_TAX,
    ];

    protected $fillable = [
        'employee_id',
        'name',
        'type',
        'amount',
        'recurring',
        'effective_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'recurring' => 'boolean',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Komponen pengurang penghasilan (potongan/PPh21). */
    public function isDeduction(): bool
    {
        return in_array($this->type, [self::TYPE_DEDUCTION, self::TYPE_TAX], true);
    }
}

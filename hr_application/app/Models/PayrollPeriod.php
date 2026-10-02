<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'periode',
        'status',
        'employee_count',
        'total_amount',
        'notes',
        'calculated_at',
        'finalized_at',
        'calculated_by',
        'finalized_by',
    ];

    protected $casts = [
        'employee_count' => 'integer',
        'total_amount' => 'float',
        'calculated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_CALCULATED, self::STATUS_FINAL];
    }

    /** Periode payroll dengan format YYYY-MM. */
    public static function isValidPeriod(string $periode): bool
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode);
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function isCalculated(): bool
    {
        return in_array($this->status, [self::STATUS_CALCULATED, self::STATUS_FINAL], true);
    }
}

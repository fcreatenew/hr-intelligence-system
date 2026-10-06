<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan kepatuhan/statutori (BPJS, PPh21) per periode. Payload berisi
 * ringkasan yang dihasilkan dari data payroll saat laporan dibuat.
 */
class StatutoryReport extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const TYPE_BPJS_KESEHATAN = 'bpjs_kesehatan';

    public const TYPE_BPJS_KETENAGAKERJAAN = 'bpjs_ketenagakerjaan';

    public const TYPE_PPH21 = 'pph21';

    public const TYPES = [
        self::TYPE_BPJS_KESEHATAN,
        self::TYPE_BPJS_KETENAGAKERJAAN,
        self::TYPE_PPH21,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_FINAL,
    ];

    protected $fillable = [
        'type',
        'period',
        'status',
        'payload',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'generated_at' => 'datetime',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::TYPE_BPJS_KESEHATAN => 'BPJS Kesehatan',
            self::TYPE_BPJS_KETENAGAKERJAAN => 'BPJS Ketenagakerjaan',
            self::TYPE_PPH21 => 'PPh 21',
            default => $type,
        };
    }
}

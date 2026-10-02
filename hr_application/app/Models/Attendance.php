<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_HADIR = 'hadir';

    public const STATUS_TERLAMBAT = 'terlambat';

    public const STATUS_IZIN = 'izin';

    public const STATUS_SAKIT = 'sakit';

    public const STATUS_CUTI = 'cuti';

    public const STATUS_ALPHA = 'alpha';

    /**
     * Status yang dihitung sebagai "tidak hadir" untuk fitur model.
     * `cuti` tidak dihitung karena direncanakan dan sudah lewat persetujuan.
     */
    public const ABSENCE_STATUSES = [
        self::STATUS_IZIN,
        self::STATUS_SAKIT,
        self::STATUS_ALPHA,
    ];

    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'check_in',
        'check_out',
        'late_minutes',
        'overtime_minutes',
        'note',
    ];

    protected $casts = [
        'late_minutes' => 'integer',
        'overtime_minutes' => 'integer',
    ];

    /**
     * `date` sengaja tidak memakai cast `date` bawaan. Cast itu menyimpan nilai
     * sebagai `Y-m-d H:i:s`, sehingga kolom tanggal berisi jam 00:00:00 dan
     * pencocokan `where('date', 'Y-m-d')` serta updateOrCreate gagal cocok.
     * Accessor ini menjaga tipe Carbon untuk pembacaan, sementara penyimpanan
     * tetap `Y-m-d` murni.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->toDateString(),
        );
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_HADIR,
            self::STATUS_TERLAMBAT,
            self::STATUS_IZIN,
            self::STATUS_SAKIT,
            self::STATUS_CUTI,
            self::STATUS_ALPHA,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Hari tidak hadir (izin/sakit/alpha), dipakai untuk fitur model. */
    public function scopeAbsence(Builder $query): Builder
    {
        return $query->whereIn('status', self::ABSENCE_STATUSES);
    }

    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date', [
            Carbon::parse($start)->startOfDay()->toDateString(),
            Carbon::parse($end)->endOfDay()->toDateString(),
        ]);
    }
}

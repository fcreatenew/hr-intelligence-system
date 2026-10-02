<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hari libur nasional / cuti bersama. Dipakai untuk menghitung hari kerja
 * pada pengajuan cuti agar akhir pekan dan libur tidak memotong saldo.
 */
class Holiday extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'name', 'is_national'];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
    ];

    /**
     * Kumpulan tanggal libur dalam rentang (inklusif) sebagai string Y-m-d.
     * Sengaja tidak di-cache agar perubahan data libur langsung terpakai.
     *
     * @return array<int, string>
     */
    public static function between(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return static::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->all();
    }
}

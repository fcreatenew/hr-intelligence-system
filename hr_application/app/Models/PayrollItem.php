<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory;
    use RecordsAudit;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'periode',
        'departemen',
        'level',
        'gaji_pokok',
        'tunjangan',
        'lembur_jam',
        'tarif_lembur',
        'biaya_lembur',
        'potongan',
        'total_gaji',
        'skor_anomali',
        'level_anomali',
        'alasan_anomali',
    ];

    protected $casts = [
        'gaji_pokok' => 'integer',
        'tunjangan' => 'float',
        'lembur_jam' => 'integer',
        'tarif_lembur' => 'float',
        'biaya_lembur' => 'float',
        'potongan' => 'float',
        'total_gaji' => 'float',
        'skor_anomali' => 'float',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

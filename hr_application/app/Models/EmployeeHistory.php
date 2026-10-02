<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Linimasa terstruktur perubahan penting karyawan: mutasi departemen,
 * perubahan jabatan/gaji, perubahan status, dan pemutusan hubungan kerja.
 *
 * Berbeda dengan `AuditLog` yang mencatat setiap perubahan atribut secara
 * generik, tabel ini menyajikan riwayat yang sudah diberi makna (event) agar
 * dapat dibaca sebagai linimasa per karyawan.
 */
class EmployeeHistory extends Model
{
    use HasFactory;

    public const EVENT_DEPARTMENT = 'department_change';

    public const EVENT_POSITION = 'position_change';

    public const EVENT_SALARY = 'salary_change';

    public const EVENT_STATUS = 'status_change';

    public const EVENT_TERMINATED = 'terminated';

    protected $fillable = [
        'employee_id',
        'event',
        'field',
        'old_value',
        'new_value',
        'note',
        'changed_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function label(): string
    {
        return match ($this->event) {
            self::EVENT_DEPARTMENT => 'Mutasi departemen',
            self::EVENT_POSITION => 'Perubahan jabatan',
            self::EVENT_SALARY => 'Perubahan gaji',
            self::EVENT_STATUS => 'Perubahan status',
            self::EVENT_TERMINATED => 'Berhenti kerja',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}

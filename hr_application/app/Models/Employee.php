<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Employee extends Model
{
    use HasFactory;
    use RecordsAudit;

    /** Alasan keluar karyawan (dipakai saat menghentikan karyawan). */
    public const TERMINATION_REASONS = [
        'Resign',
        'Kontrak berakhir',
        'Pensiun',
        'PHK',
        'Meninggal dunia',
        'Lainnya',
    ];

    protected $fillable = [
        'nik',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'join_date',
        'termination_date',
        'termination_reason',
        'status',
        'tanggal_lahir',
        'tanggal_promosi',
        'status_kerja',
        'pendidikan',
        'gaji_bulanan',
        'jam_lembur_bulanan',
        'jarak_km',
        'beban_kerja',
        'kepuasan_kerja',
        'riwayat_absen_90hari',
        'jumlah_training_setahun',
        'proyek_selesai',
        'skor_umpan_balik_360',
        'hari_absen_setahun',
        'bulan_sejak_promosi',
        'periode',
        'level',
        'gaji_pokok',
        'tunjangan',
        'lembur_jam',
        'tarif_lembur',
        'potongan',
        'total_gaji',
    ];

    protected $casts = [
        'join_date' => 'date',
        'termination_date' => 'date',
        'tanggal_lahir' => 'date',
        'tanggal_promosi' => 'date',
        'status' => 'boolean',
        'jarak_km' => 'float',
        'beban_kerja' => 'float',
        'kepuasan_kerja' => 'float',
        'skor_umpan_balik_360' => 'float',
        'bulan_sejak_promosi' => 'float',
        'tunjangan' => 'float',
        'tarif_lembur' => 'float',
        'potongan' => 'float',
        'total_gaji' => 'float',
    ];

    /** Usia (tahun) dari tanggal_lahir, atau null bila belum diisi. */
    public function getUsiaAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    /** Lama bekerja (bulan) dihitung dari join_date sampai sekarang. */
    public function getMasaKerjaBulanAttribute(): ?int
    {
        return $this->join_date
            ? (int) $this->join_date->diffInMonths(now())
            : null;
    }

    /** Sudah berhenti (punya tanggal keluar) atau masih aktif bekerja. */
    public function getHasTerminatedAttribute(): bool
    {
        return $this->termination_date !== null;
    }

    /**
     * Hasil prediksi ML untuk karyawan ini. Relasi memakai `nik` karena tabel
     * prediksi menyimpan `employee_id` berupa NIK, bukan primary key.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(HrIntelligencePrediction::class, 'employee_id', 'nik');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class)->latest();
    }

    /**
     * Catat perubahan bermakna menjadi linimasa terstruktur. Dipanggil dari
     * controller setiap kali karyawan diubah/dihentikan.
     */
    public function recordHistory(string $event, ?string $field = null, mixed $old = null, mixed $new = null, ?string $note = null): EmployeeHistory
    {
        return $this->histories()->create([
            'event' => $event,
            'field' => $field,
            'old_value' => $old === null ? null : (string) $old,
            'new_value' => $new === null ? null : (string) $new,
            'note' => $note,
            'changed_by' => Auth::id(),
        ]);
    }

    /**
     * Saldo cuti tahun berjalan, membuat baris default bila belum ada sehingga
     * pemanggil tidak perlu menangani null.
     */
    public function leaveBalanceFor(?int $year = null): LeaveBalance
    {
        $year = $year ?? (int) now()->year;

        return $this->leaveBalances()->firstOrCreate(
            ['year' => $year],
            ['entitled_days' => LeaveBalance::DEFAULT_ENTITLED_DAYS, 'used_days' => 0],
        );
    }
}

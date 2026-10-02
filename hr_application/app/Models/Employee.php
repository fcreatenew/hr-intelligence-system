<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'join_date',
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

    /**
     * Hasil prediksi ML untuk karyawan ini. Relasi memakai `nik` karena tabel
     * prediksi menyimpan `employee_id` berupa NIK, bukan primary key.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(HrIntelligencePrediction::class, 'employee_id', 'nik');
    }
}

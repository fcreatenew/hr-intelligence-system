<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TrainingParticipant;

/**
 * Menyinkronkan jumlah pelatihan karyawan ke kolom fitur ML
 * `employees.jumlah_training_setahun`.
 *
 * Kolom itu dipakai model turnover/kinerja. Sebelumnya diisi manual di form
 * karyawan; sejak modul Training ada, nilainya dihitung dari partisipasi nyata
 * (pelatihan berjalan/selesai pada tahun berjalan) agar tidak lagi diisi
 * manual dan selalu konsisten dengan data pelatihan.
 */
class TrainingService
{
    /**
     * Hitung ulang jumlah pelatihan karyawan pada tahun tertentu.
     *
     * `status` partisipan yang dihitung: selesai & gagal (keduanya berarti
     * karyawan benar-benar mengikuti pelatihan). Partisipan `terdaftar` belum
     * dihitung, `dibatalkan` tidak.
     */
    public function syncEmployeeTrainingCount(Employee $employee, ?int $year = null): int
    {
        $year ??= (int) now()->year;

        $jumlah = TrainingParticipant::query()
            ->countable()
            ->where('employee_id', $employee->id)
            ->whereHas('training', function ($query) use ($year) {
                $query->countable()->whereYear('start_date', $year);
            })
            ->count();

        $employee->forceFill(['jumlah_training_setahun' => $jumlah])->save();

        return $jumlah;
    }
}

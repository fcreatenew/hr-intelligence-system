<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Membuat riwayat absensi contoh untuk 90 hari terakhir.
 *
 * Ini penting karena fitur model `riwayat_absen_90hari` dan
 * `hari_absen_setahun` kini dihitung dari tabel `attendances`, bukan lagi
 * diisi manual — tanpa data ini kedua fitur akan selalu bernilai 0 dan
 * prediksi menjadi tidak bermakna.
 *
 * Aman diulang: memakai updateOrCreate pada (employee_id, date).
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today();

        foreach (Employee::all() as $employee) {
            // Profil kehadiran berbeda-beda per karyawan agar data tidak seragam.
            $absenceRate = match ($employee->nik) {
                'EMP0003' => 0.18, // karyawan kontrak dengan absensi buruk
                'EMP0002' => 0.02, // disiplin
                default => 0.06,
            };

            for ($offset = 89; $offset >= 0; $offset--) {
                $date = $today->subDays($offset);

                if ($date->isWeekend()) {
                    continue;
                }

                $roll = mt_rand(1, 1000) / 1000;

                if ($roll < $absenceRate) {
                    $status = match (true) {
                        $roll < $absenceRate / 3 => Attendance::STATUS_ALPHA,
                        $roll < ($absenceRate * 2) / 3 => Attendance::STATUS_SAKIT,
                        default => Attendance::STATUS_IZIN,
                    };

                    Attendance::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                        ['status' => $status, 'check_in' => null, 'check_out' => null,
                            'late_minutes' => 0, 'overtime_minutes' => 0, 'note' => null],
                    );

                    continue;
                }

                $late = mt_rand(0, 100) < 15;
                $checkIn = $late
                    ? sprintf('08:%02d', mt_rand(10, 55))
                    : sprintf('07:%02d', mt_rand(45, 59));

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'status' => $late ? Attendance::STATUS_TERLAMBAT : Attendance::STATUS_HADIR,
                        'check_in' => $checkIn,
                        'check_out' => sprintf('17:%02d', mt_rand(0, 59)),
                        'late_minutes' => $late ? mt_rand(5, 45) : 0,
                        'overtime_minutes' => mt_rand(0, 10) < 3 ? mt_rand(30, 120) : 0,
                        'note' => null,
                    ],
                );
            }
        }
    }
}

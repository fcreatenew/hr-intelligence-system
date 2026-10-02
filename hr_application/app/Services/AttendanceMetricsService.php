<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;

/**
 * Menghitung fitur absensi dari data Attendance & LeaveRequest, sehingga model
 * tidak lagi bergantung pada angka yang diisi manual.
 *
 * Definisi (penting agar konsisten dengan pelatihan model):
 * - `riwayat_absen_90hari` = jumlah hari tidak hadir (izin + sakit + alpha)
 *   dalam 90 hari terakhir. Cuti tidak dihitung karena sudah direncanakan.
 * - `hari_absen_setahun`   = jumlah hari tidak hadir dalam 12 bulan terakhir.
 *
 * Keduanya dihitung dari tabel, bukan dari kolom yang bisa diisi pengguna.
 */
class AttendanceMetricsService
{
    public const ABSENCE_WINDOW_DAYS = 90;

    public const YEAR_WINDOW_MONTHS = 12;

    /**
     * Hitung kedua fitur sekaligus untuk satu karyawan.
     *
     * @return array{riwayat_absen_90hari: int, hari_absen_setahun: int}
     */
    public function forEmployee(Employee $employee, ?CarbonImmutable $asOf = null): array
    {
        $asOf = $asOf ?? CarbonImmutable::now();

        return [
            'riwayat_absen_90hari' => $this->absencesInLastDays($employee, self::ABSENCE_WINDOW_DAYS, $asOf),
            'hari_absen_setahun' => $this->absencesInLastDays($employee, self::YEAR_WINDOW_MONTHS * 30, $asOf),
        ];
    }

    /**
     * Jumlah hari tidak hadir dalam N hari terakhir (inklusif hari ini).
     */
    public function absencesInLastDays(Employee $employee, int $days, ?CarbonImmutable $asOf = null): int
    {
        $asOf = $asOf ?? CarbonImmutable::now();
        $start = $asOf->subDays($days - 1)->toDateString();

        return $this->absenceQuery($employee, $start, $asOf->toDateString())->count();
    }

    /**
     * Jumlah hari cuti disetujui pada rentang tertentu.
     */
    public function approvedLeaveDays(Employee $employee, ?int $year = null): int
    {
        $year = $year ?? (int) now()->year;

        return (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->approved()
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    /**
     * Jumlah kejadian tidak hadir per status pada rentang tertentu — dipakai
     * untuk ringkasan di UI.
     *
     * @return array<string, int>
     */
    public function absenceBreakdown(Employee $employee, int $days = self::ABSENCE_WINDOW_DAYS): array
    {
        $asOf = CarbonImmutable::now();
        $start = $asOf->subDays($days - 1)->toDateString();

        $counts = $this->absenceQuery($employee, $start, $asOf->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $breakdown = [];
        foreach (Attendance::ABSENCE_STATUSES as $status) {
            $breakdown[$status] = (int) ($counts[$status] ?? 0);
        }

        return $breakdown;
    }

    /**
     * Absensi harian pada rentang tanggal, untuk tabel di UI.
     */
    public function recordsBetween(Employee $employee, string $start, string $end)
    {
        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->betweenDates($start, $end)
            ->orderByDesc('date');
    }

    private function absenceQuery(Employee $employee, string $start, string $end)
    {
        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->absence()
            ->betweenDates($start, $end);
    }
}

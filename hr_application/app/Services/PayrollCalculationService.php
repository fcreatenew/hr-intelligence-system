<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Perhitungan komponen payroll dari data karyawan dan absensi.
 *
 * Aturan (dibuat eksplisit agar dapat diaudit):
 * - `gaji_pokok`  : dari kolom `gaji_pokok`, jatuh ke `gaji_bulanan` bila kosong.
 * - `tunjangan`   : dari kolom `tunjangan` (dianggap nominal bulanan).
 * - `lembur_jam`  : dihitung dari total `overtime_minutes` absensi pada periode
 *                   dibagi 60 (dibulatkan). Ini menggantikan input manual.
 * - `tarif_lembur`: dari kolom `tarif_lembur`, jatuh ke tarif default config.
 * - `potongan`    : potongan absensi = jumlah hari alpha × tarif harian,
 *                   ditambah potongan tetap dari kolom `potongan`.
 * - `total_gaji`  : pokok + tunjangan + biaya lembur − potongan.
 *
 * Rumus total gaji sengaja identik dengan `payroll_intelligence/src/calculations/payroll.py`
 * dan fitur model anomali payroll, agar angka HRIS dan model memakai definisi
 * yang sama.
 */
class PayrollCalculationService
{
    public function __construct(
        private readonly float $defaultOvertimeRate = 0.0,
        private readonly int $workingDaysPerMonth = 22,
    ) {
    }

    /**
     * Hitung satu baris payroll.
     *
     * @return array{gaji_pokok: int, tunjangan: float, lembur_jam: int, tarif_lembur: float, biaya_lembur: float, potongan: float, total_gaji: float}
     */
    public function calculateFor(Employee $employee, string $periode): array
    {
        [$start, $end] = $this->periodRange($periode);

        $gajiPokok = (int) ($employee->gaji_pokok ?? $employee->gaji_bulanan ?? 0);
        $tunjangan = (float) ($employee->tunjangan ?? 0);
        $tarifLembur = (float) ($employee->tarif_lembur ?? $this->defaultOvertimeRate);

        $lemburJam = $this->overtimeHours($employee, $start, $end);
        $biayaLembur = round($lemburJam * $tarifLembur, 2);

        $potongan = $this->deduction($employee, $start, $end, $gajiPokok);
        $totalGaji = round($gajiPokok + $tunjangan + $biayaLembur - $potongan, 2);

        return [
            'gaji_pokok' => $gajiPokok,
            'tunjangan' => $tunjangan,
            'lembur_jam' => $lemburJam,
            'tarif_lembur' => $tarifLembur,
            'biaya_lembur' => $biayaLembur,
            'potongan' => $potongan,
            'total_gaji' => $totalGaji,
        ];
    }

    /**
     * Jam lembur dari total menit lembur absensi pada periode.
     * Menit sisa kurang dari satu jam dibulatkan ke bawah.
     */
    public function overtimeHours(Employee $employee, CarbonInterface $start, CarbonInterface $end): int
    {
        $minutes = (int) Attendance::query()
            ->where('employee_id', $employee->id)
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->sum('overtime_minutes');

        return intdiv($minutes, 60);
    }

    /**
     * Potongan absensi: hari alpha dikali tarif harian, ditambah potongan tetap
     * dari data karyawan. Cuti disetujui tidak dipotong.
     */
    public function deduction(Employee $employee, CarbonInterface $start, CarbonInterface $end, int $gajiPokok): float
    {
        $alphaDays = Attendance::query()
            ->where('employee_id', $employee->id)
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->where('status', Attendance::STATUS_ALPHA)
            ->count();

        $dailyRate = $this->workingDaysPerMonth > 0
            ? $gajiPokok / $this->workingDaysPerMonth
            : 0.0;

        $absenceDeduction = round($alphaDays * $dailyRate, 2);

        return round($absenceDeduction + (float) ($employee->potongan ?? 0), 2);
    }

    /**
     * Rentang tanggal periode YYYY-MM (inklusif awal & akhir bulan).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodRange(string $periode): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $periode.'-01')->startOfMonth();

        return [$start, $start->endOfMonth()];
    }

    /** Jumlah hari cuti disetujui dalam periode — untuk ringkasan, bukan potongan. */
    public function approvedLeaveDays(Employee $employee, CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->approved()
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->sum('days');
    }
}

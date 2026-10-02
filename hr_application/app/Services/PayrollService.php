<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alur payroll run: buat periode, hitung baris per karyawan, skor anomali
 * lewat Payroll Intelligence (bila aktif), lalu finalisasi.
 *
 * Hasil perhitungan disimpan per periode (bukan ditulis ulang ke `employees`)
 * supaya setiap bulan dapat ditelusuri dan dihitung ulang.
 */
class PayrollService
{
    public function __construct(
        private readonly PayrollCalculationService $calculator,
        private readonly PayrollIntelligenceService $payrollIntelligence,
    ) {
    }

    /**
     * Buat atau ambil periode payroll. Periode final tidak boleh diubah.
     */
    public function openPeriod(string $periode): PayrollPeriod
    {
        if (! PayrollPeriod::isValidPeriod($periode)) {
            throw new RuntimeException("Format periode tidak valid: {$periode}. Gunakan YYYY-MM.");
        }

        return PayrollPeriod::firstOrCreate(
            ['periode' => $periode],
            ['status' => PayrollPeriod::STATUS_DRAFT],
        );
    }

    /**
     * Hitung ulang seluruh baris periode dari data karyawan & absensi.
     * Periode yang sudah final ditolak agar angka yang dibayarkan tidak berubah.
     */
    public function calculate(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        if ($period->isFinal()) {
            throw new RuntimeException('Periode yang sudah final tidak dapat dihitung ulang.');
        }

        return DB::transaction(function () use ($period, $actor) {
            $employees = Employee::query()
                ->where('status', true)
                ->orderBy('id')
                ->get();

            $period->items()->delete();

            $rows = [];

            foreach ($employees as $employee) {
                $components = $this->calculator->calculateFor($employee, $period->periode);

                $rows[] = array_merge($components, [
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'periode' => $period->periode,
                    'departemen' => $employee->department,
                    'level' => $employee->level,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                PayrollItem::insert($chunk);
            }

            $this->applyAnomalyScores($period);

            $period->update([
                'status' => PayrollPeriod::STATUS_CALCULATED,
                'employee_count' => count($rows),
                'total_amount' => (float) $period->items()->sum('total_gaji'),
                'calculated_at' => now(),
                'calculated_by' => $actor->id,
            ]);

            return $period->fresh();
        });
    }

    /**
     * Tulis skor anomali ke baris periode. Bila Payroll Intelligence tidak aktif
     * atau tidak dapat dihubungi, baris dibiarkan tanpa skor — perhitungan
     * payroll tidak boleh gagal hanya karena layanan analitik mati.
     */
    private function applyAnomalyScores(PayrollPeriod $period): void
    {
        if (! $this->payrollIntelligence->enabled()) {
            return;
        }

        $rows = $period->items()->with('employee')->get();

        if ($rows->isEmpty()) {
            return;
        }

        $payload = $rows->map(fn (PayrollItem $item) => [
            'employee_id' => (string) $item->employee->nik,
            'periode' => $item->periode,
            'departemen' => $item->departemen,
            'level' => $item->level,
            'gaji_pokok' => (float) $item->gaji_pokok,
            'tunjangan' => (float) $item->tunjangan,
            'lembur_jam' => (int) $item->lembur_jam,
            'tarif_lembur' => (float) $item->tarif_lembur,
            'potongan' => (float) $item->potongan,
            'total_gaji' => (float) $item->total_gaji,
        ])->all();

        $scores = $this->payrollIntelligence->score($payload);

        if ($scores === []) {
            return;
        }

        $byNik = [];

        foreach ($scores as $score) {
            $byNik[(string) ($score['employee_id'] ?? '')] = $score;
        }

        foreach ($rows as $item) {
            $score = $byNik[(string) $item->employee->nik] ?? null;

            if ($score === null) {
                continue;
            }

            $item->update([
                'skor_anomali' => $score['skor_anomali'] ?? null,
                'level_anomali' => $score['level'] ?? null,
                'alasan_anomali' => $score['alasan'] ?? null,
            ]);
        }
    }

    /** Finalisasi periode: angka dibekukan dan tidak dapat dihitung ulang. */
    public function finalize(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        if (! $period->isCalculated()) {
            throw new RuntimeException('Hitung periode terlebih dahulu sebelum finalisasi.');
        }

        $period->update([
            'status' => PayrollPeriod::STATUS_FINAL,
            'finalized_at' => now(),
            'finalized_by' => $actor->id,
        ]);

        return $period->fresh();
    }

    /** Ringkasan komponen periode untuk halaman detail. */
    public function summary(PayrollPeriod $period): array
    {
        $items = $period->items();

        return [
            'employee_count' => (int) $items->count(),
            'total_gaji_pokok' => (float) (clone $items)->sum('gaji_pokok'),
            'total_tunjangan' => (float) (clone $items)->sum('tunjangan'),
            'total_biaya_lembur' => (float) (clone $items)->sum('biaya_lembur'),
            'total_potongan' => (float) (clone $items)->sum('potongan'),
            'total_gaji' => (float) (clone $items)->sum('total_gaji'),
            'jumlah_anomali' => (int) (clone $items)->where('level_anomali', 'Anomali')->count(),
            'jumlah_perlu_ditinjau' => (int) (clone $items)->where('level_anomali', 'Perlu Ditinjau')->count(),
        ];
    }

    /**
     * Rekap absensi pendukung perhitungan (lembur & alpha) per karyawan.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function attendanceRecap(PayrollPeriod $period): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->calculator->periodRange($period->periode);

        return $period->items()
            ->with('employee')
            ->get()
            ->map(function (PayrollItem $item) use ($start, $end) {
                $alpha = Attendance::query()
                    ->where('employee_id', $item->employee_id)
                    ->betweenDates($start->toDateString(), $end->toDateString())
                    ->where('status', Attendance::STATUS_ALPHA)
                    ->count();

                return [
                    'item' => $item,
                    'alpha_hari' => $alpha,
                    'lembur_jam' => $item->lembur_jam,
                ];
            });
    }
}

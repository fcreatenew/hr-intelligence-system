<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\StatutoryReport;

/**
 * Menghasilkan ringkasan laporan statutori (BPJS/PPh21) dari data payroll
 * yang sudah dihitung pada sebuah periode.
 *
 * Nilai yang dihasilkan adalah **estimasi** berbasis komponen payroll yang
 * tersedia (gaji pokok, tunjangan, potongan) dan tarif konfigurasi; bukan
 * pengganti perhitungan resmi berlisensi. Tujuannya menyediakan draft awal
 * yang bisa ditinjau staf HR sebelum pelaporan resmi.
 */
class StatutoryReportService
{
    public function __construct(
        private readonly float $bpjsKesehatanEmployeeRate = 0.01,
        private readonly float $bpjsKesehatanEmployerRate = 0.04,
        private readonly float $bpjsJhtEmployeeRate = 0.02,
        private readonly float $bpjsJhtEmployerRate = 0.037,
        private readonly float $pph21Rate = 0.05,
    ) {}

    /**
     * Buat/segarkan laporan untuk tipe & periode tertentu.
     */
    public function generate(string $type, string $period, ?int $userId = null): StatutoryReport
    {
        $payload = $this->buildPayload($type, $period);

        return StatutoryReport::updateOrCreate(
            ['type' => $type, 'period' => $period],
            [
                'payload' => $payload,
                'status' => StatutoryReport::STATUS_DRAFT,
                'generated_at' => now(),
                'generated_by' => $userId,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(string $type, string $period): array
    {
        $periodModel = PayrollPeriod::query()->where('periode', $period)->first();

        $items = $periodModel
            ? $periodModel->items()->get(['gaji_pokok', 'tunjangan', 'potongan', 'total_gaji'])
            : collect();

        $dasar = (float) $items->sum(fn ($item) => (float) $item->gaji_pokok + (float) $item->tunjangan);

        return match ($type) {
            StatutoryReport::TYPE_BPJS_KESEHATAN => [
                'jumlah_karyawan' => $items->count(),
                'dasar_perhitungan' => $dasar,
                'iuran_karyawan' => round($dasar * $this->bpjsKesehatanEmployeeRate, 2),
                'iuran_pemberi_kerja' => round($dasar * $this->bpjsKesehatanEmployerRate, 2),
                'tarif_karyawan' => $this->bpjsKesehatanEmployeeRate,
                'tarif_pemberi_kerja' => $this->bpjsKesehatanEmployerRate,
            ],
            StatutoryReport::TYPE_BPJS_KETENAGAKERJAAN => [
                'jumlah_karyawan' => $items->count(),
                'dasar_perhitungan' => $dasar,
                'jht_karyawan' => round($dasar * $this->bpjsJhtEmployeeRate, 2),
                'jht_pemberi_kerja' => round($dasar * $this->bpjsJhtEmployerRate, 2),
                'tarif_karyawan' => $this->bpjsJhtEmployeeRate,
                'tarif_pemberi_kerja' => $this->bpjsJhtEmployerRate,
            ],
            StatutoryReport::TYPE_PPH21 => [
                'jumlah_karyawan' => $items->count(),
                'dasar_perhitungan' => $dasar,
                'estimasi_pph21' => round($dasar * $this->pph21Rate, 2),
                'tarif' => $this->pph21Rate,
            ],
            default => ['jumlah_karyawan' => 0, 'dasar_perhitungan' => 0.0],
        };
    }
}

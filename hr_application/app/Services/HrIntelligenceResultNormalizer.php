<?php

namespace App\Services;

/**
 * Menyeragamkan bentuk hasil prediksi tiap use case HR Intelligence API
 * menjadi baris yang bisa disimpan di tabel hr_intelligence_predictions.
 *
 * Bentuk asli tiap use case berbeda-beda (turnover: probabilitas_keluar,
 * kinerja: probabilitas per kelas, survival: risiko_relatif, dst).
 */
class HrIntelligenceResultNormalizer
{
    /**
     * @param  array<string, mixed>  $result  satu item dari "hasil" pada respons API
     * @return array{employee_id: string, skor: float|null, label: string|null, is_flag: bool, detail: array}
     */
    public function normalize(string $useCase, array $result): array
    {
        return match ($useCase) {
            'turnover' => $this->turnover($result),
            'absensi' => $this->absensi($result),
            'kinerja' => $this->kinerja($result),
            'payroll' => $this->payroll($result),
            'survival' => $this->survival($result),
            default => $this->generic($result),
        };
    }

    private function turnover(array $r): array
    {
        return $this->row(
            $r,
            skor: (float) ($r['probabilitas_keluar'] ?? 0),
            label: (string) ($r['level_risiko'] ?? ''),
            isFlag: (bool) ($r['prediksi_keluar'] ?? false),
        );
    }

    private function absensi(array $r): array
    {
        return $this->row(
            $r,
            skor: (float) ($r['probabilitas_absen_tinggi'] ?? 0),
            label: (string) ($r['level_risiko'] ?? ''),
            isFlag: (bool) ($r['prediksi_absen_tinggi'] ?? false),
        );
    }

    private function kinerja(array $r): array
    {
        return $this->row(
            $r,
            skor: isset($r['skor_ordinal']) ? (float) $r['skor_ordinal'] : null,
            label: (string) ($r['kategori_prediksi'] ?? ''),
            isFlag: false,
        );
    }

    private function payroll(array $r): array
    {
        return $this->row(
            $r,
            skor: (float) ($r['skor_anomali'] ?? 0),
            label: (string) ($r['level'] ?? ''),
            isFlag: (bool) ($r['is_anomali'] ?? false),
        );
    }

    private function survival(array $r): array
    {
        return $this->row(
            $r,
            skor: (float) ($r['risiko_relatif'] ?? 0),
            label: (string) ($r['level_risiko'] ?? ''),
            isFlag: ($r['level_risiko'] ?? null) === 'Tinggi',
        );
    }

    private function generic(array $r): array
    {
        return $this->row($r, skor: null, label: null, isFlag: false);
    }

    private function row(array $r, ?float $skor, ?string $label, bool $isFlag): array
    {
        return [
            'employee_id' => (string) ($r['employee_id'] ?? ''),
            'skor' => $skor,
            'label' => $label,
            'is_flag' => $isFlag,
            'detail' => $r,
        ];
    }
}

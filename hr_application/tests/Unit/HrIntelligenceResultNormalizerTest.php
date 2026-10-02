<?php

namespace Tests\Unit;

use App\Services\HrIntelligenceResultNormalizer;
use PHPUnit\Framework\TestCase;

class HrIntelligenceResultNormalizerTest extends TestCase
{
    private HrIntelligenceResultNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new HrIntelligenceResultNormalizer();
    }

    public function test_normalizes_turnover(): void
    {
        $row = $this->normalizer->normalize('turnover', [
            'employee_id' => 'EMP1',
            'probabilitas_keluar' => 0.68,
            'prediksi_keluar' => 1,
            'level_risiko' => 'Tinggi',
        ]);

        $this->assertSame('EMP1', $row['employee_id']);
        $this->assertSame(0.68, $row['skor']);
        $this->assertSame('Tinggi', $row['label']);
        $this->assertTrue($row['is_flag']);
    }

    public function test_normalizes_absensi(): void
    {
        $row = $this->normalizer->normalize('absensi', [
            'employee_id' => 'EMP2',
            'probabilitas_absen_tinggi' => 0.3,
            'prediksi_absen_tinggi' => 1,
            'level_risiko' => 'Sedang',
        ]);

        $this->assertSame(0.3, $row['skor']);
        $this->assertTrue($row['is_flag']);
    }

    public function test_normalizes_kinerja_uses_ordinal_score(): void
    {
        $row = $this->normalizer->normalize('kinerja', [
            'employee_id' => 'EMP3',
            'kategori_prediksi' => 'Tinggi',
            'probabilitas' => ['Rendah' => 0.1, 'Sedang' => 0.2, 'Tinggi' => 0.7],
            'skor_ordinal' => 2.6,
            'level' => 'Tinggi',
        ]);

        $this->assertSame(2.6, $row['skor']);
        $this->assertSame('Tinggi', $row['label']);
        $this->assertFalse($row['is_flag']);
    }

    public function test_normalizes_payroll(): void
    {
        $row = $this->normalizer->normalize('payroll', [
            'employee_id' => 'EMP4',
            'periode' => '2025-01',
            'skor_anomali' => 0.9,
            'is_anomali' => true,
            'level' => 'Anomali',
        ]);

        $this->assertSame(0.9, $row['skor']);
        $this->assertTrue($row['is_flag']);
    }

    public function test_normalizes_survival_flags_high_risk(): void
    {
        $row = $this->normalizer->normalize('survival', [
            'employee_id' => 'EMP5',
            'risiko_relatif' => 3.6,
            'level_risiko' => 'Tinggi',
            'median_survival_bulan' => 4.0,
        ]);

        $this->assertSame(3.6, $row['skor']);
        $this->assertTrue($row['is_flag']);
    }

    public function test_unknown_use_case_is_generic(): void
    {
        $row = $this->normalizer->normalize('entah', ['employee_id' => 'EMP6']);

        $this->assertSame('EMP6', $row['employee_id']);
        $this->assertNull($row['skor']);
        $this->assertFalse($row['is_flag']);
    }
}

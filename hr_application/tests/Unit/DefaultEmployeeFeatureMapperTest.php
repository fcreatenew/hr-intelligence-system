<?php

namespace Tests\Unit;

use App\Services\AttendanceMetricsService;
use App\Services\Mappers\DefaultEmployeeFeatureMapper;
use PHPUnit\Framework\TestCase;

class DefaultEmployeeFeatureMapperTest extends TestCase
{
    public function test_maps_array_input_with_promosi(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $result = $mapper->map([
            'employee_id' => 'EMP0001',
            'usia' => '38',
            'masa_kerja_bulan' => '172',
            'jam_lembur_bulanan' => '2',
            'gaji_bulanan' => '14514816',
            'tanggal_masuk' => '2010-08-12',
            'tanggal_promosi' => '2015-07-28',
            'departemen' => 'Marketing',
            'status_kerja' => 'Tetap',
            'pendidikan' => 'S1',
        ]);

        $this->assertSame('EMP0001', $result['employee_id']);
        $this->assertSame(38, $result['usia']);
        $this->assertIsInt($result['usia']);
        $this->assertSame('2010-08-12', $result['tanggal_masuk']);
        $this->assertSame('2015-07-28', $result['tanggal_promosi']);
    }

    public function test_maps_null_tanggal_promosi_correctly(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $result = $mapper->map([
            'employee_id' => 'EMP0002',
            'usia' => 26,
            'masa_kerja_bulan' => 6,
            'jam_lembur_bulanan' => 45,
            'gaji_bulanan' => 4500000,
            'tanggal_masuk' => '2024-06-01',
            'tanggal_promosi' => null,
            'departemen' => 'IT',
            'status_kerja' => 'Kontrak',
            'pendidikan' => 'SMA',
        ]);

        $this->assertNull($result['tanggal_promosi']);
    }

    public function test_maps_many_returns_indexed_array(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $employees = [
            ['employee_id' => 'A', 'usia' => 30, 'masa_kerja_bulan' => 12, 'jam_lembur_bulanan' => 5,
                'gaji_bulanan' => 5000000, 'tanggal_masuk' => '2023-01-01', 'tanggal_promosi' => null,
                'departemen' => 'IT', 'status_kerja' => 'Tetap', 'pendidikan' => 'S1'],
            ['employee_id' => 'B', 'usia' => 40, 'masa_kerja_bulan' => 24, 'jam_lembur_bulanan' => 10,
                'gaji_bulanan' => 6000000, 'tanggal_masuk' => '2022-01-01', 'tanggal_promosi' => null,
                'departemen' => 'HR', 'status_kerja' => 'Tetap', 'pendidikan' => 'S2'],
        ];

        $result = $mapper->mapMany($employees);

        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]['employee_id']);
        $this->assertSame('B', $result[1]['employee_id']);
    }

    public function test_maps_absensi_payload(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $result = $mapper->mapForUseCase('absensi', [
            'employee_id' => 'EMP0001',
            'usia' => 30,
            'masa_kerja_bulan' => 24,
            'jarak_km' => 15.5,
            'beban_kerja' => 80.0,
            'jam_lembur_bulanan' => 20.0,
            'kepuasan_kerja' => 3.1,
            'riwayat_absen_90hari' => 5,
            'departemen' => 'IT',
            'status_kerja' => 'Tetap',
            'pendidikan' => 'S1',
        ]);

        $this->assertSame(15.5, $result['jarak_km']);
        $this->assertSame(5, $result['riwayat_absen_90hari']);
        $this->assertSame('IT', $result['departemen']);
    }

    public function test_maps_kinerja_payload_with_defaults(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        // Record minimal: atribut non-turnover harus terisi default, bukan null.
        $result = $mapper->mapForUseCase('kinerja', [
            'employee_id' => 'EMP0002',
            'usia' => 40,
            'masa_kerja_bulan' => 60,
            'departemen' => 'Finance',
            'pendidikan' => 'S2',
        ]);

        $this->assertSame(2, $result['jumlah_training_setahun']);
        $this->assertSame(5, $result['proyek_selesai']);
        $this->assertIsFloat($result['skor_umpan_balik_360']);
        $this->assertSame(3, $result['hari_absen_setahun']);
    }

    public function test_maps_payroll_payload_falls_back_to_gaji_bulanan(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $result = $mapper->mapForUseCase('payroll', [
            'employee_id' => 'EMP0003',
            'gaji_bulanan' => 9000000,
            'departemen' => 'Finance',
        ]);

        $this->assertSame(9000000.0, $result['gaji_pokok']);
        $this->assertSame(9000000.0, $result['total_gaji']);
        $this->assertSame('Staff', $result['level']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $result['periode']);
    }

    public function test_maps_survival_payload(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $result = $mapper->mapForUseCase('survival', [
            'employee_id' => 'EMP0004',
            'usia' => 34,
            'gaji_bulanan' => 9500000,
            'jam_lembur_bulanan' => 18.0,
            'kepuasan_kerja' => 3.4,
            'bulan_sejak_promosi' => 22.0,
            'jarak_km' => 11.5,
            'departemen' => 'IT',
            'pendidikan' => 'S1',
        ]);

        $this->assertSame(9500000.0, $result['gaji_bulanan']);
        $this->assertSame(22.0, $result['bulan_sejak_promosi']);
    }

    public function test_map_for_use_case_turnover_matches_map(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper(new AttendanceMetricsService);

        $employee = [
            'employee_id' => 'EMP0001', 'usia' => 38, 'masa_kerja_bulan' => 172,
            'jam_lembur_bulanan' => 2, 'gaji_bulanan' => 14514816,
            'tanggal_masuk' => '2010-08-12', 'tanggal_promosi' => '2015-07-28',
            'departemen' => 'Marketing', 'status_kerja' => 'Tetap', 'pendidikan' => 'S1',
        ];

        $this->assertSame($mapper->map($employee), $mapper->mapForUseCase('turnover', $employee));
    }

    public function test_map_for_use_case_rejects_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DefaultEmployeeFeatureMapper(new AttendanceMetricsService))->mapForUseCase('tidak-ada', []);
    }
}

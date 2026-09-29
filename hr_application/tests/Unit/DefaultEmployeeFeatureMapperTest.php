<?php

namespace Tests\Unit;

use App\Services\Mappers\DefaultEmployeeFeatureMapper;
use PHPUnit\Framework\TestCase;

class DefaultEmployeeFeatureMapperTest extends TestCase
{
    public function test_maps_array_input_with_promosi(): void
    {
        $mapper = new DefaultEmployeeFeatureMapper();

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
        $mapper = new DefaultEmployeeFeatureMapper();

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
        $mapper = new DefaultEmployeeFeatureMapper();

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
}

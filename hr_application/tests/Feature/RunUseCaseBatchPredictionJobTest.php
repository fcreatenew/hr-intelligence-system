<?php

namespace Tests\Feature;

use App\Jobs\RunUseCaseBatchPredictionJob;
use App\Models\Employee;
use App\Models\HrIntelligencePrediction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunUseCaseBatchPredictionJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(string $nik, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'nik' => $nik,
            'name' => "Karyawan {$nik}",
            'email' => strtolower($nik).'@example.com',
            'department' => 'IT',
            'position' => 'Engineer',
            'join_date' => '2020-01-10',
            'status' => true,
            'tanggal_lahir' => '1990-05-01',
            'status_kerja' => 'Tetap',
            'pendidikan' => 'S1',
            'gaji_bulanan' => 12000000,
            'jam_lembur_bulanan' => 10,
        ], $overrides));
    }

    private function fakeApi(array $hasil): void
    {
        Http::fake([
            '*/predict/*/batch' => Http::response([
                'hasil' => $hasil,
                'jumlah_diproses' => count($hasil),
                'model_version' => 'v1',
                'threshold' => 0.5,
            ], 200),
        ]);
    }

    public function test_absensi_job_stores_normalized_predictions(): void
    {
        $this->makeEmployee('NIK001');
        $this->makeEmployee('NIK002', ['gaji_bulanan' => 9000000]);

        $this->fakeApi([
            [
                'employee_id' => 'NIK001',
                'probabilitas_absen_tinggi' => 0.3,
                'prediksi_absen_tinggi' => 1,
                'level_risiko' => 'Sedang',
                'threshold' => 0.19,
            ],
            [
                'employee_id' => 'NIK002',
                'probabilitas_absen_tinggi' => 0.8,
                'prediksi_absen_tinggi' => 1,
                'level_risiko' => 'Tinggi',
                'threshold' => 0.19,
            ],
        ]);

        RunUseCaseBatchPredictionJob::dispatchSync('absensi');

        $this->assertSame(2, HrIntelligencePrediction::count());

        $row = HrIntelligencePrediction::where('employee_id', 'NIK002')->first();
        $this->assertSame('absensi', $row->use_case);
        $this->assertSame(0.8, $row->skor);
        $this->assertSame('Tinggi', $row->label);
        $this->assertTrue($row->is_flag);
        $this->assertSame('v1', $row->model_version);
    }

    public function test_payroll_job_stores_predictions(): void
    {
        $this->makeEmployee('NIK010', ['gaji_pokok' => 9000000, 'level' => 'Senior']);

        $this->fakeApi([
            [
                'employee_id' => 'NIK010',
                'periode' => '2025-01',
                'skor_anomali' => 0.99,
                'is_anomali' => true,
                'level' => 'Anomali',
                'threshold' => 0.5,
            ],
        ]);

        RunUseCaseBatchPredictionJob::dispatchSync('payroll');

        $row = HrIntelligencePrediction::first();
        $this->assertSame('payroll', $row->use_case);
        $this->assertTrue($row->is_flag);
        $this->assertSame('Anomali', $row->label);
    }

    public function test_unknown_use_case_stores_nothing(): void
    {
        $this->makeEmployee('NIK020');
        Http::fake();

        RunUseCaseBatchPredictionJob::dispatchSync('entah-apa');

        $this->assertSame(0, HrIntelligencePrediction::count());
    }
}

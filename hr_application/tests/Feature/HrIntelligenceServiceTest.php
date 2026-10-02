<?php

namespace Tests\Feature;

use App\Exceptions\HrIntelligenceAuthException;
use App\Exceptions\HrIntelligenceRateLimitException;
use App\Exceptions\HrIntelligenceUnavailableException;
use App\Exceptions\HrIntelligenceValidationException;
use App\Services\HrIntelligenceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HrIntelligenceServiceTest extends TestCase
{
    private function makeService(array $overrides = []): HrIntelligenceService
    {
        $config = array_merge([
            'baseUrl' => 'http://hr-api.test',
            'apiKey' => 'test-key',
            'timeoutSeconds' => 5,
            'retryTimes' => 2,
            'retryDelayMs' => 1,
            'batchChunkSize' => 500,
            'useCases' => [
                'turnover' => ['label' => 'Turnover', 'path' => '/predict', 'batch_path' => '/predict/batch'],
                'absensi' => ['label' => 'Absensi', 'path' => '/predict/absensi', 'batch_path' => '/predict/absensi/batch'],
                'kinerja' => ['label' => 'Kinerja', 'path' => '/predict/kinerja', 'batch_path' => '/predict/kinerja/batch'],
                'payroll' => ['label' => 'Payroll', 'path' => '/predict/payroll', 'batch_path' => '/predict/payroll/batch'],
                'survival' => ['label' => 'Survival', 'path' => '/predict/survival', 'batch_path' => '/predict/survival/batch'],
            ],
        ], $overrides);

        return new HrIntelligenceService(...$config);
    }

    private array $validEmployee = [
        'employee_id' => 'EMP0001',
        'usia' => 38,
        'masa_kerja_bulan' => 172,
        'jam_lembur_bulanan' => 2,
        'gaji_bulanan' => 14514816,
        'tanggal_masuk' => '2010-08-12',
        'tanggal_promosi' => '2015-07-28',
        'departemen' => 'Marketing',
        'status_kerja' => 'Tetap',
        'pendidikan' => 'S1',
    ];

    public function test_predict_success(): void
    {
        Http::fake([
            'hr-api.test/predict' => Http::response([
                'employee_id' => 'EMP0001',
                'probabilitas_keluar' => 0.22,
                'prediksi_keluar' => 0,
                'level_risiko' => 'Rendah',
            ], 200),
        ]);

        $result = $this->makeService()->predict($this->validEmployee);

        $this->assertSame('EMP0001', $result['employee_id']);
        $this->assertSame('Rendah', $result['level_risiko']);

        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'test-key')
            && $request->url() === 'http://hr-api.test/predict');
    }

    public function test_predict_throws_auth_exception_on_401(): void
    {
        Http::fake([
            '*/predict' => Http::response(['detail' => 'API key tidak valid.'], 401),
        ]);

        $this->expectException(HrIntelligenceAuthException::class);
        $this->expectExceptionMessage('API key tidak valid.');

        $this->makeService()->predict($this->validEmployee);
    }

    public function test_predict_throws_validation_exception_on_422_with_errors_preserved(): void
    {
        Http::fake([
            '*/predict' => Http::response([
                'detail' => 'Input tidak valid.',
                'errors' => [['loc' => ['usia'], 'msg' => 'usia harus >= 17']],
            ], 422),
        ]);

        try {
            $this->makeService()->predict(['usia' => -5] + $this->validEmployee);
            $this->fail('Exception seharusnya dilempar.');
        } catch (HrIntelligenceValidationException $e) {
            $this->assertSame('Input tidak valid.', $e->getMessage());
            $this->assertNotEmpty($e->getApiErrors());
        }
    }

    public function test_predict_throws_rate_limit_exception_on_429_with_retry_after(): void
    {
        Http::fake([
            '*/predict' => Http::response(
                ['detail' => 'Rate limit terlampaui.'],
                429,
                ['Retry-After' => '30']
            ),
        ]);

        try {
            $this->makeService()->predict($this->validEmployee);
            $this->fail('Exception seharusnya dilempar.');
        } catch (HrIntelligenceRateLimitException $e) {
            $this->assertSame(30, $e->getRetryAfterSeconds());
        }
    }

    public function test_predict_retries_on_500_then_succeeds(): void
    {
        Http::fake([
            '*/predict' => Http::sequence()
                ->push(['detail' => 'internal error'], 500)
                ->push([
                    'employee_id' => 'EMP0001',
                    'probabilitas_keluar' => 0.4,
                    'prediksi_keluar' => 1,
                    'level_risiko' => 'Sedang',
                ], 200),
        ]);

        $result = $this->makeService(['retryTimes' => 3])->predict($this->validEmployee);

        $this->assertSame('Sedang', $result['level_risiko']);
        Http::assertSentCount(2);
    }

    public function test_predict_throws_unavailable_exception_after_exhausting_retries_on_500(): void
    {
        Http::fake([
            '*/predict' => Http::response(['detail' => 'internal error'], 500),
        ]);

        $this->expectException(HrIntelligenceUnavailableException::class);

        $this->makeService(['retryTimes' => 3])->predict($this->validEmployee);

        Http::assertSentCount(3);
    }

    public function test_predict_throws_unavailable_exception_on_connection_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->expectException(HrIntelligenceUnavailableException::class);

        $this->makeService(['retryTimes' => 2])->predict($this->validEmployee);
    }

    public function test_predict_batch_rejects_payload_larger_than_chunk_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->makeService(['batchChunkSize' => 2]);
        $service->predictBatch([$this->validEmployee, $this->validEmployee, $this->validEmployee]);
    }

    public function test_health_does_not_send_api_key_header(): void
    {
        Http::fake([
            'hr-api.test/health' => Http::response(['status' => 'ok', 'model_loaded' => true], 200),
        ]);

        $this->makeService()->health();

        Http::assertSent(fn ($request) => ! $request->hasHeader('X-API-Key'));
    }

    public function test_predict_use_case_hits_correct_path(): void
    {
        Http::fake([
            'hr-api.test/predict/absensi' => Http::response([
                'employee_id' => 'EMP0001',
                'probabilitas_absen_tinggi' => 0.4,
                'prediksi_absen_tinggi' => 1,
                'level_risiko' => 'Sedang',
                'threshold' => 0.19,
            ], 200),
        ]);

        $result = $this->makeService()->predictUseCase('absensi', ['employee_id' => 'EMP0001']);

        $this->assertSame(0.4, $result['probabilitas_absen_tinggi']);
        Http::assertSent(fn ($request) => $request->url() === 'http://hr-api.test/predict/absensi'
            && $request->hasHeader('X-API-Key', 'test-key'));
    }

    public function test_predict_use_case_batch_wraps_payload(): void
    {
        Http::fake([
            'hr-api.test/predict/payroll/batch' => Http::response([
                'hasil' => [['employee_id' => 'EMP0001']],
                'jumlah_diproses' => 1,
                'model_version' => 'v1',
                'threshold' => 0.5,
            ], 200),
        ]);

        $result = $this->makeService()->predictUseCaseBatch('payroll', [['employee_id' => 'EMP0001']]);

        $this->assertSame(1, $result['jumlah_diproses']);
        Http::assertSent(fn ($request) => $request['employees'][0]['employee_id'] === 'EMP0001');
    }

    public function test_predict_use_case_batch_rejects_oversized_payload(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->makeService(['batchChunkSize' => 1]);
        $service->predictUseCaseBatch('kinerja', [['employee_id' => 'A'], ['employee_id' => 'B']]);
    }

    public function test_predict_use_case_rejects_unknown_use_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeService()->predictUseCase('tidak-ada', []);
    }

    public function test_use_case_info_hits_model_endpoint(): void
    {
        Http::fake([
            'hr-api.test/model/survival/info' => Http::response([
                'use_case' => 'survival',
                'versi_model' => 'v1',
                'model' => 'Cox Proportional Hazards (lifelines)',
            ], 200),
        ]);

        $info = $this->makeService()->useCaseInfo('survival');

        $this->assertSame('v1', $info['versi_model']);
    }
}

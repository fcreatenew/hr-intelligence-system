<?php

namespace Tests\Feature;

use App\Models\HrIntelligencePrediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HrIntelligenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedPrediction(string $useCase, string $employeeId, float $skor, bool $flag): void
    {
        HrIntelligencePrediction::create([
            'use_case' => $useCase,
            'employee_id' => $employeeId,
            'skor' => $skor,
            'label' => $flag ? 'Tinggi' : 'Rendah',
            'is_flag' => $flag,
            'detail' => ['employee_id' => $employeeId],
            'model_version' => 'v1',
            'predicted_at' => now(),
        ]);
    }

    public function test_guest_cannot_access_use_cases(): void
    {
        $this->getJson('/admin/hr-intelligence/use-cases')->assertUnauthorized();
    }

    public function test_hr_manager_can_access(): void
    {
        $user = User::factory()->create(['role' => 'hr-manager']);

        Http::fake(['*/model/*/info' => Http::response(['use_case' => 'x', 'versi_model' => 'v1'], 200)]);

        $this->actingAs($user)
            ->getJson('/admin/hr-intelligence/use-cases')
            ->assertOk();
    }

    public function test_lists_use_cases_with_model_metadata(): void
    {
        Http::fake([
            '*/model/turnover/info' => Http::response(['use_case' => 'turnover', 'versi_model' => 'v2', 'model' => 'Soft Voting'], 200),
            '*/model/absensi/info' => Http::response(['use_case' => 'absensi', 'versi_model' => 'v1', 'model' => 'Gradient Boosting'], 200),
            '*/model/kinerja/info' => Http::response(['use_case' => 'kinerja', 'versi_model' => 'v1', 'model' => 'XGBoost'], 200),
            '*/model/payroll/info' => Http::response(['use_case' => 'payroll', 'versi_model' => 'v1', 'model' => 'IsolationForest'], 200),
            '*/model/survival/info' => Http::response(['use_case' => 'survival', 'versi_model' => 'v1', 'model' => 'Cox PH'], 200),
        ]);

        $resp = $this->actingAs($this->admin())->getJson('/admin/hr-intelligence/use-cases');

        $resp->assertOk();
        $useCases = collect($resp->json('data'))->pluck('use_case')->all();
        $this->assertSame(['turnover', 'absensi', 'kinerja', 'payroll', 'survival'], $useCases);
    }

    public function test_trigger_queues_job_for_known_use_case(): void
    {
        Http::fake();

        $this->actingAs($this->admin())
            ->postJson('/admin/hr-intelligence/payroll/run')
            ->assertStatus(202)
            ->assertJsonPath('message', "Job prediksi 'payroll' telah diantrikan.");
    }

    public function test_trigger_rejects_unknown_use_case(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/hr-intelligence/tidak-ada/run')
            ->assertNotFound();
    }

    public function test_index_returns_latest_snapshot_sorted_by_score(): void
    {
        $this->seedPrediction('absensi', 'EMP1', 0.2, false);
        $this->seedPrediction('absensi', 'EMP2', 0.9, true);
        // Snapshot lama untuk EMP1; hanya yang terbaru yang boleh tampil.
        $this->seedPrediction('absensi', 'EMP1', 0.55, true);
        $this->seedPrediction('payroll', 'EMP3', 0.99, true);

        $resp = $this->actingAs($this->admin())
            ->getJson('/admin/hr-intelligence/absensi/predictions');

        $resp->assertOk();
        $rows = $resp->json('data');
        $this->assertCount(2, $rows);
        $this->assertSame('EMP2', $rows[0]['employee_id']);
        $this->assertSame('EMP1', $rows[1]['employee_id']);
        $this->assertSame(0.55, $rows[1]['skor']);
    }
}

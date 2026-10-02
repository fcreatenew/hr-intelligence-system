<?php

namespace Tests\Feature;

use App\Jobs\RunUseCaseBatchPredictionJob;
use App\Models\HrIntelligencePrediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HrIntelligencePageTest extends TestCase
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

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get('/admin/hr-intelligence')->assertRedirect('/login');
    }

    public function test_dashboard_renders_all_use_cases(): void
    {
        Http::fake(['*/model/*/info' => Http::response(['versi_model' => 'v2'], 200)]);

        $this->actingAs($this->admin())
            ->get('/admin/hr-intelligence')
            ->assertOk()
            ->assertSee('Prediksi ML')
            ->assertSee('Turnover karyawan')
            ->assertSee('Anomali payroll');
    }

    public function test_predictions_page_renders_results(): void
    {
        $this->seedPrediction('absensi', 'EMP-001', 0.91, true);

        $this->actingAs($this->admin())
            ->get('/admin/hr-intelligence/absensi')
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertSee('Absensi tinggi');
    }

    public function test_predictions_page_unknown_use_case_is_not_found(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/hr-intelligence/tidak-ada')
            ->assertNotFound();
    }

    public function test_run_from_html_form_redirects_and_queues_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())
            ->post('/admin/hr-intelligence/payroll/run')
            ->assertRedirect(route('hr-intelligence.use-case.predictions', 'payroll'));

        Queue::assertPushed(RunUseCaseBatchPredictionJob::class);
    }

    public function test_run_as_json_still_returns_accepted(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())
            ->postJson('/admin/hr-intelligence/payroll/run')
            ->assertAccepted();
    }
}

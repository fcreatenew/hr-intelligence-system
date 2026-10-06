<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\StatutoryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function hrManager(): User
    {
        return User::factory()->create(['role' => 'hr-manager']);
    }

    private function periodWithItems(): PayrollPeriod
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-10']);

        PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => Employee::factory()->create()->id,
            'periode' => '2026-10',
            'gaji_pokok' => 10_000_000,
            'tunjangan' => 2_000_000,
            'potongan' => 0,
            'total_gaji' => 12_000_000,
        ]);

        return $period;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('statutory-reports.index'))->assertRedirect(route('login'));
    }

    public function test_generating_bpjs_report_uses_payroll_totals(): void
    {
        $this->periodWithItems();

        $this->actingAs($this->admin())
            ->post(route('statutory-reports.store'), [
                'type' => StatutoryReport::TYPE_BPJS_KESEHATAN,
                'period' => '2026-10',
            ])
            ->assertRedirect();

        $report = StatutoryReport::query()->firstOrFail();
        $this->assertSame(12_000_000.0, (float) $report->payload['dasar_perhitungan']);
        $this->assertSame(120_000.0, (float) $report->payload['iuran_karyawan']);
        $this->assertSame(480_000.0, (float) $report->payload['iuran_pemberi_kerja']);
        $this->assertSame(StatutoryReport::STATUS_DRAFT, $report->status);
    }

    public function test_generating_report_is_idempotent_per_type_and_period(): void
    {
        $this->periodWithItems();

        $payload = ['type' => StatutoryReport::TYPE_PPH21, 'period' => '2026-10'];

        $this->actingAs($this->admin())->post(route('statutory-reports.store'), $payload);
        $this->actingAs($this->admin())->post(route('statutory-reports.store'), $payload);

        $this->assertSame(1, StatutoryReport::query()->count());
    }

    public function test_period_must_be_yyyy_mm(): void
    {
        $this->actingAs($this->admin())
            ->post(route('statutory-reports.store'), [
                'type' => StatutoryReport::TYPE_BPJS_KESEHATAN,
                'period' => '10-2026',
            ])
            ->assertSessionHasErrors('period');
    }

    public function test_finalize_marks_report_final(): void
    {
        $report = StatutoryReport::factory()->create();

        $this->actingAs($this->hrManager())
            ->post(route('statutory-reports.finalize', $report))
            ->assertRedirect(route('statutory-reports.show', $report));

        $this->assertSame(StatutoryReport::STATUS_FINAL, $report->refresh()->status);
    }

    public function test_only_admin_can_delete_report(): void
    {
        $report = StatutoryReport::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete(route('statutory-reports.destroy', $report))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('statutory-reports.destroy', $report))
            ->assertRedirect(route('statutory-reports.index'));

        $this->assertDatabaseMissing('statutory_reports', ['id' => $report->id]);
    }
}

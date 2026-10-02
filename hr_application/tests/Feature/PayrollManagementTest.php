<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\PayrollCalculationService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payroll-periods.index'))->assertRedirect(route('login'));
    }

    public function test_invalid_period_format_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('payroll-periods.store'), ['periode' => '2026-13'])
            ->assertSessionHasErrors('periode');
    }

    public function test_duplicate_period_is_rejected(): void
    {
        PayrollPeriod::factory()->create(['periode' => '2026-05']);

        $this->actingAs($this->admin())
            ->post(route('payroll-periods.store'), ['periode' => '2026-05'])
            ->assertSessionHasErrors('periode');
    }

    public function test_opening_period_creates_draft(): void
    {
        $this->actingAs($this->admin())
            ->post(route('payroll-periods.store'), ['periode' => '2026-05'])
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_periods', [
            'periode' => '2026-05',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);
    }

    public function test_calculate_builds_rows_for_active_employees_only(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        Employee::factory()->count(3)->create(['status' => true]);
        Employee::factory()->inactive()->create();

        $this->actingAs($this->admin())
            ->post(route('payroll-periods.calculate', $period))
            ->assertRedirect(route('payroll-periods.show', $period));

        $period->refresh();
        $this->assertSame(PayrollPeriod::STATUS_CALCULATED, $period->status);
        $this->assertSame(3, $period->items()->count());
        $this->assertSame(3, $period->employee_count);
    }

    public function test_total_gaji_follows_documented_formula(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        $employee = Employee::factory()->create([
            'status' => true,
            'gaji_pokok' => 10_000_000,
            'tunjangan' => 2_000_000,
            'tarif_lembur' => 50_000,
            'potongan' => 0,
        ]);

        Attendance::factory()->for($employee)->onDate('2026-05-04')->hadir()->create(['overtime_minutes' => 300]);
        Attendance::factory()->for($employee)->onDate('2026-05-05')->hadir()->create(['overtime_minutes' => 90]);

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $item = PayrollItem::where('employee_id', $employee->id)->firstOrFail();

        // 300 + 90 = 390 menit -> 6 jam (sisa < 1 jam dibulatkan ke bawah).
        $this->assertSame(6, $item->lembur_jam);
        $this->assertSame(300_000.0, (float) $item->biaya_lembur);
        $this->assertSame(
            12_300_000.0,
            (float) $item->total_gaji,
            'total = pokok + tunjangan + biaya lembur - potongan',
        );
    }

    public function test_alpha_days_are_deducted_using_daily_rate(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        $employee = Employee::factory()->create([
            'status' => true,
            'gaji_pokok' => 22_000_000,
            'tunjangan' => 0,
            'tarif_lembur' => 0,
            'potongan' => 0,
        ]);

        Attendance::factory()->for($employee)->onDate('2026-05-06')->absence()->create();
        Attendance::factory()->for($employee)->onDate('2026-05-07')->absence()->create();

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $item = PayrollItem::where('employee_id', $employee->id)->firstOrFail();

        // 2 hari alpha × (22.000.000 / 22) = 2.000.000.
        $this->assertSame(2_000_000.0, (float) $item->potongan);
        $this->assertSame(20_000_000.0, (float) $item->total_gaji);
    }

    public function test_overtime_outside_period_is_ignored(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        $employee = Employee::factory()->create([
            'status' => true,
            'gaji_pokok' => 10_000_000,
            'tarif_lembur' => 50_000,
        ]);

        Attendance::factory()->for($employee)->onDate('2026-04-30')->create(['overtime_minutes' => 600]);
        Attendance::factory()->for($employee)->onDate('2026-05-02')->create(['overtime_minutes' => 60]);

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $this->assertSame(1, PayrollItem::where('employee_id', $employee->id)->firstOrFail()->lembur_jam);
    }

    public function test_period_summary_totals_match_items(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        Employee::factory()->count(2)->create(['status' => true]);

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $summary = app(PayrollService::class)->summary($period->refresh());

        $this->assertSame(
            (float) $period->items()->sum('total_gaji'),
            $summary['total_gaji'],
        );
        $this->assertSame(2, $summary['employee_count']);
    }

    public function test_recalculate_replaces_previous_rows(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        $employee = Employee::factory()->create(['status' => true, 'gaji_pokok' => 5_000_000]);

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));
        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $this->assertSame(1, PayrollItem::where('employee_id', $employee->id)->count());
    }

    public function test_finalize_freezes_period_and_blocks_recalculation(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        Employee::factory()->create(['status' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('payroll-periods.calculate', $period));
        $this->actingAs($admin)->post(route('payroll-periods.finalize', $period))->assertRedirect();

        $period->refresh();
        $this->assertSame(PayrollPeriod::STATUS_FINAL, $period->status);
        $this->assertNotNull($period->finalized_at);

        $this->actingAs($admin)
            ->post(route('payroll-periods.calculate', $period))
            ->assertForbidden();
    }

    public function test_hr_manager_cannot_finalize(): void
    {
        $period = PayrollPeriod::factory()->calculated()->create();

        $this->actingAs($this->hrManager())
            ->post(route('payroll-periods.finalize', $period))
            ->assertForbidden();
    }

    public function test_finalize_requires_calculation_first(): void
    {
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriod::STATUS_DRAFT]);

        $this->actingAs($this->admin())
            ->post(route('payroll-periods.finalize', $period))
            ->assertForbidden();
    }

    public function test_export_streams_csv_with_components(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        $employee = Employee::factory()->create([
            'status' => true,
            'name' => 'Siti Aminah',
            'nik' => 'EMP7777',
            'gaji_pokok' => 8_000_000,
        ]);

        $this->actingAs($this->admin())->post(route('payroll-periods.calculate', $period));

        $response = $this->actingAs($this->admin())
            ->get(route('payroll-periods.export', $period))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('EMP7777', $csv);
        $this->assertStringContainsString('Siti Aminah', $csv);
        $this->assertStringContainsString('total_gaji', $csv);
    }

    public function test_calculation_service_is_consistent_with_reference_formula(): void
    {
        $employee = Employee::factory()->create([
            'status' => true,
            'gaji_pokok' => 9_000_000,
            'tunjangan' => 1_500_000,
            'tarif_lembur' => 60_000,
            'potongan' => 250_000,
        ]);

        Attendance::factory()->for($employee)->onDate('2026-05-10')->create(['overtime_minutes' => 120]);

        $components = app(PayrollCalculationService::class)->calculateFor($employee, '2026-05');

        $expected = $components['gaji_pokok']
            + $components['tunjangan']
            + $components['lembur_jam'] * $components['tarif_lembur']
            - $components['potongan'];

        $this->assertEqualsWithDelta($expected, $components['total_gaji'], 0.01);
    }
}

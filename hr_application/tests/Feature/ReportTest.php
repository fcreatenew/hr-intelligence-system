<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_report_aggregates_headcount_and_salary_per_department(): void
    {
        Employee::factory()->create([
            'department' => 'IT',
            'status' => true,
            'gaji_bulanan' => 10_000_000,
        ]);
        Employee::factory()->create([
            'department' => 'IT',
            'status' => true,
            'gaji_bulanan' => 8_000_000,
        ]);
        Employee::factory()->create([
            'department' => 'Sales',
            'status' => false,
            'gaji_bulanan' => 6_000_000,
        ]);

        $response = $this->actingAs($this->admin())->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Laporan HR')
            ->assertSee('IT')
            ->assertSee('Sales');

        $departments = $response->viewData('departments')->keyBy('department');

        $this->assertSame(2, $departments['IT']['total']);
        $this->assertSame(2, $departments['IT']['aktif']);
        $this->assertSame(0.0, $departments['IT']['turnover_rate']);
        $this->assertEquals(18_000_000, $departments['IT']['total_gaji']);

        $this->assertSame(1, $departments['Sales']['total']);
        $this->assertSame(0, $departments['Sales']['aktif']);
        $this->assertSame(100.0, $departments['Sales']['turnover_rate']);
        // Gaji karyawan non-aktif tidak dihitung sebagai biaya berjalan.
        $this->assertEquals(0, $departments['Sales']['total_gaji']);
    }

    public function test_report_counts_absences_within_period_only(): void
    {
        $employee = Employee::factory()->create(['department' => 'IT', 'status' => true]);

        Attendance::factory()->for($employee)->absence()->onDate('2026-05-10')->create();
        Attendance::factory()->for($employee)->absence()->onDate('2026-05-11')->create();
        // Di luar rentang — tidak boleh ikut terhitung.
        Attendance::factory()->for($employee)->absence()->onDate('2026-01-05')->create();

        $response = $this->actingAs($this->admin())->get(route('reports.index', [
            'from' => '2026-05-01',
            'to' => '2026-05-31',
        ]));

        $departments = $response->viewData('departments')->keyBy('department');

        $this->assertSame(2.0, $departments['IT']['rata_tidak_hadir']);
    }

    public function test_export_streams_csv_with_totals(): void
    {
        Employee::factory()->create([
            'department' => 'IT',
            'status' => true,
            'gaji_bulanan' => 5_000_000,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('reports.export', ['from' => '2026-05-01', 'to' => '2026-05-31']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Departemen,Karyawan,Aktif,Non-aktif', $csv);
        $this->assertStringContainsString('IT,1,1,0', $csv);
        $this->assertStringContainsString('Total,1,1,0', $csv);
    }

    public function test_hr_manager_can_view_report(): void
    {
        Employee::factory()->create(['department' => 'IT']);

        $this->actingAs(User::factory()->create(['role' => 'hr-manager']))
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('IT');
    }
}

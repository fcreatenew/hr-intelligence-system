<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
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

    public function test_creating_employee_writes_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->post(route('employees.store'), [
                'nik' => 'EMP1001',
                'name' => 'Andi Wijaya',
                'email' => 'andi@example.com',
                'department' => 'IT',
                'position' => 'Staff',
                'join_date' => '2024-01-05',
                'status' => true,
            ]);

        $log = AuditLog::where('auditable_type', Employee::class)
            ->where('event', AuditLog::EVENT_CREATED)
            ->firstOrFail();

        $this->assertSame('EMP1001', $log->new_values['nik']);
        $this->assertNotNull($log->user_id);
    }

    public function test_updating_employee_records_only_changed_attributes(): void
    {
        $employee = Employee::factory()->create(['name' => 'Nama Lama', 'department' => 'IT']);

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), [
                'nik' => $employee->nik,
                'name' => 'Nama Baru',
                'email' => $employee->email,
                'department' => 'IT',
                'position' => $employee->position,
                'join_date' => $employee->join_date->toDateString(),
                'status' => true,
            ]);

        $log = AuditLog::where('auditable_type', Employee::class)
            ->where('event', AuditLog::EVENT_UPDATED)
            ->firstOrFail();

        $this->assertSame('Nama Lama', $log->old_values['name']);
        $this->assertSame('Nama Baru', $log->new_values['name']);
        $this->assertArrayNotHasKey('department', $log->new_values, 'atribut yang tidak berubah tidak dicatat');
        $this->assertArrayNotHasKey('updated_at', $log->new_values);
    }

    public function test_attendance_and_leave_changes_are_audited(): void
    {
        $employee = Employee::factory()->create();

        Attendance::factory()->for($employee)->onDate('2026-05-04')->create(['status' => Attendance::STATUS_HADIR]);
        LeaveRequest::factory()->for($employee)->create();

        $this->assertDatabaseHas('audit_logs', ['auditable_type' => Attendance::class, 'event' => AuditLog::EVENT_CREATED]);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => LeaveRequest::class, 'event' => AuditLog::EVENT_CREATED]);
    }

    public function test_payroll_period_lifecycle_is_audited(): void
    {
        $period = PayrollPeriod::factory()->create(['periode' => '2026-05']);
        Employee::factory()->create(['status' => true]);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('payroll-periods.calculate', $period));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PayrollPeriod::class,
            'auditable_id' => $period->id,
            'event' => AuditLog::EVENT_UPDATED,
        ]);
    }

    public function test_deleting_employee_writes_audit_log(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())->delete(route('employees.destroy', $employee));

        $log = AuditLog::where('auditable_type', Employee::class)
            ->where('event', AuditLog::EVENT_DELETED)
            ->firstOrFail();

        $this->assertSame($employee->nik, $log->old_values['nik']);
    }

    public function test_audit_page_is_admin_only(): void
    {
        $this->actingAs($this->hrManager())
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Jejak Audit');
    }

    public function test_audit_page_shows_change_values(): void
    {
        $employee = Employee::factory()->create(['name' => 'Budi Lama']);
        $employee->update(['name' => 'Budi Baru']);

        $this->actingAs($this->admin())
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Budi Baru');
    }
}

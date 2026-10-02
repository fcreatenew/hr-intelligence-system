<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::factory()->create($overrides);
    }

    /** @return array<string, mixed> */
    private function payload(Employee $employee, array $overrides = []): array
    {
        return array_merge([
            'nik' => $employee->nik,
            'name' => $employee->name,
            'email' => $employee->email,
            'department' => $employee->department,
            'position' => $employee->position,
            'join_date' => $employee->join_date->toDateString(),
            'status' => '1',
        ], $overrides);
    }

    public function test_terminate_requires_authentication(): void
    {
        $employee = $this->employee();

        $this->post(route('employees.terminate', $employee))->assertRedirect(route('login'));
    }

    public function test_update_records_position_salary_and_department_history(): void
    {
        $employee = $this->employee([
            'department' => 'IT',
            'position' => 'Staff',
            'gaji_bulanan' => 5_000_000,
        ]);

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), $this->payload($employee, [
                'department' => 'HR',
                'position' => 'Manager',
                'gaji_bulanan' => 9_000_000,
            ]))
            ->assertRedirect(route('employees.show', $employee));

        $events = $employee->histories()->pluck('event')->all();

        $this->assertContains(EmployeeHistory::EVENT_DEPARTMENT, $events);
        $this->assertContains(EmployeeHistory::EVENT_POSITION, $events);
        $this->assertContains(EmployeeHistory::EVENT_SALARY, $events);

        $salary = $employee->histories()->where('event', EmployeeHistory::EVENT_SALARY)->first();
        $this->assertSame('5000000', $salary->old_value);
        $this->assertSame('9000000', $salary->new_value);
    }

    public function test_update_without_meaningful_change_writes_no_history(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), $this->payload($employee));

        $this->assertSame(0, $employee->histories()->count());
    }

    public function test_terminate_sets_status_and_records_history(): void
    {
        $employee = $this->employee(['status' => true, 'termination_date' => null]);

        $this->actingAs($this->admin())
            ->post(route('employees.terminate', $employee), [
                'termination_date' => now()->subDays(2)->toDateString(),
                'termination_reason' => 'Resign',
                'termination_note' => 'Pindah perusahaan',
            ])
            ->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertFalse($employee->status);
        $this->assertNotNull($employee->termination_date);
        $this->assertSame('Resign', $employee->termination_reason);
        $this->assertTrue($employee->has_terminated);

        $history = $employee->histories()->where('event', EmployeeHistory::EVENT_TERMINATED)->first();
        $this->assertNotNull($history);
        $this->assertSame('Pindah perusahaan', $history->note);
    }

    public function test_terminate_rejects_future_date_and_unknown_reason(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('employees.terminate', $employee), [
                'termination_date' => now()->addWeek()->toDateString(),
                'termination_reason' => 'Alasan tidak dikenal',
            ])
            ->assertSessionHasErrors(['termination_date', 'termination_reason']);

        $this->assertNull($employee->fresh()->termination_date);
    }

    public function test_employee_show_lists_history(): void
    {
        $employee = $this->employee();
        $employee->recordHistory(EmployeeHistory::EVENT_POSITION, 'position', 'Staff', 'Manager');

        $this->actingAs($this->admin())
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Perubahan jabatan')
            ->assertSee('Manager');
    }
}

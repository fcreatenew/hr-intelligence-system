<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendances.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_attendance_records(): void
    {
        $employee = $this->employee(['name' => 'Budi Santoso']);
        Attendance::factory()->for($employee)->onDate(now()->toDateString())->create([
            'status' => Attendance::STATUS_HADIR,
        ]);

        $this->actingAs($this->admin())
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_index_filters_by_employee_and_status(): void
    {
        $budi = $this->employee(['name' => 'Budi Santoso']);
        $siti = $this->employee(['name' => 'Siti Aminah']);

        Attendance::factory()->for($budi)->onDate(now()->toDateString())->absence()->create();
        Attendance::factory()->for($siti)->onDate(now()->toDateString())->create([
            'status' => Attendance::STATUS_HADIR,
        ]);

        $this->actingAs($this->admin())
            ->get(route('attendances.index', ['employee_id' => $budi->id, 'status' => 'alpha']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('1 catatan pada rentang ini.');
    }

    public function test_can_create_attendance_record(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('attendances.store'), [
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'status' => Attendance::STATUS_HADIR,
                'check_in' => '08:00',
                'check_out' => '17:00',
                'late_minutes' => 0,
                'overtime_minutes' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'status' => Attendance::STATUS_HADIR,
            'overtime_minutes' => 30,
        ]);
    }

    public function test_duplicate_record_for_same_employee_and_date_is_rejected(): void
    {
        $employee = $this->employee();
        $date = now()->toDateString();

        Attendance::factory()->for($employee)->onDate($date)->create();

        $this->actingAs($this->admin())
            ->post(route('attendances.store'), [
                'employee_id' => $employee->id,
                'date' => $date,
                'status' => Attendance::STATUS_HADIR,
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame(1, Attendance::where('employee_id', $employee->id)->count());
    }

    public function test_check_out_before_check_in_is_rejected(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('attendances.store'), [
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'status' => Attendance::STATUS_HADIR,
                'check_in' => '17:00',
                'check_out' => '08:00',
            ])
            ->assertSessionHasErrors('check_out');
    }

    public function test_can_update_attendance_record(): void
    {
        $employee = $this->employee();
        $attendance = Attendance::factory()->for($employee)->create([
            'status' => Attendance::STATUS_HADIR,
            'late_minutes' => 0,
        ]);

        $this->actingAs($this->admin())
            ->put(route('attendances.update', $attendance), [
                'employee_id' => $employee->id,
                'date' => $attendance->date->toDateString(),
                'status' => Attendance::STATUS_TERLAMBAT,
                'late_minutes' => 25,
            ])
            ->assertRedirect();

        $this->assertSame(Attendance::STATUS_TERLAMBAT, $attendance->fresh()->status);
        $this->assertSame(25, $attendance->fresh()->late_minutes);
    }

    public function test_admin_can_delete_but_hr_manager_cannot(): void
    {
        $attendance = Attendance::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'hr-manager']))
            ->delete(route('attendances.destroy', $attendance))
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);

        $this->actingAs($this->admin())
            ->delete(route('attendances.destroy', $attendance))
            ->assertRedirect();

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_recap_shows_computed_absence_features(): void
    {
        $employee = $this->employee(['name' => 'Budi Santoso']);

        Attendance::factory()->count(3)->for($employee)->absence()->create();
        Attendance::factory()->for($employee)->onDate(now()->toDateString())->create([
            'status' => Attendance::STATUS_HADIR,
        ]);

        $this->actingAs($this->admin())
            ->get(route('attendances.recap'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('absen 90hr');
    }
}

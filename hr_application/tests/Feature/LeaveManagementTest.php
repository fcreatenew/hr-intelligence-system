<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
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

    private function employee(array $overrides = []): Employee
    {
        return Employee::factory()->create($overrides);
    }

    /** @return array<string, mixed> */
    private function payload(Employee $employee, array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $employee->id,
            'type' => 'tahunan',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'reason' => 'Liburan keluarga',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('leave-requests.index'))->assertRedirect(route('login'));
    }

    public function test_days_are_computed_from_date_range_ignoring_client_input(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('leave-requests.store'), $this->payload($employee) + ['days' => 99])
            ->assertRedirect();

        $leaveRequest = LeaveRequest::first();
        $this->assertSame(3, $leaveRequest->days, 'days harus dihitung server, bukan dari input');
        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->status);
    }

    public function test_single_day_request_counts_as_one_day(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('leave-requests.store'), $this->payload($employee, [
                'start_date' => '2026-11-02',
                'end_date' => '2026-11-02',
            ]));

        $this->assertSame(1, LeaveRequest::first()->days);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('leave-requests.store'), $this->payload($employee, [
                'start_date' => '2026-11-05',
                'end_date' => '2026-11-01',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_approval_deducts_balance_and_writes_attendance(): void
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::factory()->for($employee)->annual()->create([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->hrManager())
            ->post(route('leave-requests.approve', $leaveRequest), ['decision_note' => 'Disetujui'])
            ->assertRedirect(route('leave-requests.show', $leaveRequest));

        $leaveRequest->refresh();
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leaveRequest->status);
        $this->assertNotNull($leaveRequest->decided_at);

        $balance = $employee->leaveBalanceFor(2026);
        $this->assertSame(3, $balance->used_days);
        $this->assertSame(9, $balance->remaining_days);

        $this->assertSame(3, Attendance::where('employee_id', $employee->id)
            ->where('status', Attendance::STATUS_CUTI)->count());
    }

    public function test_approved_leave_does_not_count_as_absence_for_model(): void
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::factory()->for($employee)->annual()->create([
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.approve', $leaveRequest));

        // Cuti bukan "tidak hadir": fitur model harus tetap 0.
        $this->assertSame(0, app(AttendanceMetricsService::class)->absencesInLastDays($employee, 90));
    }

    public function test_approval_is_blocked_when_balance_is_insufficient(): void
    {
        $employee = $this->employee();
        $employee->leaveBalanceFor(2026)->update(['entitled_days' => 2, 'used_days' => 0]);

        $leaveRequest = LeaveRequest::factory()->for($employee)->annual()->create([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertSessionHasErrors('decision');

        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->fresh()->status);
        $this->assertSame(0, $employee->leaveBalanceFor(2026)->used_days);
    }

    public function test_each_leave_type_deducts_its_own_quota(): void
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::factory()->for($employee)->create([
            'type' => 'melahirkan',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.approve', $leaveRequest));

        $this->assertSame(0, $employee->leaveBalanceFor(2026)->used_days, 'cuti melahirkan tidak memotong jatah tahunan');
        $this->assertSame(3, $employee->leaveBalanceFor(2026, 'melahirkan')->used_days);
        $this->assertSame(87, $employee->leaveBalanceFor(2026, 'melahirkan')->remaining_days);
    }

    public function test_leave_type_without_default_quota_is_blocked(): void
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::factory()->for($employee)->create([
            'type' => 'lainnya',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertSessionHasErrors('decision');

        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->fresh()->status);
    }

    public function test_already_decided_request_cannot_be_decided_again(): void
    {
        $leaveRequest = LeaveRequest::factory()->annual()->approved()->create([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'days' => 3,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertForbidden();
    }

    public function test_rejection_keeps_balance_and_status(): void
    {
        $employee = $this->employee();
        $leaveRequest = LeaveRequest::factory()->for($employee)->annual()->create([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('leave-requests.reject', $leaveRequest), ['decision_note' => 'Beban kerja tinggi'])
            ->assertRedirect();

        $this->assertSame(LeaveRequest::STATUS_REJECTED, $leaveRequest->fresh()->status);
        $this->assertSame(0, $employee->leaveBalanceFor(2026)->used_days);
    }

    public function test_hr_manager_can_decide_but_cannot_delete(): void
    {
        $leaveRequest = LeaveRequest::factory()->annual()->create(['status' => LeaveRequest::STATUS_PENDING]);
        $manager = $this->hrManager();

        $this->actingAs($manager)
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertRedirect();

        $this->actingAs($manager)
            ->delete(route('leave-requests.destroy', $leaveRequest))
            ->assertForbidden();
    }

    public function test_pending_requests_are_listed_before_decided_ones(): void
    {
        $pending = LeaveRequest::factory()->create(['status' => LeaveRequest::STATUS_PENDING, 'start_date' => now()]);
        $decided = LeaveRequest::factory()->approved()->create(['start_date' => now()->addDays(5)]);

        $response = $this->actingAs($this->admin())->get(route('leave-requests.index'))->assertOk();

        $response->assertSeeInOrder([$pending->employee->name, $decided->employee->name]);
    }

    public function test_balances_page_shows_remaining_days(): void
    {
        $employee = $this->employee(['name' => 'Budi Santoso']);
        $employee->leaveBalanceFor(2026)->update(['entitled_days' => 12, 'used_days' => 4]);

        $this->actingAs($this->admin())
            ->get(route('leave-requests.balances', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('8');
    }

    public function test_calendar_shows_approved_leave_for_its_month(): void
    {
        $employee = $this->employee(['name' => 'Sari Wijaya']);
        LeaveRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'type' => 'tahunan',
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-05',
            'days' => 3,
        ]);

        $this->actingAs($this->admin())
            ->get(route('leave-requests.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Sari Wijaya')
            ->assertSee('November 2026');
    }

    public function test_calendar_hides_pending_leave(): void
    {
        $employee = $this->employee(['name' => 'Rudi Pending']);
        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => LeaveRequest::STATUS_PENDING,
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-05',
            'days' => 3,
        ]);

        $this->actingAs($this->admin())
            ->get(route('leave-requests.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertDontSee('Rudi Pending');
    }

    public function test_calendar_excludes_leave_outside_selected_month(): void
    {
        $employee = $this->employee(['name' => 'Tono Desember']);
        LeaveRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-12',
            'days' => 3,
        ]);

        $this->actingAs($this->admin())
            ->get(route('leave-requests.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertDontSee('Tono Desember');
    }

    public function test_calendar_includes_leave_spanning_month_boundary(): void
    {
        $employee = $this->employee(['name' => 'Lintas Bulan']);
        LeaveRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-10-30',
            'end_date' => '2026-11-02',
            'days' => 4,
        ]);

        $this->actingAs($this->admin())
            ->get(route('leave-requests.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('Lintas Bulan');
    }

    public function test_calendar_falls_back_to_current_month_for_invalid_input(): void
    {
        $this->actingAs($this->admin())
            ->get(route('leave-requests.calendar', ['month' => 'bukan-bulan']))
            ->assertOk();
    }

    public function test_calendar_requires_authentication(): void
    {
        $this->get(route('leave-requests.calendar'))->assertRedirect(route('login'));
    }
}

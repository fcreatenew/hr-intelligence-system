<?php

namespace Tests\Feature;

use App\Models\CompensationComponent;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompensationManagementTest extends TestCase
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
        $this->get(route('compensation-components.index'))->assertRedirect(route('login'));
    }

    public function test_hr_can_create_component(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->hrManager())
            ->post(route('compensation-components.store'), [
                'employee_id' => $employee->id,
                'name' => 'Tunjangan transport',
                'type' => CompensationComponent::TYPE_ALLOWANCE,
                'amount' => 750000,
                'recurring' => true,
                'effective_date' => '2026-10-01',
            ])
            ->assertRedirect(route('compensation-components.index'));

        $this->assertDatabaseHas('compensation_components', [
            'employee_id' => $employee->id,
            'name' => 'Tunjangan transport',
            'type' => CompensationComponent::TYPE_ALLOWANCE,
        ]);
    }

    public function test_component_requires_valid_type_and_amount(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('compensation-components.store'), [
                'employee_id' => $employee->id,
                'name' => 'Aneh',
                'type' => 'ngawur',
                'amount' => -5,
            ])
            ->assertSessionHasErrors(['type', 'amount']);
    }

    public function test_end_date_cannot_precede_effective_date(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('compensation-components.store'), [
                'employee_id' => $employee->id,
                'name' => 'Bonus',
                'type' => CompensationComponent::TYPE_BONUS,
                'amount' => 1000000,
                'effective_date' => '2026-10-10',
                'end_date' => '2026-10-01',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_only_admin_can_delete_component(): void
    {
        $component = CompensationComponent::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete(route('compensation-components.destroy', $component))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('compensation-components.destroy', $component))
            ->assertRedirect(route('compensation-components.index'));

        $this->assertDatabaseMissing('compensation_components', ['id' => $component->id]);
    }

    public function test_deduction_types_are_flagged(): void
    {
        $deduction = CompensationComponent::factory()->create(['type' => CompensationComponent::TYPE_TAX]);
        $allowance = CompensationComponent::factory()->create(['type' => CompensationComponent::TYPE_ALLOWANCE]);

        $this->assertTrue($deduction->isDeduction());
        $this->assertFalse($allowance->isDeduction());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('performance-goals.index'))->assertRedirect(route('login'));
    }

    public function test_hr_can_create_goal(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('performance-goals.store'), [
                'employee_id' => $employee->id,
                'period' => '2026-Q4',
                'title' => 'Naikkan NPS',
                'weight' => 30,
                'target' => 80,
                'status' => PerformanceGoal::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('performance-goals.index'));

        $this->assertDatabaseHas('performance_goals', [
            'employee_id' => $employee->id,
            'period' => '2026-Q4',
            'title' => 'Naikkan NPS',
            'status' => PerformanceGoal::STATUS_ACTIVE,
        ]);
    }

    public function test_goal_requires_existing_employee(): void
    {
        $this->actingAs($this->admin())
            ->post(route('performance-goals.store'), [
                'employee_id' => 999999,
                'period' => '2026-Q4',
                'title' => 'Sasaran',
                'status' => PerformanceGoal::STATUS_DRAFT,
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_creating_review_derives_kategori_from_rating(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('performance-reviews.store'), [
                'employee_id' => $employee->id,
                'period' => '2026-Q4',
                'rating' => 5,
                'status' => PerformanceReview::STATUS_SUBMITTED,
            ])
            ->assertRedirect(route('performance-reviews.index'));

        $this->assertDatabaseHas('performance_reviews', [
            'employee_id' => $employee->id,
            'rating' => 5,
            'kategori' => 'A',
        ]);
    }

    public function test_review_is_unique_per_employee_and_period(): void
    {
        $employee = Employee::factory()->create();
        PerformanceReview::factory()->create([
            'employee_id' => $employee->id,
            'period' => '2026-Q4',
        ]);

        $this->actingAs($this->admin())
            ->post(route('performance-reviews.store'), [
                'employee_id' => $employee->id,
                'period' => '2026-Q4',
                'status' => PerformanceReview::STATUS_DRAFT,
            ])
            ->assertSessionHasErrors('period');

        $this->assertSame(1, PerformanceReview::query()->where('employee_id', $employee->id)->count());
    }

    public function test_assigned_reviewer_can_update_review(): void
    {
        $reviewer = User::factory()->create(['role' => 'staff']);
        $review = PerformanceReview::factory()->create(['reviewer_id' => $reviewer->id]);

        $this->actingAs($reviewer)
            ->get(route('performance-reviews.edit', $review))
            ->assertOk();
    }

    public function test_goal_progress_is_computed(): void
    {
        $goal = PerformanceGoal::factory()->create(['target' => 100, 'achievement' => 75]);

        $this->assertSame(0.75, $goal->progress());
    }
}

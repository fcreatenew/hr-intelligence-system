<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PerformanceGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceGoal>
 */
class PerformanceGoalFactory extends Factory
{
    protected $model = PerformanceGoal::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y').'-Q'.now()->quarter,
            'title' => 'Sasaran '.fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'weight' => fake()->numberBetween(10, 40),
            'target' => fake()->numberBetween(1, 10) * 10,
            'achievement' => null,
            'status' => PerformanceGoal::STATUS_DRAFT,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => PerformanceGoal::STATUS_ACTIVE]);
    }
}

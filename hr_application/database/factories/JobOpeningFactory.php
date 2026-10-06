<?php

namespace Database\Factories;

use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    public function definition(): array
    {
        return [
            'title' => 'Posisi '.fake()->unique()->jobTitle(),
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations', 'Marketing', 'Sales']),
            'employment_type' => fake()->randomElement(JobOpening::EMPLOYMENT_TYPES),
            'location' => fake()->city(),
            'description' => fake()->paragraph(),
            'requirements' => fake()->sentence(),
            'quota' => fake()->numberBetween(1, 5),
            'status' => JobOpening::STATUS_DRAFT,
            'opened_at' => null,
            'closed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => JobOpening::STATUS_OPEN,
            'opened_at' => now()->subDays(7)->toDateString(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => JobOpening::STATUS_CLOSED,
            'opened_at' => now()->subMonth()->toDateString(),
            'closed_at' => now()->subDays(3)->toDateString(),
        ]);
    }
}

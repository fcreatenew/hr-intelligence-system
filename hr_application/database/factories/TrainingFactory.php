<?php

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-30 days', '+45 days');

        return [
            'title' => 'Pelatihan '.fake()->unique()->words(2, true),
            'category' => fake()->randomElement(Training::CATEGORIES),
            'provider' => fake()->optional()->company(),
            'mode' => fake()->randomElement(Training::MODES),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+2 days')->format('Y-m-d'),
            'duration_hours' => fake()->numberBetween(4, 40),
            'cost' => fake()->numberBetween(0, 50) * 100000,
            'quota' => fake()->optional(0.7)->numberBetween(5, 40),
            'status' => Training::STATUS_RUNNING,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Training::STATUS_DRAFT]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => Training::STATUS_DONE,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subMonth()->addDays(2)->toDateString(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'job_opening_id' => JobOpening::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'source' => fake()->randomElement(['lamaran_online', 'referral', 'job_fair', 'headhunter']),
            'stage' => Candidate::STAGE_APPLIED,
            'expected_salary' => fake()->numberBetween(5, 30) * 1000000,
            'notes' => null,
            'applied_at' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
        ];
    }

    public function hired(): static
    {
        return $this->state(fn () => ['stage' => Candidate::STAGE_HIRED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['stage' => Candidate::STAGE_REJECTED]);
    }

    public function atStage(string $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }
}

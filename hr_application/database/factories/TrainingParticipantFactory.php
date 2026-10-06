<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingParticipant>
 */
class TrainingParticipantFactory extends Factory
{
    protected $model = TrainingParticipant::class;

    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'employee_id' => Employee::factory(),
            'status' => TrainingParticipant::STATUS_REGISTERED,
            'score' => null,
            'certified' => false,
        ];
    }

    public function completed(int $score = 85): static
    {
        return $this->state(fn () => [
            'status' => TrainingParticipant::STATUS_COMPLETED,
            'score' => $score,
            'certified' => $score >= 75,
            'completed_at' => now(),
        ]);
    }
}

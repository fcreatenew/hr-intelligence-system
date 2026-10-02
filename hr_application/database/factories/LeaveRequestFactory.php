<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-60 days', '+30 days');
        $days = fake()->numberBetween(1, 5);
        $end = (clone $start)->modify('+'.($days - 1).' days');

        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(LeaveRequest::TYPES),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $days,
            'reason' => fake()->optional()->sentence(),
            'status' => LeaveRequest::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => LeaveRequest::STATUS_APPROVED,
            'decided_at' => now(),
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn () => ['type' => 'tahunan']);
    }
}

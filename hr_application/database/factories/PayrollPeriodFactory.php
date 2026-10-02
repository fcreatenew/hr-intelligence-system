<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        return [
            'periode' => fake()->unique()->dateTimeBetween('-1 year', 'now')->format('Y-m'),
            'status' => PayrollPeriod::STATUS_DRAFT,
            'employee_count' => 0,
            'total_amount' => 0,
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn () => [
            'status' => PayrollPeriod::STATUS_CALCULATED,
            'calculated_at' => now(),
        ]);
    }

    public function final(): static
    {
        return $this->state(fn () => [
            'status' => PayrollPeriod::STATUS_FINAL,
            'calculated_at' => now(),
            'finalized_at' => now(),
        ]);
    }
}

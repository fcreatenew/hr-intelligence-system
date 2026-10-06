<?php

namespace Database\Factories;

use App\Models\CompensationComponent;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompensationComponent>
 */
class CompensationComponentFactory extends Factory
{
    protected $model = CompensationComponent::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => 'Tunjangan '.fake()->word(),
            'type' => CompensationComponent::TYPE_ALLOWANCE,
            'amount' => fake()->numberBetween(1, 20) * 100000,
            'recurring' => true,
            'effective_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'notes' => null,
        ];
    }
}

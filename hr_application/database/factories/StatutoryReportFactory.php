<?php

namespace Database\Factories;

use App\Models\StatutoryReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatutoryReport>
 */
class StatutoryReportFactory extends Factory
{
    protected $model = StatutoryReport::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(StatutoryReport::TYPES),
            'period' => now()->format('Y-m'),
            'status' => StatutoryReport::STATUS_DRAFT,
            'payload' => null,
            'generated_at' => null,
        ];
    }
}

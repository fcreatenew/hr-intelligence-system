<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'title' => 'Dokumen '.fake()->word(),
            'category' => fake()->randomElement(EmployeeDocument::CATEGORIES),
            'file_path' => null,
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'status' => EmployeeDocument::STATUS_ACTIVE,
            'notes' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDays(5)->toDateString(),
            'status' => EmployeeDocument::STATUS_EXPIRED,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);
    }
}

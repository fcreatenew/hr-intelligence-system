<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeHistory>
 */
class EmployeeHistoryFactory extends Factory
{
    protected $model = EmployeeHistory::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'event' => EmployeeHistory::EVENT_POSITION,
            'field' => 'position',
            'old_value' => 'Staff',
            'new_value' => 'Senior Staff',
            'note' => null,
            'changed_by' => null,
        ];
    }
}

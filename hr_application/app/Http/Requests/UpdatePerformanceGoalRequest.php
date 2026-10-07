<?php

namespace App\Http\Requests;

use App\Models\PerformanceGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceGoal $goal */
        $goal = $this->route('performance_goal');

        return $this->user()?->can('update', $goal) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'weight' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target' => ['nullable', 'numeric', 'min:0'],
            'achievement' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(PerformanceGoal::STATUSES)],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\PerformanceGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerformanceGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PerformanceGoal::class) ?? false;
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

<?php

namespace App\Http\Requests;

use App\Models\CompensationComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompensationComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CompensationComponent $component */
        $component = $this->route('compensation_component');

        return $this->user()?->can('update', $component) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(CompensationComponent::TYPES)],
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'recurring' => ['boolean'],
            'effective_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

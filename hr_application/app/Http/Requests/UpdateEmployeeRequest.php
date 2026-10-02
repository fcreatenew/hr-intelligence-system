<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $id = $employee instanceof Employee ? $employee->getKey() : $employee;

        return array_merge(parent::rules(), [
            'nik' => ['required', 'string', 'max:50', Rule::unique('employees', 'nik')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($id)],
            'termination_date' => ['nullable', 'date', 'before_or_equal:today'],
            'termination_reason' => ['nullable', Rule::in(Employee::TERMINATION_REASONS)],
        ]);
    }
}

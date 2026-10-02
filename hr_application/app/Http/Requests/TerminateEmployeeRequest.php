<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateEmployeeRequest extends FormRequest
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
        return [
            'termination_date' => ['required', 'date', 'before_or_equal:today'],
            'termination_reason' => ['required', Rule::in(Employee::TERMINATION_REASONS)],
            'termination_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'termination_date.required' => 'Tanggal keluar wajib diisi.',
            'termination_date.before_or_equal' => 'Tanggal keluar tidak boleh di masa depan.',
            'termination_reason.required' => 'Alasan keluar wajib dipilih.',
            'termination_reason.in' => 'Alasan keluar tidak dikenal.',
        ];
    }
}

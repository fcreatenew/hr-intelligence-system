<?php

namespace App\Http\Requests;

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeDocument::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(EmployeeDocument::CATEGORIES)],
            'file_path' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'status' => ['required', Rule::in(EmployeeDocument::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

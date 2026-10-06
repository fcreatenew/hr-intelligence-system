<?php

namespace App\Http\Requests;

use App\Models\StatutoryReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateStatutoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StatutoryReport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(StatutoryReport::TYPES)],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.regex' => 'Periode harus dalam format YYYY-MM, mis. 2025-03.',
        ];
    }
}

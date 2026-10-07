<?php

namespace App\Http\Requests;

use App\Models\JobOpening;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var JobOpening $jobOpening */
        $jobOpening = $this->route('job_opening');

        return $this->user()?->can('update', $jobOpening) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:80'],
            'employment_type' => ['required', Rule::in(JobOpening::EMPLOYMENT_TYPES)],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', Rule::in(JobOpening::STATUSES)],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
        ];
    }
}

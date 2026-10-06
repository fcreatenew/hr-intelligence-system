<?php

namespace App\Http\Requests;

use App\Models\Training;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Training $training */
        $training = $this->route('training');

        return $this->user()?->can('update', $training) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(Training::CATEGORIES)],
            'provider' => ['nullable', 'string', 'max:120'],
            'mode' => ['required', Rule::in(Training::MODES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in(Training::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}

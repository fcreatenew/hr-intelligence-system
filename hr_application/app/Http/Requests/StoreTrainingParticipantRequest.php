<?php

namespace App\Http\Requests;

use App\Models\Training;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Training $training */
        $training = $this->route('training');

        return $this->user()?->can('manageParticipants', $training) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Pilih minimal satu karyawan.',
        ];
    }
}

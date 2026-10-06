<?php

namespace App\Http\Requests;

use App\Models\TrainingParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TrainingParticipant $participant */
        $participant = $this->route('participant');

        return $this->user()?->can('manageParticipants', $participant->training) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(TrainingParticipant::STATUSES)],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'certified' => ['boolean'],
            'certificate_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'score.max' => 'Skor maksimum 100.',
        ];
    }
}

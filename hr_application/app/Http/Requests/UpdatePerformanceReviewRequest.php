<?php

namespace App\Http\Requests;

use App\Models\PerformanceReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceReview $review */
        $review = $this->route('performance_review');

        return $this->user()?->can('update', $review) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => [
                'required',
                'string',
                'max:10',
                Rule::unique('performance_reviews', 'period')
                    ->where('employee_id', $this->input('employee_id'))
                    ->ignore($review->id),
            ],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'review_date' => ['nullable', 'date'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(PerformanceReview::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.unique' => 'Karyawan ini sudah punya review untuk periode tersebut.',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceReview>
 */
class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        $rating = fake()->numberBetween(1, 5);

        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y').'-Q'.now()->quarter,
            'reviewer_id' => User::factory(),
            'review_date' => now()->toDateString(),
            'rating' => $rating,
            'kategori' => PerformanceReview::kategoriFromRating($rating),
            'strengths' => fake()->sentence(),
            'improvements' => fake()->sentence(),
            'status' => PerformanceReview::STATUS_DRAFT,
        ];
    }
}

<?php

namespace App\Policies;

use App\Models\PerformanceReview;
use App\Models\User;

class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, PerformanceReview $review): bool
    {
        return $this->isHr($user) || $this->isReviewer($user, $review);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    /** HR atau penilai yang ditugaskan boleh memperbarui review. */
    public function update(User $user, PerformanceReview $review): bool
    {
        return $this->isHr($user) || $this->isReviewer($user, $review);
    }

    public function delete(User $user, PerformanceReview $review): bool
    {
        return $user->role === 'admin';
    }

    private function isReviewer(User $user, PerformanceReview $review): bool
    {
        return $review->reviewer_id !== null && $review->reviewer_id === $user->id;
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

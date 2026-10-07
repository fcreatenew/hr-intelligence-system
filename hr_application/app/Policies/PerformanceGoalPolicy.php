<?php

namespace App\Policies;

use App\Models\PerformanceGoal;
use App\Models\User;

class PerformanceGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, PerformanceGoal $goal): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, PerformanceGoal $goal): bool
    {
        return $this->isHr($user);
    }

    public function delete(User $user, PerformanceGoal $goal): bool
    {
        return $this->isHr($user);
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

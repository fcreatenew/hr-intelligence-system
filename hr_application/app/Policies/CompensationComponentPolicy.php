<?php

namespace App\Policies;

use App\Models\CompensationComponent;
use App\Models\User;

class CompensationComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, CompensationComponent $component): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, CompensationComponent $component): bool
    {
        return $this->isHr($user);
    }

    public function delete(User $user, CompensationComponent $component): bool
    {
        return $user->role === 'admin';
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $this->isHr($user);
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $this->isHr($user);
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

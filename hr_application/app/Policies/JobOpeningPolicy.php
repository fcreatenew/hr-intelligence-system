<?php

namespace App\Policies;

use App\Models\JobOpening;
use App\Models\User;

class JobOpeningPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, JobOpening $jobOpening): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, JobOpening $jobOpening): bool
    {
        return $this->isHr($user);
    }

    /** Menghapus lowongan bersifat destruktif (menghapus kandidat) → admin. */
    public function delete(User $user, JobOpening $jobOpening): bool
    {
        return $user->role === 'admin';
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

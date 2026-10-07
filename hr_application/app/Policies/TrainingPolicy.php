<?php

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, Training $training): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, Training $training): bool
    {
        return $this->isHr($user);
    }

    /** Menghapus katalog pelatihan bersifat destruktif → admin saja. */
    public function delete(User $user, Training $training): bool
    {
        return $user->role === 'admin';
    }

    /** Mendaftarkan karyawan ke pelatihan. */
    public function manageParticipants(User $user, Training $training): bool
    {
        return $this->isHr($user);
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

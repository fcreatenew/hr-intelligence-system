<?php

namespace App\Policies;

use App\Models\User;

/**
 * Manajemen pengguna hanya untuk admin.
 *
 * `hr-manager` boleh memakai HRIS tetapi tidak boleh mengelola akun, sehingga
 * tidak bisa menaikkan perannya sendiri menjadi admin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, User $target): bool
    {
        // Admin tidak boleh menghapus akunnya sendiri.
        return $this->isAdmin($user) && $user->isNot($target);
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }
}

<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Semua peran HR boleh melihat daftar karyawan.
     */
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->isHr($user);
    }

    /**
     * Menghapus data karyawan hanya untuk admin — bersifat destruktif dan
     * memutus riwayat prediksi yang merujuk ke karyawan tersebut.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->role === 'admin';
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

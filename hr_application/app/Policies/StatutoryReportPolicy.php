<?php

namespace App\Policies;

use App\Models\StatutoryReport;
use App\Models\User;

class StatutoryReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, StatutoryReport $report): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, StatutoryReport $report): bool
    {
        return $this->isHr($user);
    }

    /** Laporan statutori bersifat arsip resmi → hanya admin boleh hapus. */
    public function delete(User $user, StatutoryReport $report): bool
    {
        return $user->role === 'admin';
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

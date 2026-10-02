<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, PayrollPeriod $period): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    /** Menghitung ulang hanya selama periode belum final. */
    public function calculate(User $user, PayrollPeriod $period): bool
    {
        return $this->isHr($user) && ! $period->isFinal();
    }

    /** Finalisasi mengubah angka yang dibayarkan — hanya admin. */
    public function finalize(User $user, PayrollPeriod $period): bool
    {
        return $user->role === 'admin' && $period->isCalculated();
    }

    public function delete(User $user, PayrollPeriod $period): bool
    {
        return $user->role === 'admin' && ! $period->isFinal();
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

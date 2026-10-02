<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isHr($user);
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->isHr($user);
    }

    public function create(User $user): bool
    {
        return $this->isHr($user);
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->isHr($user);
    }

    /**
     * Menyetujui/menolak cuti hanya admin & hr-manager, dan hanya selama
     * pengajuan masih berstatus pending — keputusan yang sudah dibuat tidak
     * boleh ditimpa diam-diam.
     */
    public function decide(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->isHr($user) && $leaveRequest->status === LeaveRequest::STATUS_PENDING;
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->role === 'admin';
    }

    private function isHr(User $user): bool
    {
        return in_array($user->role, ['admin', 'hr-manager'], true);
    }
}

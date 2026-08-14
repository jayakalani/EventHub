<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use App\Models\UserRole;

class ComplaintPolicy
{
    public function view(User $user, Complaint $complaint): bool
    {
        return $this->isCro($user) && $complaint->isInCroQueue((int) $user->id);
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if (! $this->view($user, $complaint)) {
            return false;
        }

        if ($complaint->isGeneral() && ! $complaint->isUnassigned() && ! $complaint->isAssignedTo((int) $user->id)) {
            return false;
        }

        return true;
    }

    private function isCro(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::CRO;
    }
}

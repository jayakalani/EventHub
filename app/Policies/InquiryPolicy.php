<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;

class InquiryPolicy
{
    public function view(User $user, Inquiry $inquiry): bool
    {
        return $this->isCro($user) && $inquiry->isInCroQueue((int) $user->id);
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $this->view($user, $inquiry);
    }

    private function isCro(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::CRO;
    }
}

<?php

namespace App\Policies;

use App\Models\RefundRequest;
use App\Models\User;
use App\Models\UserRole;

class RefundRequestPolicy
{
    public function view(User $user, RefundRequest $refundRequest): bool
    {
        return $this->isCro($user) && $refundRequest->isInCroQueue((int) $user->id);
    }

    public function update(User $user, RefundRequest $refundRequest): bool
    {
        return $this->view($user, $refundRequest);
    }

    private function isCro(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::CRO;
    }
}

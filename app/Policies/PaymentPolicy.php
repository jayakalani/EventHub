<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isOrganizer() && $payment->isOwnedByOrganizer($user->id);
    }
}

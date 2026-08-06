<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, Rating $rating): bool
    {
        return $user->isOrganizer() && $rating->isOwnedByOrganizer($user->id);
    }
}

<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, Artist $artist): bool
    {
        return $this->owns($user, $artist);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function update(User $user, Artist $artist): bool
    {
        return $this->owns($user, $artist);
    }

    public function delete(User $user, Artist $artist): bool
    {
        return $this->owns($user, $artist);
    }

    public function toggleActive(User $user, Artist $artist): bool
    {
        return $this->owns($user, $artist);
    }

    private function owns(User $user, Artist $artist): bool
    {
        return $user->isOrganizer() && $artist->isOwnedByOrganizer($user->id);
    }
}

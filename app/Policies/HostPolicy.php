<?php

namespace App\Policies;

use App\Models\Host;
use App\Models\User;

class HostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, Host $host): bool
    {
        return $user->isOrganizer();
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function update(User $user, Host $host): bool
    {
        return $this->owns($user, $host);
    }

    public function delete(User $user, Host $host): bool
    {
        return $this->owns($user, $host);
    }

    public function toggleActive(User $user, Host $host): bool
    {
        return $this->owns($user, $host);
    }

    private function owns(User $user, Host $host): bool
    {
        return $user->isOrganizer() && $host->isOwnedByOrganizer($user->id);
    }
}

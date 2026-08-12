<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ticketCategory;

class TicketCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, ticketCategory $ticketCategory): bool
    {
        return $this->owns($user, $ticketCategory);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function update(User $user, ticketCategory $ticketCategory): bool
    {
        return $this->owns($user, $ticketCategory) && ! $ticketCategory->event?->isLocked();
    }

    public function delete(User $user, ticketCategory $ticketCategory): bool
    {
        return $this->owns($user, $ticketCategory) && ! $ticketCategory->event?->isLocked();
    }

    private function owns(User $user, ticketCategory $ticketCategory): bool
    {
        return $user->isOrganizer() && $ticketCategory->isOwnedByOrganizer($user->id);
    }
}

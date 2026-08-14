<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function update(User $user, Event $event): bool
    {
        return $this->owns($user, $event) && ! $event->isLocked();
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->owns($user, $event) && $event->canBeHardDeleted();
    }

    public function archive(User $user, Event $event): bool
    {
        return $this->owns($user, $event) && $event->canBeArchived();
    }

    public function restore(User $user, Event $event): bool
    {
        return $this->owns($user, $event) && $event->canBeRestored();
    }

    private function owns(User $user, Event $event): bool
    {
        return $user->isOrganizer() && $event->isOwnedByOrganizer($user->id);
    }
}

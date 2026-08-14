<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ticketBooking;

class TicketBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer() || $user->isCro();
    }

    public function view(User $user, ticketBooking $ticketBooking): bool
    {
        if ($user->isOrganizer()) {
            return $ticketBooking->isOwnedByOrganizer($user->id);
        }

        if ($user->isCro()) {
            return $ticketBooking->isAssignedToCro($user->id);
        }

        return false;
    }

    public function checkIn(User $user, ticketBooking $ticketBooking): bool
    {
        return $this->view($user, $ticketBooking) && $ticketBooking->canCheckIn();
    }

    public function undoCheckIn(User $user, ticketBooking $ticketBooking): bool
    {
        return false;
    }
}

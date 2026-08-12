<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ticketBooking;

class TicketBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function view(User $user, ticketBooking $ticketBooking): bool
    {
        return $user->isOrganizer() && $ticketBooking->isOwnedByOrganizer($user->id);
    }

    public function checkIn(User $user, ticketBooking $ticketBooking): bool
    {
        return $this->view($user, $ticketBooking) && $ticketBooking->canCheckIn();
    }

    public function undoCheckIn(User $user, ticketBooking $ticketBooking): bool
    {
        return $this->view($user, $ticketBooking) && $ticketBooking->canUndoCheckIn();
    }
}

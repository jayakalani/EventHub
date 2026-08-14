<?php

namespace App\Services;

use App\Models\ticketBooking;
use Illuminate\Support\Facades\DB;

class TicketCheckInService
{
    /**
     * Atomically mark a ticket as checked in.
     *
     * @return string|null Error message when check-in did not apply; null on success
     */
    public function markCheckedIn(ticketBooking $booking, int $userId): ?string
    {
        return DB::transaction(function () use ($booking, $userId) {
            $locked = ticketBooking::query()
                ->lockForUpdate()
                ->find($booking->id);

            if (! $locked) {
                return 'Ticket not found.';
            }

            if ($locked->isCheckedIn()) {
                $booking->refresh();

                return 'This ticket was already checked in'
                    .($locked->checked_in_at ? ' at '.$locked->checked_in_at->format('M d, Y H:i') : '')
                    .'.';
            }

            if (! $locked->canCheckIn()) {
                $booking->refresh();

                return $locked->checkInIneligibilityReason() ?? 'This ticket cannot be checked in.';
            }

            $locked->forceFill([
                'checked_in_at' => now(),
                'checked_in_by' => $userId,
            ])->save();

            $booking->refresh();

            return null;
        });
    }
}

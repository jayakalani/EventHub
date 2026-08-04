<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\Event;
use App\Models\PostponementAlertDismissal;
use App\Models\ticketBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostponementAlertService
{
    /**
     * Postponed events where the attendee still has unresolved tickets
     * (not kept and not refunded for the current postponement).
     *
     * @return Collection<int, Event>
     */
    public function undismissedAlertsFor(User $user): Collection
    {
        $events = Event::query()
            ->where('status', Event::STATUS_POSTPONED)
            ->whereNotNull('postponed_at')
            ->whereHas('ticketBookings', function ($query) use ($user) {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', BookingStatusEnum::Confirmed);
            })
            ->with(['host'])
            ->orderByDesc('postponed_at')
            ->get();

        $unresolved = $events
            ->filter(fn (Event $event) => $this->hasUnresolvedTicketsForEvent($user, $event))
            ->values();

        // Fully resolved (all pre-postponement tickets kept or refunded) — stop showing on future logins.
        $events
            ->reject(fn (Event $event) => $unresolved->contains(fn (Event $item) => $item->id === $event->id))
            ->filter(fn (Event $event) => $event->hasPrePostponementPurchaseBy($user))
            ->each(fn (Event $event) => $this->dismiss($user, [$event->id]));

        return $unresolved
            ->reject(function (Event $event) use ($user) {
                return PostponementAlertDismissal::query()
                    ->where('user_id', $user->id)
                    ->where('event_id', $event->id)
                    ->where('postponed_at', $event->postponed_at)
                    ->exists();
            })
            ->values();
    }

    public function hasUnresolvedTicketsForEvent(User $user, Event $event): bool
    {
        if (! $event->isPostponed() || ! $event->postponed_at) {
            return false;
        }

        $bookings = ticketBooking::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('created_at', '<=', $event->postponed_at)
            ->get();

        if ($bookings->isEmpty()) {
            return false;
        }

        foreach ($bookings as $booking) {
            $booking->setRelation('event', $event);

            if (! $booking->hasKeptCurrentPostponement()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a ticket as kept for the current postponement.
     * When all tickets for that event are resolved, dismiss the login alert.
     */
    public function keepTicket(User $user, ticketBooking $booking): void
    {
        $booking->loadMissing('event', 'refundRequest');

        if ((int) $booking->user_id !== (int) $user->id) {
            throw new RuntimeException('This ticket does not belong to you.');
        }

        if (! $booking->isPostponementRefundable()) {
            throw new RuntimeException('This ticket cannot be kept for the current postponement.');
        }

        DB::transaction(function () use ($user, $booking) {
            $booking->update([
                'postponement_kept_for' => $booking->event->postponed_at,
            ]);

            $this->dismissEventIfFullyResolved($user, $booking->event->fresh());
        });
    }

    public function dismissEventIfFullyResolved(User $user, Event $event): void
    {
        if (! $event->isPostponed() || ! $event->postponed_at) {
            return;
        }

        if ($this->hasUnresolvedTicketsForEvent($user, $event)) {
            return;
        }

        $this->dismiss($user, [$event->id]);
    }

    /**
     * Permanently dismiss postponement alerts for the given event IDs.
     *
     * @param  list<int>  $eventIds
     */
    public function dismiss(User $user, array $eventIds): void
    {
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));

        if ($eventIds === []) {
            return;
        }

        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->where('status', Event::STATUS_POSTPONED)
            ->whereNotNull('postponed_at')
            ->get();

        DB::transaction(function () use ($user, $events) {
            foreach ($events as $event) {
                PostponementAlertDismissal::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'postponed_at' => $event->postponed_at,
                ]);
            }
        });
    }
}

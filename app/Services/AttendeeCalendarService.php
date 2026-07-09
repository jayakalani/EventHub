<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class AttendeeCalendarService
{
    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            'upcoming' => '#3b82f6',
            'ongoing' => '#10b981',
            'completed' => '#64748b',
            'cancelled' => '#f43f5e',
        ];
    }

    /**
     * @return Builder<Event>
     */
    public function bookedEventsQuery(int $userId): Builder
    {
        return Event::query()
            ->bookedByUser($userId)
            ->visibleToAttendees()
            ->with(['host', 'eventCategory'])
            ->withCount([
                'ticketBookings as user_ticket_count' => fn ($query) => $query
                    ->where('user_id', $userId)
                    ->where('status', BookingStatusEnum::Confirmed),
            ]);
    }

    /**
     * @return Collection<int, Event>
     */
    public function getUpcomingBookedEvents(int $userId): Collection
    {
        return $this->bookedEventsQuery($userId)
            ->whereNotIn('status', [Event::STATUS_COMPLETED, Event::STATUS_CANCELLED])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    public function getPastBookedEvents(int $userId): Collection
    {
        return $this->bookedEventsQuery($userId)
            ->where(function (Builder $query) {
                $query
                    ->where('status', Event::STATUS_COMPLETED)
                    ->orWhere('status', Event::STATUS_CANCELLED);
            })
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function formatForCalendar(int $userId): SupportCollection
    {
        return $this->bookedEventsQuery($userId)
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(fn (Event $event) => $this->toCalendarEntry($event));
    }

    /**
     * @return array<string, mixed>
     */
    public function toCalendarEntry(Event $event): array
    {
        $displayStatus = $event->calendarDisplayStatus();
        $color = self::statusColors()[$displayStatus];

        return [
            'id' => $event->id,
            'title' => $event->name,
            'start' => $event->startsAt()->toIso8601String(),
            'url' => route('attendee.events.show', $event),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'status' => $displayStatus,
                'statusLabel' => ucfirst($displayStatus),
                'place' => $event->place,
                'host' => $event->host?->name,
                'ticketCount' => (int) ($event->user_ticket_count ?? 0),
            ],
        ];
    }
}

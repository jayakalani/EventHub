<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class OrganizerCalendarService
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
            'unpublished' => '#f59e0b',
            'postponed' => '#ea580c',
        ];
    }

    /**
     * @return Builder<Event>
     */
    public function eventsQuery(int $organizerId): Builder
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->with(['host', 'eventCategory'])
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query
                    ->where('status', BookingStatusEnum::Confirmed),
            ]);
    }

    /**
     * @return Collection<int, Event>
     */
    public function getUpcomingEvents(int $organizerId): Collection
    {
        return $this->eventsQuery($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING, Event::STATUS_POSTPONED])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    public function getPastEvents(int $organizerId): Collection
    {
        return $this->eventsQuery($organizerId)
            ->whereIn('status', [Event::STATUS_COMPLETED, Event::STATUS_CANCELLED])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    public function getDraftEvents(int $organizerId): Collection
    {
        return $this->eventsQuery($organizerId)
            ->where('status', Event::STATUS_UNPUBLISHED)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();
    }

    /**
     * All organizer events for the FullCalendar widget.
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function formatForCalendar(int $organizerId): SupportCollection
    {
        return $this->eventsQuery($organizerId)
            ->whereNotNull('date')
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
        $status = $event->status;
        $color = self::statusColors()[$status] ?? self::statusColors()['upcoming'];

        return [
            'id' => $event->id,
            'title' => $event->name,
            'start' => $event->startsAt()->toIso8601String(),
            'url' => route('organizer.events.show', $event),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'status' => $status,
                'statusLabel' => $event->isPostponed() ? 'POSTPONED' : ucfirst($status),
                'place' => $event->place,
                'host' => $event->host?->name,
                'ticketCount' => (int) ($event->tickets_sold ?? 0),
                'capacity' => (int) $event->total_tickets,
            ],
        ];
    }
}

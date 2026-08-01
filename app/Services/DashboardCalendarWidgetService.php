<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class DashboardCalendarWidgetService
{
    /**
     * @return array{
     *     title: string,
     *     subtitle: string,
     *     today: string,
     *     initialMonth: string,
     *     calendarUrl: string|null,
     *     createUrl: string|null,
     *     statusColors: array<string, string>,
     *     events: list<array<string, mixed>>
     * }
     */
    public function forOrganizer(int $organizerId): array
    {
        return $this->build(
            title: 'Event Calendar',
            subtitle: 'Your schedule at a glance',
            eventsQuery: Event::query()->createdByOrganizer($organizerId),
            eventUrlResolver: fn (Event $event) => route('organizer.events.show', $event),
            calendarUrl: route('organizer.calendar.index'),
            createUrl: route('organizer.events.create'),
            includeOrganizerName: false,
        );
    }

    /**
     * @return array{
     *     title: string,
     *     subtitle: string,
     *     today: string,
     *     initialMonth: string,
     *     calendarUrl: string|null,
     *     createUrl: string|null,
     *     statusColors: array<string, string>,
     *     events: list<array<string, mixed>>
     * }
     */
    public function forAdmin(): array
    {
        return $this->build(
            title: 'Platform Calendar',
            subtitle: 'Upcoming events across organizers',
            eventsQuery: Event::query()->with('organizer'),
            eventUrlResolver: function (Event $event) {
                if (in_array($event->status, [Event::STATUS_UNPUBLISHED, Event::STATUS_CANCELLED], true)) {
                    return null;
                }

                return route('attendee.events.show', $event);
            },
            calendarUrl: null,
            createUrl: null,
            includeOrganizerName: true,
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Event>  $eventsQuery
     * @param  callable(Event): (?string)  $eventUrlResolver
     * @return array{
     *     title: string,
     *     subtitle: string,
     *     today: string,
     *     initialMonth: string,
     *     calendarUrl: string|null,
     *     createUrl: string|null,
     *     statusColors: array<string, string>,
     *     events: list<array<string, mixed>>
     * }
     */
    private function build(
        string $title,
        string $subtitle,
        $eventsQuery,
        callable $eventUrlResolver,
        ?string $calendarUrl,
        ?string $createUrl,
        bool $includeOrganizerName,
    ): array {
        $start = now()->subMonthsNoOverflow(1)->startOfMonth()->startOfDay();
        $end = now()->addMonthsNoOverflow(2)->endOfMonth()->endOfDay();

        $events = $eventsQuery
            ->whereNotNull('date')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('time')
            ->limit(200)
            ->get()
            ->map(function (Event $event) use ($eventUrlResolver, $includeOrganizerName) {
                $date = $event->date ? Carbon::parse($event->date)->toDateString() : null;
                $time = $event->time ? Carbon::parse($event->time)->format('g:i A') : null;

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $date,
                    'time' => $time,
                    'place' => $event->place,
                    'status' => $event->status,
                    'statusLabel' => ucfirst((string) $event->status),
                    'organizer' => $includeOrganizerName
                        ? ($event->organizer?->full_name ?? 'Unknown')
                        : null,
                    'url' => $eventUrlResolver($event),
                    'color' => OrganizerCalendarService::statusColors()[$event->status]
                        ?? OrganizerCalendarService::statusColors()['upcoming'],
                ];
            })
            ->filter(fn (array $event) => filled($event['date']))
            ->values()
            ->all();

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'today' => now()->toDateString(),
            'initialMonth' => now()->format('Y-m'),
            'calendarUrl' => $calendarUrl,
            'createUrl' => $createUrl,
            'statusColors' => OrganizerCalendarService::statusColors(),
            'events' => $events,
        ];
    }
}

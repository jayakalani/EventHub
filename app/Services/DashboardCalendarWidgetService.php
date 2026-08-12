<?php

namespace App\Services;

use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\RefundRequest;
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
    public function forCro(?int $croId = null): array
    {
        $payload = $this->build(
            title: 'Support Calendar',
            subtitle: 'Event dates with support activity',
            eventsQuery: Event::query()
                ->visibleToAttendees()
                ->where('status', '!=', Event::STATUS_CANCELLED)
                ->when($croId, fn ($q) => $q->where('contact_person', $croId))
                ->with('organizer'),
            eventUrlResolver: fn (Event $event) => route('cro.dashboard', ['event' => $event->id]),
            calendarUrl: null,
            createUrl: null,
            includeOrganizerName: true,
        );

        $eventIds = collect($payload['events'])->pluck('id')->filter()->all();
        if ($eventIds === []) {
            return $payload;
        }

        $openInquiries = Inquiry::query()
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])
            ->selectRaw('event_id, COUNT(*) as count')
            ->groupBy('event_id')
            ->pluck('count', 'event_id');

        $pendingRefunds = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->whereHas('ticketBooking', fn ($q) => $q->whereIn('event_id', $eventIds))
            ->with('ticketBooking:id,event_id')
            ->get()
            ->countBy(fn (RefundRequest $refund) => $refund->ticketBooking?->event_id);

        $payload['events'] = collect($payload['events'])
            ->map(function (array $event) use ($openInquiries, $pendingRefunds) {
                $inquiries = (int) ($openInquiries[$event['id']] ?? 0);
                $refunds = (int) ($pendingRefunds[$event['id']] ?? 0);
                $supportTotal = $inquiries + $refunds;

                $metaParts = [];
                if ($inquiries > 0) {
                    $metaParts[] = $inquiries.' open inquir'.($inquiries === 1 ? 'y' : 'ies');
                }
                if ($refunds > 0) {
                    $metaParts[] = $refunds.' pending refund'.($refunds === 1 ? '' : 's');
                }

                $event['supportCount'] = $supportTotal;
                $event['place'] = $metaParts !== []
                    ? implode(' · ', $metaParts).($event['place'] ? ' · '.$event['place'] : '')
                    : $event['place'];

                return $event;
            })
            ->values()
            ->all();

        return $payload;
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

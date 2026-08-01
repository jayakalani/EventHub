<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Host;
use App\Models\Like;
use App\Models\Rating;
use App\Models\SavedEvent;
use App\Models\ticketBooking;
use App\Models\User;
use App\Notifications\LowTicketInventoryNotification;
use Carbon\Carbon;

class OrganizerDashboardService
{
    private const LOW_INVENTORY_PERCENT = 15;

    private const LOW_INVENTORY_ABSOLUTE = 10;

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(
        int $organizerId,
        ?int $kpiEventId = null,
        ?int $goalEventId = null,
        ?int $chartEventId = null,
        ?int $engagementEventId = null,
    ): array {
        $reportService = app(OrganizerReportService::class);
        $sales = $reportService->getTicketSalesReport($organizerId);
        $revenue = $reportService->getRevenueReport($organizerId);

        $totalAttendees = (int) ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');

        $statusCounts = $this->statusCounts($organizerId);
        $performance = $this->eventPerformance($organizerId);
        $upcomingEvents = $this->upcomingEvents($organizerId);
        $recentPurchases = $this->recentPurchases($organizerId);
        $recentActivity = $this->recentActivity($organizerId, $recentPurchases);
        $todaySummary = $this->todaySummary($organizerId);
        $nextUpcomingEvent = $this->nextUpcomingEvent($organizerId);
        $kpiFilter = $this->kpiEventFilter($organizerId, $kpiEventId);
        $kpis = $kpiFilter['selectedEventId']
            ? $this->buildEventKpis($organizerId, $kpiFilter['selectedEventId'])
            : $this->buildKpis($organizerId, [
                'totalEvents' => $sales['totalEvents'],
                'ticketsSold' => $sales['totalTicketsSold'],
                'grossRevenue' => $revenue['grossRevenue'],
            ]);
        $revenueGoal = $this->revenueGoal($organizerId, $goalEventId);
        $chartFilter = $this->kpiEventFilter($organizerId, $chartEventId);
        $engagementFilter = $this->kpiEventFilter($organizerId, $engagementEventId);
        $engagement = $this->engagementInsights($organizerId, $engagementFilter['selectedEventId']);
        $engagement['filter'] = $engagementFilter;

        return [
            'stats' => [
                'totalEvents' => $sales['totalEvents'],
                'upcomingEvents' => ($statusCounts['upcoming'] ?? 0) + ($statusCounts['ongoing'] ?? 0),
                'completedEvents' => $statusCounts['completed'] ?? 0,
                'unpublishedEvents' => $statusCounts['unpublished'] ?? 0,
                'cancelledEvents' => $statusCounts['cancelled'] ?? 0,
                'totalHosts' => Host::where('created_by', $organizerId)->count(),
                'totalAttendees' => $totalAttendees,
                'ticketsSold' => $sales['totalTicketsSold'],
                'grossRevenue' => $revenue['grossRevenue'],
                'netRevenue' => $revenue['netRevenue'],
                'totalRefunded' => $revenue['totalRefunded'],
            ],
            'todaySummary' => $todaySummary,
            'kpiFilter' => $kpiFilter,
            'kpis' => $kpis,
            'revenueGoal' => $revenueGoal,
            'engagement' => $engagement,
            'statusSummary' => [
                ['key' => 'upcoming', 'label' => 'Upcoming', 'count' => $statusCounts['upcoming'] ?? 0, 'color' => 'emerald'],
                ['key' => 'ongoing', 'label' => 'Ongoing', 'count' => $statusCounts['ongoing'] ?? 0, 'color' => 'blue'],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $statusCounts['completed'] ?? 0, 'color' => 'slate'],
                ['key' => 'unpublished', 'label' => 'Unpublished', 'count' => $statusCounts['unpublished'] ?? 0, 'color' => 'amber'],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'count' => $statusCounts['cancelled'] ?? 0, 'color' => 'rose'],
            ],
            'chartFilter' => $chartFilter,
            'charts' => $this->buildChartPeriods($organizerId, $chartFilter['selectedEventId']),
            'performance' => $performance,
            'upcomingEvents' => $upcomingEvents,
            'nextUpcomingEvent' => $nextUpcomingEvent,
            'recentPurchases' => $recentPurchases,
            'recentActivity' => $recentActivity,
            'miniCalendar' => app(DashboardCalendarWidgetService::class)->forOrganizer($organizerId),
        ];
    }

    /**
     * @return array{defaultPeriod: string, periods: array<string, array<string, mixed>>}
     */
    private function buildChartPeriods(int $organizerId, ?int $eventId = null): array
    {
        return [
            'defaultPeriod' => 'month',
            'periods' => [
                'week' => $this->chartPeriodPayload(
                    $organizerId,
                    'This Week',
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek(),
                    'day',
                    $eventId,
                ),
                'month' => $this->chartPeriodPayload(
                    $organizerId,
                    'This Month',
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                    now()->subMonthNoOverflow()->startOfMonth(),
                    now()->subMonthNoOverflow()->endOfMonth(),
                    'day',
                    $eventId,
                ),
                'year' => $this->chartPeriodPayload(
                    $organizerId,
                    'This Year',
                    now()->startOfYear(),
                    now()->endOfYear(),
                    now()->subYear()->startOfYear(),
                    now()->subYear(),
                    'month',
                    $eventId,
                ),
            ],
        ];
    }

    /**
     * @return array{label: string, revenue: array<string, mixed>, tickets: array<string, mixed>}
     */
    private function chartPeriodPayload(
        int $organizerId,
        string $label,
        Carbon $currentStart,
        Carbon $currentEnd,
        Carbon $previousStart,
        Carbon $previousEnd,
        string $bucket,
        ?int $eventId = null,
    ): array {
        $currentRevenueSeries = $this->bookingSeries($organizerId, $currentStart, $currentEnd, $bucket, 'sum', $eventId);
        $currentTicketSeries = $this->bookingSeries($organizerId, $currentStart, $currentEnd, $bucket, 'count', $eventId);

        $currentRevenueTotal = array_sum($currentRevenueSeries['values']);
        $currentTicketTotal = array_sum($currentTicketSeries['values']);

        $previousRevenueTotal = (float) $this->organizerConfirmedBookings($organizerId, $eventId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('ticket_price');

        $previousTicketTotal = (int) $this->organizerConfirmedBookings($organizerId, $eventId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        return [
            'label' => $label,
            'revenue' => [
                'labels' => $currentRevenueSeries['labels'],
                'series' => $currentRevenueSeries['values'],
                'total' => round($currentRevenueTotal, 2),
                'totalFormatted' => 'LKR '.number_format($currentRevenueTotal, 0),
                'changePercent' => $this->percentChange($currentRevenueTotal, $previousRevenueTotal),
                'up' => $currentRevenueTotal >= $previousRevenueTotal,
            ],
            'tickets' => [
                'labels' => $currentTicketSeries['labels'],
                'series' => $currentTicketSeries['values'],
                'total' => $currentTicketTotal,
                'totalFormatted' => number_format($currentTicketTotal),
                'changePercent' => $this->percentChange($currentTicketTotal, $previousTicketTotal),
                'up' => $currentTicketTotal >= $previousTicketTotal,
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float|int>}
     */
    private function bookingSeries(
        int $organizerId,
        Carbon $start,
        Carbon $end,
        string $bucket,
        string $aggregate,
        ?int $eventId = null,
    ): array {
        $effectiveEnd = $end->copy()->min(now()->endOfDay());

        if ($bucket === 'month') {
            $keys = [];
            $labels = [];
            $cursor = $start->copy()->startOfMonth();
            $last = $effectiveEnd->copy()->startOfMonth();

            while ($cursor <= $last) {
                $keys[] = $cursor->format('Y-m');
                $labels[] = $cursor->format('M');
                $cursor->addMonth();
            }

            $rows = $this->organizerConfirmedBookings($organizerId, $eventId)
                ->whereBetween('created_at', [$start, $effectiveEnd])
                ->selectRaw(
                    $aggregate === 'sum'
                        ? "DATE_FORMAT(created_at, '%Y-%m') as bucket, SUM(ticket_price) as total"
                        : "DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as total"
                )
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            return [
                'labels' => $labels,
                'values' => collect($keys)
                    ->map(fn (string $key) => $aggregate === 'sum'
                        ? round((float) ($rows[$key] ?? 0), 2)
                        : (int) ($rows[$key] ?? 0))
                    ->values()
                    ->all(),
            ];
        }

        $keys = [];
        $labels = [];
        $cursor = $start->copy()->startOfDay();
        $last = $effectiveEnd->copy()->startOfDay();

        while ($cursor <= $last) {
            $keys[] = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('D j');
            $cursor->addDay();
        }

        $rows = $this->organizerConfirmedBookings($organizerId, $eventId)
            ->whereBetween('created_at', [$start, $effectiveEnd])
            ->selectRaw(
                $aggregate === 'sum'
                    ? "DATE(created_at) as bucket, SUM(ticket_price) as total"
                    : "DATE(created_at) as bucket, COUNT(*) as total"
            )
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return [
            'labels' => $labels,
            'values' => collect($keys)
                ->map(fn (string $key) => $aggregate === 'sum'
                    ? round((float) ($rows[$key] ?? 0), 2)
                    : (int) ($rows[$key] ?? 0))
                ->values()
                ->all(),
        ];
    }

    private function organizerConfirmedBookings(int $organizerId, ?int $eventId = null)
    {
        $query = ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Confirmed);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        return $query;
    }

    private function percentChange(float|int $current, float|int $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    /**
     * @return array{eventsToday: int, ticketsSold: int, revenue: float}
     */
    private function todaySummary(int $organizerId): array
    {
        $today = now()->toDateString();

        $eventsToday = Event::query()
            ->createdByOrganizer($organizerId)
            ->whereDate('date', $today)
            ->whereNotIn('status', [Event::STATUS_CANCELLED, Event::STATUS_UNPUBLISHED])
            ->count();

        $todaysBookingsQuery = fn () => ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereDate('created_at', $today);

        return [
            'eventsToday' => $eventsToday,
            'ticketsSold' => (int) $todaysBookingsQuery()->count(),
            'revenue' => (float) $todaysBookingsQuery()->sum('ticket_price'),
        ];
    }

    /**
     * @param  array{totalEvents: int, ticketsSold: int, grossRevenue: float}  $stats
     * @return list<array<string, mixed>>
     */
    private function buildKpis(int $organizerId, array $stats): array
    {
        $bookingsQuery = fn () => ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Confirmed);

        $thisMonthRevenue = (float) $bookingsQuery()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ticket_price');

        $lastMonthRevenue = (float) $bookingsQuery()
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('ticket_price');

        $revenuePercent = $this->percentChange($thisMonthRevenue, $lastMonthRevenue);

        $eventsThisMonth = Event::query()
            ->createdByOrganizer($organizerId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $ticketsToday = (int) $bookingsQuery()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $likesQuery = fn () => Like::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId));

        $totalLikes = (int) $likesQuery()->count();
        $likesToday = (int) $likesQuery()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return [
            [
                'key' => 'revenue',
                'label' => 'Total Revenue',
                'emoji' => '💰',
                'value' => 'LKR '.number_format($stats['grossRevenue'], 0),
                'trendValue' => $revenuePercent,
                'trendLabel' => abs($revenuePercent).'%',
                'trendHint' => 'Compared with last month',
                'trendUp' => $revenuePercent >= 0,
                'showTrend' => true,
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'key' => 'events',
                'label' => 'Total Events',
                'emoji' => '📅',
                'value' => number_format($stats['totalEvents']),
                'trendValue' => $eventsThisMonth,
                'trendLabel' => (string) $eventsThisMonth,
                'trendHint' => 'Added this month',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-calendar-event',
                'accent' => 'indigo',
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets Sold',
                'emoji' => '🎟',
                'value' => number_format($stats['ticketsSold']),
                'trendValue' => $ticketsToday,
                'trendLabel' => (string) $ticketsToday,
                'trendHint' => 'Sold today',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-ticket-perforated',
                'accent' => 'blue',
            ],
            [
                'key' => 'followers',
                'label' => 'Followers',
                'emoji' => '❤️',
                'value' => number_format($totalLikes),
                'trendValue' => $likesToday,
                'trendLabel' => (string) $likesToday,
                'trendHint' => 'New today',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-heart-fill',
                'accent' => 'rose',
            ],
        ];
    }

    /**
     * @return array{selectedEventId: int|null, selectedEventName: string|null, events: list<array{id: int, name: string}>}
     */
    private function kpiEventFilter(int $organizerId, ?int $kpiEventId): array
    {
        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
            ])
            ->values()
            ->all();

        $selectedEventId = null;
        $selectedEventName = null;

        if ($kpiEventId) {
            $selected = collect($events)->firstWhere('id', $kpiEventId);
            if ($selected) {
                $selectedEventId = $selected['id'];
                $selectedEventName = $selected['name'];
            }
        }

        return [
            'selectedEventId' => $selectedEventId,
            'selectedEventName' => $selectedEventName,
            'events' => $events,
        ];
    }

    /**
     * Whole-event KPIs for a single selected event (no monthly comparisons).
     *
     * @return list<array<string, mixed>>
     */
    private function buildEventKpis(int $organizerId, int $eventId): array
    {
        $event = Event::query()
            ->createdByOrganizer($organizerId)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
                'likes',
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ], 'ticket_price')
            ->find($eventId);

        if (! $event) {
            return $this->buildKpis($organizerId, [
                'totalEvents' => 0,
                'ticketsSold' => 0,
                'grossRevenue' => 0,
            ]);
        }

        $ticketsSold = (int) $event->tickets_sold;
        $capacity = (int) $event->total_tickets;
        $remaining = max(0, $capacity - $ticketsSold);
        $fillRate = $capacity > 0 ? round(($ticketsSold / $capacity) * 100, 1) : 0;
        $revenue = (float) ($event->revenue ?? 0);
        $attendees = (int) ticketBooking::query()
            ->where('event_id', $event->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');
        $likes = (int) $event->likes_count;

        return [
            [
                'key' => 'revenue',
                'label' => 'Event Revenue',
                'emoji' => '💰',
                'value' => 'LKR '.number_format($revenue, 0),
                'trendValue' => 0,
                'trendLabel' => '',
                'trendHint' => 'Whole event total',
                'trendUp' => true,
                'showTrend' => false,
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets Sold',
                'emoji' => '🎟',
                'value' => number_format($ticketsSold).($capacity > 0 ? ' / '.number_format($capacity) : ''),
                'trendValue' => $fillRate,
                'trendLabel' => $fillRate.'%',
                'trendHint' => 'Fill rate',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-ticket-perforated',
                'accent' => 'blue',
            ],
            [
                'key' => 'attendees',
                'label' => 'Attendees',
                'emoji' => '👥',
                'value' => number_format($attendees),
                'trendValue' => $remaining,
                'trendLabel' => (string) $remaining,
                'trendHint' => 'Tickets remaining',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-people',
                'accent' => 'indigo',
            ],
            [
                'key' => 'followers',
                'label' => 'Likes',
                'emoji' => '❤️',
                'value' => number_format($likes),
                'trendValue' => 0,
                'trendLabel' => '',
                'trendHint' => 'Whole event total',
                'trendUp' => true,
                'showTrend' => false,
                'icon' => 'bi-heart-fill',
                'accent' => 'rose',
            ],
        ];
    }

    /**
     * Push low-inventory alerts into the organizer's notification inbox (nav bell).
     */
    public function syncLowInventoryNotifications(int $organizerId): void
    {
        $organizer = User::find($organizerId);

        if (! $organizer) {
            return;
        }

        foreach ($this->lowInventoryAlerts($organizerId) as $alert) {
            $this->notifyOrganizerIfNeeded($organizer, $alert);
        }
    }

    /**
     * @param  list<int>  $eventIds
     */
    public function notifyLowInventoryForEvents(array $eventIds): void
    {
        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->with('organizer')
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->get();

        foreach ($events as $event) {
            $capacity = (int) $event->total_tickets;
            $sold = (int) $event->tickets_sold;
            $remaining = max(0, $capacity - $sold);

            if (! $this->isLowInventory($event->status, $capacity, $remaining)) {
                continue;
            }

            $organizer = $event->organizer;

            if (! $organizer) {
                continue;
            }

            $this->notifyOrganizerIfNeeded($organizer, [
                'id' => $event->id,
                'name' => $event->name,
                'remaining' => $remaining,
                'capacity' => $capacity,
                'fill_rate' => $capacity > 0 ? round(($sold / $capacity) * 100, 1) : 0,
            ]);
        }
    }

    /**
     * @param  array{id: int, name: string, remaining: int, capacity: int, fill_rate: float|int}  $alert
     */
    private function notifyOrganizerIfNeeded(User $organizer, array $alert): void
    {
        $alreadyNotified = $organizer->unreadNotifications()
            ->where('type', LowTicketInventoryNotification::class)
            ->where('data->event_id', $alert['id'])
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $event = Event::find($alert['id']);

        if (! $event) {
            return;
        }

        $organizer->notify(new LowTicketInventoryNotification(
            $event,
            (int) $alert['remaining'],
            (int) $alert['capacity'],
            (float) $alert['fill_rate'],
        ));
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventPerformance(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->with('host')
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ], 'ticket_price')
            ->orderByDesc('date')
            ->limit(10)
            ->get()
            ->map(function (Event $event) {
                $capacity = (int) $event->total_tickets;
                $sold = (int) $event->tickets_sold;
                $remaining = max(0, $capacity - $sold);
                $fillRate = $capacity > 0 ? round(($sold / $capacity) * 100, 1) : 0;

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $event->date ? Carbon::parse($event->date)->format('M d, Y') : '—',
                    'raw_date' => $event->date,
                    'time' => $event->time ? Carbon::parse($event->time)->format('g:i A') : null,
                    'place' => $event->place,
                    'host' => $event->host?->name,
                    'status' => $event->status,
                    'capacity' => $capacity,
                    'sold' => $sold,
                    'remaining' => $remaining,
                    'fill_rate' => $fillRate,
                    'revenue' => round((float) ($event->revenue ?? 0), 2),
                    'is_low_inventory' => $this->isLowInventory($event->status, $capacity, $remaining),
                    'url' => route('organizer.events.show', $event),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingEvents(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->with('host')
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->limit(6)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date ? Carbon::parse($event->date)->format('D, M d') : '—',
                'day' => $event->date ? Carbon::parse($event->date)->format('d') : '—',
                'month' => $event->date ? Carbon::parse($event->date)->format('M') : '—',
                'time' => $event->time ? Carbon::parse($event->time)->format('g:i A') : null,
                'place' => $event->place,
                'host' => $event->host?->name,
                'status' => $event->status,
                'sold' => (int) $event->tickets_sold,
                'capacity' => (int) $event->total_tickets,
                'url' => route('organizer.events.show', $event),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nextUpcomingEvent(int $organizerId): ?array
    {
        $event = Event::query()
            ->createdByOrganizer($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->with(['ticketCategories' => fn ($query) => $query->where('is_active', true)->orderBy('ticket_price')])
            ->orderBy('date')
            ->orderBy('time')
            ->first();

        if (! $event) {
            return null;
        }

        $eventDate = $event->date ? Carbon::parse($event->date)->startOfDay() : null;
        $dayLabel = 'Upcoming';

        if ($eventDate) {
            if ($eventDate->isToday()) {
                $dayLabel = 'Today';
            } elseif ($eventDate->isTomorrow()) {
                $dayLabel = 'Tomorrow';
            } else {
                $dayLabel = $eventDate->format('D, M j');
            }
        }

        $categories = $event->ticketCategories
            ->map(fn ($category) => [
                'name' => $category->name,
                'remaining' => max(0, (int) $category->no_of_available_tickets),
                'capacity' => (int) $category->no_of_tickets,
                'color' => $category->ticket_color ?: '#6366f1',
            ])
            ->values()
            ->all();

        if ($categories === []) {
            $capacity = (int) $event->total_tickets;
            $sold = (int) $event->ticketBookings()
                ->where('status', BookingStatusEnum::Confirmed)
                ->count();

            $categories = [[
                'name' => 'General',
                'remaining' => max(0, $capacity - $sold),
                'capacity' => $capacity,
                'color' => '#6366f1',
            ]];
        }

        return [
            'id' => $event->id,
            'name' => $event->name,
            'day_label' => $dayLabel,
            'date' => $event->date ? Carbon::parse($event->date)->format('M d, Y') : '—',
            'time' => $event->time ? Carbon::parse($event->time)->format('g:i A') : 'Time TBD',
            'place' => $event->place,
            'status' => $event->status,
            'categories' => $categories,
            'url' => route('organizer.events.show', $event),
            'manage_url' => route('organizer.events.show', $event),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentPurchases(int $organizerId): array
    {
        $bookings = ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->with(['user', 'event', 'ticketCategory'])
            ->where('status', BookingStatusEnum::Confirmed)
            ->latest()
            ->limit(40)
            ->get();

        return $bookings
            ->groupBy(fn (ticketBooking $booking) => $booking->payment_id
                ? 'payment-'.$booking->payment_id
                : 'booking-'.$booking->id)
            ->take(8)
            ->map(function ($group) {
                /** @var ticketBooking $first */
                $first = $group->first();

                $categoryBadges = $group
                    ->groupBy(fn (ticketBooking $booking) => $booking->ticket_category_id ?? 'general')
                    ->map(function ($items) {
                        /** @var ticketBooking $item */
                        $item = $items->first();
                        $category = $item->ticketCategory;
                        $count = $items->count();
                        $name = $category?->name ?? 'General';

                        return [
                            'name' => $name,
                            'label' => $name.($count > 1 ? ' ×'.$count : ''),
                            'count' => $count,
                            'color' => $category?->ticket_color ?: '#6366f1',
                        ];
                    })
                    ->values()
                    ->all();

                $categoryLines = collect($categoryBadges)->pluck('label')->all();

                return [
                    'id' => $first->id,
                    'buyer' => $first->user?->full_name ?? 'Unknown',
                    'email' => $first->user?->email ?? '—',
                    'event' => $first->event?->name ?? '—',
                    'event_id' => $first->event_id,
                    'category' => $categoryLines[0] ?? 'General',
                    'categories' => $categoryLines,
                    'category_badges' => $categoryBadges,
                    'quantity' => $group->count(),
                    'amount' => round((float) $group->sum('ticket_price'), 2),
                    'booked_at' => $first->created_at?->diffForHumans() ?? '—',
                    'booked_raw' => $first->created_at?->toIso8601String(),
                    'url' => $first->event_id
                        ? route('organizer.events.show', $first->event_id)
                        : route('organizer.events.index'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function engagementInsights(int $organizerId, ?int $eventId = null): array
    {
        $scope = function ($query) use ($organizerId, $eventId) {
            $query->createdByOrganizer($organizerId);

            if ($eventId) {
                $query->where('id', $eventId);
            }
        };

        $likes = (int) Like::query()->whereHas('event', $scope)->count();
        $saves = (int) SavedEvent::query()->whereHas('event', $scope)->count();
        $comments = (int) Comment::query()->whereHas('event', $scope)->count();

        $ratingsQuery = Rating::query()->whereHas('event', $scope);
        $reviewsCount = (int) (clone $ratingsQuery)->count();
        $averageRating = $reviewsCount > 0
            ? round((float) (clone $ratingsQuery)->avg('score'), 1)
            : null;

        return [
            'likes' => $likes,
            'saves' => $saves,
            'comments' => $comments,
            'average_rating' => $averageRating,
            'reviews_count' => $reviewsCount,
            'url' => route('organizer.reports', ['tab' => 'engagement']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function revenueGoal(int $organizerId, ?int $goalEventId = null): array
    {
        $filter = $this->kpiEventFilter($organizerId, $goalEventId);

        if ($filter['selectedEventId']) {
            return $this->eventRevenueGoal($organizerId, $filter);
        }

        $organizer = User::find($organizerId);

        $thisMonthRevenue = (float) $this->organizerConfirmedBookings($organizerId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ticket_price');

        $lastMonthRevenue = (float) $this->organizerConfirmedBookings($organizerId)
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('ticket_price');

        $suggestedGoal = max(50000, (int) (ceil(($lastMonthRevenue * 1.15) / 5000) * 5000));
        if ($suggestedGoal < $thisMonthRevenue) {
            $suggestedGoal = (int) (ceil(($thisMonthRevenue * 1.1) / 5000) * 5000);
        }

        $goal = (float) ($organizer?->monthly_revenue_goal ?: $suggestedGoal);
        $progress = $goal > 0 ? min(100, round(($thisMonthRevenue / $goal) * 100, 1)) : 0;
        $remaining = max(0, $goal - $thisMonthRevenue);

        return [
            'mode' => 'monthly',
            'selectedEventId' => null,
            'selectedEventName' => null,
            'events' => $filter['events'],
            'goal' => $goal,
            'current' => $thisMonthRevenue,
            'remaining' => $remaining,
            'progress' => $progress,
            'label' => now()->format('F Y'),
            'description' => 'Track progress toward your monthly sales target across all events.',
            'is_custom' => $organizer?->monthly_revenue_goal !== null,
            'suggested' => $suggestedGoal,
            'achieved' => $thisMonthRevenue >= $goal && $goal > 0,
        ];
    }

    /**
     * @param  array{selectedEventId: int|null, selectedEventName: string|null, events: list<array{id: int, name: string}>}  $filter
     * @return array<string, mixed>
     */
    private function eventRevenueGoal(int $organizerId, array $filter): array
    {
        $event = Event::query()
            ->createdByOrganizer($organizerId)
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ], 'ticket_price')
            ->find($filter['selectedEventId']);

        $currentRevenue = (float) ($event?->revenue ?? 0);
        $customGoal = $event?->revenue_goal !== null ? (float) $event->revenue_goal : null;

        $suggestedGoal = max(10000, (int) (ceil(($currentRevenue * 1.25) / 5000) * 5000));
        if ($suggestedGoal <= $currentRevenue) {
            $suggestedGoal = (int) (ceil((($currentRevenue + 10000) * 1.1) / 5000) * 5000);
        }

        $goal = $customGoal ?? (float) $suggestedGoal;
        $progress = $goal > 0 ? min(100, round(($currentRevenue / $goal) * 100, 1)) : 0;
        $remaining = max(0, $goal - $currentRevenue);

        return [
            'mode' => 'event',
            'selectedEventId' => $filter['selectedEventId'],
            'selectedEventName' => $filter['selectedEventName'],
            'events' => $filter['events'],
            'goal' => $goal,
            'current' => $currentRevenue,
            'remaining' => $remaining,
            'progress' => $progress,
            'label' => $filter['selectedEventName'] ?? 'Event',
            'description' => 'Track progress toward this event\'s sales target.',
            'is_custom' => $customGoal !== null,
            'suggested' => $suggestedGoal,
            'achieved' => $currentRevenue >= $goal && $goal > 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lowInventoryAlerts(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->orderBy('date')
            ->get()
            ->map(function (Event $event) {
                $capacity = (int) $event->total_tickets;
                $sold = (int) $event->tickets_sold;
                $remaining = max(0, $capacity - $sold);
                $fillRate = $capacity > 0 ? round(($sold / $capacity) * 100, 1) : 0;

                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'remaining' => $remaining,
                    'capacity' => $capacity,
                    'sold' => $sold,
                    'fill_rate' => $fillRate,
                    'date' => $event->date ? Carbon::parse($event->date)->format('M d, Y') : '—',
                    'url' => route('organizer.events.show', $event),
                    'is_low_inventory' => $this->isLowInventory($event->status, $capacity, $remaining),
                ];
            })
            ->filter(fn (array $event) => $event['is_low_inventory'])
            ->map(fn (array $event) => [
                'id' => $event['id'],
                'name' => $event['name'],
                'remaining' => $event['remaining'],
                'capacity' => $event['capacity'],
                'sold' => $event['sold'],
                'fill_rate' => $event['fill_rate'],
                'date' => $event['date'],
                'url' => $event['url'],
                'message' => $event['remaining'] === 0
                    ? 'Sold out — no tickets remaining.'
                    : sprintf(
                        'Only %d of %d tickets left (%s%% sold).',
                        $event['remaining'],
                        $event['capacity'],
                        $event['fill_rate']
                    ),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $recentPurchases
     * @return list<array<string, mixed>>
     */
    private function recentActivity(int $organizerId, array $recentPurchases): array
    {
        $eventIds = Event::query()
            ->createdByOrganizer($organizerId)
            ->pluck('id');

        $hostIds = Host::query()
            ->where('created_by', $organizerId)
            ->pluck('id');

        $auditItems = AuditLog::query()
            ->where('user_id', $organizerId)
            ->where(function ($query) use ($eventIds, $hostIds) {
                $query->where(function ($eventQuery) use ($eventIds) {
                    $eventQuery->where('model_type', Event::class)
                        ->whereIn('model_id', $eventIds);
                })->orWhere(function ($hostQuery) use ($hostIds) {
                    $hostQuery->where('model_type', Host::class)
                        ->whereIn('model_id', $hostIds);
                });
            })
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (AuditLog $log) {
                $isEvent = $log->model_type === Event::class;
                $label = $isEvent ? 'Event' : 'Host';
                $name = $this->resolveAuditSubjectName($log);
                $action = strtolower((string) $log->action);

                [$icon, $color, $title] = match ($action) {
                    'created' => ['bi-plus-circle-fill', 'violet', "{$label} Created"],
                    'deleted' => ['bi-trash-fill', 'rose', "{$label} Deleted"],
                    default => ['bi-pencil-square', 'blue', "{$label} Updated"],
                };

                return [
                    'type' => $action === 'created' ? 'created' : ($action === 'deleted' ? 'deleted' : 'updated'),
                    'icon' => $icon,
                    'color' => $color,
                    'title' => $title,
                    'description' => $name,
                    'time' => $log->created_at?->diffForHumans(),
                    'timestamp' => $log->created_at?->timestamp ?? 0,
                    'url' => $isEvent && $log->model_id
                        ? route('organizer.events.show', $log->model_id)
                        : ($isEvent ? route('organizer.events.index') : route('organizer.hosts')),
                ];
            });

        $purchaseItems = collect($recentPurchases)
            ->take(6)
            ->map(fn (array $purchase) => [
                'type' => 'purchase',
                'icon' => 'bi-ticket-perforated-fill',
                'color' => 'emerald',
                'title' => 'Ticket Purchased',
                'description' => "{$purchase['buyer']} · {$purchase['event']}",
                'time' => $purchase['booked_at'],
                'timestamp' => isset($purchase['booked_raw'])
                    ? Carbon::parse($purchase['booked_raw'])->timestamp
                    : 0,
                'url' => $purchase['url'],
            ]);

        $refundItems = ticketBooking::query()
            ->with(['user', 'event'])
            ->where('status', BookingStatusEnum::Refunded)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (ticketBooking $booking) {
                $buyer = $booking->user?->full_name ?? 'Attendee';
                $eventName = $booking->event?->name ?? 'Event';

                return [
                    'type' => 'refund',
                    'icon' => 'bi-arrow-counterclockwise',
                    'color' => 'amber',
                    'title' => 'Ticket Refunded',
                    'description' => "{$buyer} · {$eventName}",
                    'time' => $booking->updated_at?->diffForHumans(),
                    'timestamp' => $booking->updated_at?->timestamp ?? 0,
                    'url' => $booking->event_id
                        ? route('organizer.events.show', $booking->event_id)
                        : route('organizer.events.index'),
                ];
            });

        return $auditItems
            ->concat($purchaseItems)
            ->concat($refundItems)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    private function resolveAuditSubjectName(AuditLog $log): string
    {
        $values = $log->new_values;

        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [];
        }

        if (is_array($values) && ! empty($values['name'])) {
            return (string) $values['name'];
        }

        return class_basename($log->model_type).' #'.$log->model_id;
    }

    private function isLowInventory(string $status, int $capacity, int $remaining): bool
    {
        if (! in_array($status, [Event::STATUS_UPCOMING, Event::STATUS_ONGOING], true)) {
            return false;
        }

        if ($capacity <= 0 || $remaining <= 0) {
            return $capacity > 0 && $remaining <= 0;
        }

        $threshold = max(
            self::LOW_INVENTORY_ABSOLUTE,
            (int) ceil($capacity * (self::LOW_INVENTORY_PERCENT / 100))
        );

        return $remaining <= $threshold;
    }
}

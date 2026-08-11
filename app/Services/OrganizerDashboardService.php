<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\Artist;
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
     * @param  array{kpi?: bool, goal?: bool, chart?: bool, engagement?: bool}  $overrideFlags
     * @param  array<string, int|string>  $filterQuery
     * @return array<string, mixed>
     */
    public function getDashboardData(
        int $organizerId,
        ?int $kpiEventId = null,
        ?int $goalEventId = null,
        ?int $chartEventId = null,
        ?int $engagementEventId = null,
        ?int $focusEventId = null,
        array $overrideFlags = [],
        array $filterQuery = [],
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
        $dayOfOps = $this->dayOfOps($organizerId);
        $nextUpcomingEvent = $this->nextUpcomingEvent($organizerId);
        $focusFilter = $this->kpiEventFilter($organizerId, $focusEventId);
        $kpiFilter = $this->kpiEventFilter($organizerId, $kpiEventId);
        $kpiFilter['isOverride'] = (bool) ($overrideFlags['kpi'] ?? false);
        $kpis = $kpiFilter['selectedEventId']
            ? $this->buildEventKpis($organizerId, $kpiFilter['selectedEventId'])
            : $this->buildKpis($organizerId, [
                'totalEvents' => $sales['totalEvents'],
                'ticketsSold' => $sales['totalTicketsSold'],
                'grossRevenue' => $revenue['grossRevenue'],
                'netRevenue' => $revenue['netRevenue'],
                'totalRefunded' => $revenue['totalRefunded'],
            ]);
        $revenueGoal = $this->revenueGoal($organizerId, $goalEventId);
        $revenueGoal['isOverride'] = (bool) ($overrideFlags['goal'] ?? false);
        $chartFilter = $this->kpiEventFilter($organizerId, $chartEventId);
        $chartFilter['isOverride'] = (bool) ($overrideFlags['chart'] ?? false);
        $engagementFilter = $this->kpiEventFilter($organizerId, $engagementEventId);
        $engagementFilter['isOverride'] = (bool) ($overrideFlags['engagement'] ?? false);
        $engagement = $this->engagementInsights($organizerId, $engagementFilter['selectedEventId']);
        $engagement['filter'] = $engagementFilter;
        $needsAttention = $this->needsAttention($organizerId);
        $onboarding = $this->onboardingChecklist($organizerId);

        return [
            'stats' => [
                'totalEvents' => $sales['totalEvents'],
                'upcomingEvents' => ($statusCounts['upcoming'] ?? 0) + ($statusCounts['ongoing'] ?? 0),
                'completedEvents' => $statusCounts['completed'] ?? 0,
                'unpublishedEvents' => $statusCounts['unpublished'] ?? 0,
                'cancelledEvents' => $statusCounts['cancelled'] ?? 0,
                'postponedEvents' => $statusCounts['postponed'] ?? 0,
                'totalHosts' => Host::where('created_by', $organizerId)->count(),
                'totalArtists' => Artist::where('created_by', $organizerId)->count(),
                'totalAttendees' => $totalAttendees,
                'ticketsSold' => $sales['totalTicketsSold'],
                'grossRevenue' => $revenue['grossRevenue'],
                'netRevenue' => $revenue['netRevenue'],
                'totalRefunded' => $revenue['totalRefunded'],
            ],
            'todaySummary' => $todaySummary,
            'dayOfOps' => $dayOfOps,
            'needsAttention' => $needsAttention,
            'onboarding' => $onboarding,
            'focusFilter' => $focusFilter,
            'filterQuery' => $filterQuery,
            'kpiFilter' => $kpiFilter,
            'kpis' => $kpis,
            'revenueGoal' => $revenueGoal,
            'engagement' => $engagement,
            'statusSummary' => [
                ['key' => 'upcoming', 'label' => 'Upcoming', 'count' => $statusCounts['upcoming'] ?? 0, 'color' => 'emerald'],
                ['key' => 'ongoing', 'label' => 'Ongoing', 'count' => $statusCounts['ongoing'] ?? 0, 'color' => 'blue'],
                ['key' => 'postponed', 'label' => 'Postponed', 'count' => $statusCounts['postponed'] ?? 0, 'color' => 'orange'],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $statusCounts['completed'] ?? 0, 'color' => 'slate'],
                ['key' => 'unpublished', 'label' => 'Unpublished', 'count' => $statusCounts['unpublished'] ?? 0, 'color' => 'amber'],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'count' => $statusCounts['cancelled'] ?? 0, 'color' => 'rose'],
            ],
            'chartFilter' => $chartFilter,
            'charts' => $this->buildChartPeriods($organizerId, $chartFilter['selectedEventId']),
            'performance' => $performance['active'],
            'performanceCompleted' => $performance['completed'],
            'upcomingEvents' => $upcomingEvents,
            'nextUpcomingEvent' => $nextUpcomingEvent,
            'recentPurchases' => $recentPurchases,
            'recentActivity' => $recentActivity,
            'miniCalendar' => app(DashboardCalendarWidgetService::class)->forOrganizer($organizerId),
            'livePulseUrl' => route('organizer.dashboard.live'),
        ];
    }

    /**
     * Lightweight payload for live dashboard polling (today + recent sales).
     *
     * @return array<string, mixed>
     */
    public function getLivePulse(int $organizerId): array
    {
        $todaySummary = $this->todaySummary($organizerId);
        $dayOfOps = $this->dayOfOps($organizerId);

        return [
            'todaySummary' => $todaySummary,
            'dayOfOps' => [
                'active' => $dayOfOps['active'],
                'checked_in' => $dayOfOps['checked_in'],
                'sold' => $dayOfOps['sold'],
                'rate' => $dayOfOps['rate'],
                'count' => $dayOfOps['count'],
            ],
            'recentPurchases' => $this->recentPurchases($organizerId),
            'refreshed_at' => now()->toIso8601String(),
            'refreshed_label' => now()->format('g:i:s A'),
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
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses());

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
     * Door-day check-in ops for events happening today.
     *
     * @return array{
     *     active: bool,
     *     count: int,
     *     checked_in: int,
     *     sold: int,
     *     rate: float,
     *     scan_url: string,
     *     guest_list_url: string,
     *     events: list<array<string, mixed>>
     * }
     */
    private function dayOfOps(int $organizerId): array
    {
        $today = now()->toDateString();

        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->whereDate('date', $today)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->whereIn(
                    'status',
                    BookingStatusEnum::retainedSaleStatuses()
                ),
                'ticketBookings as checked_in' => fn ($query) => $query
                    ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
                    ->whereNotNull('checked_in_at'),
            ])
            ->orderBy('time')
            ->orderBy('id')
            ->get(['id', 'name', 'time', 'place', 'status']);

        $eventRows = $events->map(function (Event $event) {
            $sold = (int) $event->tickets_sold;
            $checkedIn = (int) $event->checked_in;
            $awaiting = max(0, $sold - $checkedIn);
            $rate = $sold > 0 ? round(($checkedIn / $sold) * 100, 1) : 0.0;

            return [
                'id' => $event->id,
                'name' => $event->name,
                'time' => $event->time ? Carbon::parse($event->time)->format('g:i A') : 'Time TBD',
                'place' => $event->place,
                'status' => $event->status,
                'sold' => $sold,
                'checked_in' => $checkedIn,
                'awaiting' => $awaiting,
                'rate' => $rate,
                'url' => route('organizer.events.show', $event),
                'scan_url' => route('organizer.bookings.scan', ['event_id' => $event->id]),
                'guest_list_url' => route('organizer.bookings.index', ['event_id' => $event->id]),
            ];
        })->values()->all();

        $sold = (int) collect($eventRows)->sum('sold');
        $checkedIn = (int) collect($eventRows)->sum('checked_in');
        $rate = $sold > 0 ? round(($checkedIn / $sold) * 100, 1) : 0.0;

        $primaryEventId = $eventRows[0]['id'] ?? null;

        return [
            'active' => $eventRows !== [],
            'count' => count($eventRows),
            'checked_in' => $checkedIn,
            'sold' => $sold,
            'rate' => $rate,
            'scan_url' => $primaryEventId
                ? route('organizer.bookings.scan', ['event_id' => $primaryEventId])
                : route('organizer.bookings.scan'),
            'guest_list_url' => $primaryEventId
                ? route('organizer.bookings.index', ['event_id' => $primaryEventId])
                : route('organizer.bookings.index'),
            'events' => $eventRows,
        ];
    }

    /**
     * @param  array{
     *     totalEvents: int,
     *     ticketsSold: int,
     *     grossRevenue: float,
     *     netRevenue?: float,
     *     totalRefunded?: float
     * }  $stats
     * @return list<array<string, mixed>>
     */
    private function buildKpis(int $organizerId, array $stats): array
    {
        $bookingsQuery = fn () => ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses());

        $thisMonthRevenue = (float) $bookingsQuery()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ticket_price');

        $lastMonthRevenue = (float) $bookingsQuery()
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('ticket_price');

        $thisMonthRefunded = (float) ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Refunded)
            ->where('updated_at', '>=', now()->startOfMonth())
            ->sum('ticket_price');

        $lastMonthRefunded = (float) ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('status', BookingStatusEnum::Refunded)
            ->whereBetween('updated_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('ticket_price');

        $thisMonthNet = $thisMonthRevenue - $thisMonthRefunded;
        $lastMonthNet = $lastMonthRevenue - $lastMonthRefunded;
        $netPercent = $this->percentChange($thisMonthNet, $lastMonthNet);

        $netRevenue = (float) ($stats['netRevenue'] ?? (($stats['grossRevenue'] ?? 0) - ($stats['totalRefunded'] ?? 0)));
        $totalRefunded = (float) ($stats['totalRefunded'] ?? 0);

        $ticketsToday = (int) $bookingsQuery()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $inventory = $this->activeInventorySnapshot($organizerId);
        $checkInsToday = $this->checkInsToday($organizerId);

        return [
            [
                'key' => 'net_revenue',
                'label' => 'Net Revenue',
                'emoji' => '💰',
                'value' => 'LKR '.number_format($netRevenue, 0),
                'trendValue' => $netPercent,
                'trendLabel' => abs($netPercent).'%',
                'trendHint' => $totalRefunded > 0
                    ? 'LKR '.number_format($totalRefunded, 0).' refunded · vs last month'
                    : 'Compared with last month',
                'trendUp' => $netPercent >= 0,
                'showTrend' => true,
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'key' => 'inventory',
                'label' => 'Fill Rate',
                'emoji' => '📊',
                'value' => $inventory['capacity'] > 0
                    ? $inventory['fill_rate'].'%'
                    : '—',
                'trendValue' => $inventory['remaining'],
                'trendLabel' => number_format($inventory['remaining']),
                'trendHint' => $inventory['capacity'] > 0
                    ? 'Tickets remaining on live events'
                    : 'No live event inventory',
                'trendUp' => $inventory['remaining'] > 0,
                'showTrend' => true,
                'icon' => 'bi-pie-chart-fill',
                'accent' => $inventory['remaining'] > 0 && $inventory['remaining'] <= 10 ? 'rose' : 'indigo',
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
                'key' => 'check_ins',
                'label' => 'Check-ins Today',
                'emoji' => '✅',
                'value' => number_format($checkInsToday['checked_in']),
                'trendValue' => $checkInsToday['awaiting'],
                'trendLabel' => number_format($checkInsToday['awaiting']),
                'trendHint' => $checkInsToday['sold'] > 0
                    ? 'Still awaiting entry'
                    : ($checkInsToday['events_today'] > 0 ? 'No sold tickets yet' : 'No events today'),
                'trendUp' => $checkInsToday['awaiting'] === 0 && $checkInsToday['checked_in'] > 0,
                'showTrend' => true,
                'icon' => 'bi-person-check-fill',
                'accent' => 'cyan',
            ],
        ];
    }

    /**
     * Fill rate / remaining inventory across upcoming and ongoing events.
     *
     * @return array{capacity: int, sold: int, remaining: int, fill_rate: float}
     */
    private function activeInventorySnapshot(int $organizerId): array
    {
        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->whereIn(
                    'status',
                    BookingStatusEnum::retainedSaleStatuses()
                ),
            ])
            ->get(['id', 'total_tickets']);

        $capacity = (int) $events->sum(fn (Event $event) => (int) $event->total_tickets);
        $sold = (int) $events->sum(fn (Event $event) => (int) $event->tickets_sold);
        $remaining = max(0, $capacity - $sold);
        $fillRate = $capacity > 0 ? round(($sold / $capacity) * 100, 1) : 0.0;

        return [
            'capacity' => $capacity,
            'sold' => $sold,
            'remaining' => $remaining,
            'fill_rate' => $fillRate,
        ];
    }

    /**
     * @return array{checked_in: int, awaiting: int, sold: int, events_today: int}
     */
    private function checkInsToday(int $organizerId): array
    {
        $today = now()->toDateString();

        $eventsToday = Event::query()
            ->createdByOrganizer($organizerId)
            ->whereDate('date', $today)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->pluck('id');

        if ($eventsToday->isEmpty()) {
            // Still count any check-ins recorded today (door ops after midnight edge cases).
            $checkedIn = (int) ticketBooking::query()
                ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
                ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
                ->whereDate('checked_in_at', $today)
                ->count();

            return [
                'checked_in' => $checkedIn,
                'awaiting' => 0,
                'sold' => 0,
                'events_today' => 0,
            ];
        }

        $soldQuery = ticketBooking::query()
            ->whereIn('event_id', $eventsToday)
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses());

        $sold = (int) (clone $soldQuery)->count();
        $checkedIn = (int) (clone $soldQuery)->whereNotNull('checked_in_at')->count();

        return [
            'checked_in' => $checkedIn,
            'awaiting' => max(0, $sold - $checkedIn),
            'sold' => $sold,
            'events_today' => $eventsToday->count(),
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
                'ticketBookings as tickets_sold' => fn ($query) => $query->whereIn(
                    'status',
                    BookingStatusEnum::retainedSaleStatuses()
                ),
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->whereIn(
                    'status',
                    BookingStatusEnum::retainedSaleStatuses()
                ),
            ], 'ticket_price')
            ->find($eventId);

        if (! $event) {
            return $this->buildKpis($organizerId, [
                'totalEvents' => 0,
                'ticketsSold' => 0,
                'grossRevenue' => 0,
                'netRevenue' => 0,
                'totalRefunded' => 0,
            ]);
        }

        $ticketsSold = (int) $event->tickets_sold;
        $capacity = (int) $event->total_tickets;
        $remaining = max(0, $capacity - $ticketsSold);
        $fillRate = $capacity > 0 ? round(($ticketsSold / $capacity) * 100, 1) : 0;
        $revenue = (float) ($event->revenue ?? 0);
        $refunded = (float) ticketBooking::query()
            ->where('event_id', $event->id)
            ->where('status', BookingStatusEnum::Refunded)
            ->sum('ticket_price');
        $netRevenue = max(0, $revenue - $refunded);
        $checkedIn = (int) ticketBooking::query()
            ->where('event_id', $event->id)
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
            ->whereNotNull('checked_in_at')
            ->count();
        $awaiting = max(0, $ticketsSold - $checkedIn);

        return [
            [
                'key' => 'net_revenue',
                'label' => 'Net Revenue',
                'emoji' => '💰',
                'value' => 'LKR '.number_format($netRevenue, 0),
                'trendValue' => $refunded,
                'trendLabel' => $refunded > 0 ? 'LKR '.number_format($refunded, 0) : '',
                'trendHint' => $refunded > 0 ? 'Refunded on this event' : 'Whole event total',
                'trendUp' => true,
                'showTrend' => $refunded > 0,
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'key' => 'inventory',
                'label' => 'Fill Rate',
                'emoji' => '📊',
                'value' => $capacity > 0 ? $fillRate.'%' : '—',
                'trendValue' => $remaining,
                'trendLabel' => number_format($remaining),
                'trendHint' => 'Tickets remaining',
                'trendUp' => $remaining > 0,
                'showTrend' => true,
                'icon' => 'bi-pie-chart-fill',
                'accent' => $remaining > 0 && $remaining <= 10 ? 'rose' : 'indigo',
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets Sold',
                'emoji' => '🎟',
                'value' => number_format($ticketsSold).($capacity > 0 ? ' / '.number_format($capacity) : ''),
                'trendValue' => $fillRate,
                'trendLabel' => $capacity > 0 ? number_format($ticketsSold).' sold' : (string) $ticketsSold,
                'trendHint' => 'Confirmed tickets',
                'trendUp' => true,
                'showTrend' => true,
                'icon' => 'bi-ticket-perforated',
                'accent' => 'blue',
            ],
            [
                'key' => 'check_ins',
                'label' => 'Checked In',
                'emoji' => '✅',
                'value' => number_format($checkedIn).($ticketsSold > 0 ? ' / '.number_format($ticketsSold) : ''),
                'trendValue' => $awaiting,
                'trendLabel' => number_format($awaiting),
                'trendHint' => $ticketsSold > 0 ? 'Still awaiting entry' : 'No sold tickets yet',
                'trendUp' => $awaiting === 0 && $checkedIn > 0,
                'showTrend' => true,
                'icon' => 'bi-person-check-fill',
                'accent' => 'cyan',
            ],
        ];
    }

    /**
     * Actionable items the organizer should address soon.
     *
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    private function needsAttention(int $organizerId): array
    {
        $items = [];

        foreach ($this->lowInventoryAlerts($organizerId) as $alert) {
            $soldOut = (int) $alert['remaining'] === 0;

            $items[] = [
                'key' => 'low_inventory_'.$alert['id'],
                'type' => 'low_inventory',
                'severity' => $soldOut ? 1 : 2,
                'accent' => $soldOut ? 'rose' : 'amber',
                'icon' => $soldOut ? 'bi-x-octagon-fill' : 'bi-exclamation-triangle-fill',
                'badge' => $soldOut ? 'Sold out' : 'Low inventory',
                'title' => $alert['name'],
                'message' => $alert['message'],
                'meta' => $alert['date'],
                'cta' => 'Manage tickets',
                'url' => $alert['url'],
            ];
        }

        $postponedTba = Event::query()
            ->createdByOrganizer($organizerId)
            ->where('status', Event::STATUS_POSTPONED)
            ->where('date_tba', true)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'updated_at']);

        foreach ($postponedTba as $event) {
            $items[] = [
                'key' => 'postponed_tba_'.$event->id,
                'type' => 'postponed_tba',
                'severity' => 3,
                'accent' => 'orange',
                'icon' => 'bi-calendar-x-fill',
                'badge' => 'Date TBA',
                'title' => $event->name,
                'message' => 'Postponed — set a new date so attendees know when to return.',
                'meta' => $event->updated_at?->diffForHumans() ?? '—',
                'cta' => 'Set schedule',
                'url' => route('organizer.events.show', $event),
            ];
        }

        $unpublished = Event::query()
            ->createdByOrganizer($organizerId)
            ->where('status', Event::STATUS_UNPUBLISHED)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'status', 'updated_at', 'date', 'date_tba']);

        foreach ($unpublished as $event) {
            $items[] = [
                'key' => 'unpublished_'.$event->id,
                'type' => 'unpublished',
                'severity' => 4,
                'accent' => 'slate',
                'icon' => 'bi-eye-slash-fill',
                'badge' => 'Draft',
                'title' => $event->name,
                'message' => 'Unpublished draft — publish when ready for ticket sales.',
                'meta' => $event->hasDateYetToBeScheduled()
                    ? 'Schedule TBA'
                    : ($event->date ? Carbon::parse($event->date)->format('M d, Y') : 'No date set'),
                'cta' => 'Review draft',
                'url' => route('organizer.events.show', $event),
            ];
        }

        $sorted = collect($items)
            ->sortBy([
                ['severity', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->take(12)
            ->all();

        return [
            'count' => count($sorted),
            'items' => $sorted,
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
     * @return array{active: list<array<string, mixed>>, completed: list<array<string, mixed>>}
     */
    private function eventPerformance(int $organizerId): array
    {
        $mapEvent = function (Event $event) {
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
        };

        $baseQuery = fn () => Event::query()
            ->createdByOrganizer($organizerId)
            ->with('host')
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ], 'ticket_price');

        $active = $baseQuery()
            ->whereIn('status', [
                Event::STATUS_ONGOING,
                Event::STATUS_UPCOMING,
                Event::STATUS_POSTPONED,
                Event::STATUS_UNPUBLISHED,
            ])
            ->orderByRaw("CASE status
                WHEN 'ongoing' THEN 0
                WHEN 'upcoming' THEN 1
                WHEN 'postponed' THEN 2
                WHEN 'unpublished' THEN 3
                ELSE 4 END")
            ->orderBy('date')
            ->orderBy('time')
            ->limit(10)
            ->get()
            ->map($mapEvent)
            ->values()
            ->all();

        $completed = $baseQuery()
            ->whereIn('status', [Event::STATUS_COMPLETED, Event::STATUS_CANCELLED])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map($mapEvent)
            ->values()
            ->all();

        return [
            'active' => $active,
            'completed' => $completed,
        ];
    }

    /**
     * Setup checklist for organizers who have not finished publishing.
     *
     * @return array{show: bool, completed_count: int, total: int, steps: list<array<string, mixed>>}
     */
    private function onboardingChecklist(int $organizerId): array
    {
        $hasHost = Host::query()->where('created_by', $organizerId)->exists();

        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->withCount('ticketCategories')
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'status']);

        $hasEvent = $events->isNotEmpty();
        $eventWithCategories = $events->first(fn (Event $event) => (int) $event->ticket_categories_count > 0);
        $hasTicketCategories = $eventWithCategories !== null;
        $publishedEvent = $events->first(fn (Event $event) => $event->status !== Event::STATUS_UNPUBLISHED);
        $hasPublished = $publishedEvent !== null;
        $draftForCategories = $events->first(fn (Event $event) => $event->status === Event::STATUS_UNPUBLISHED)
            ?? $events->first();

        $steps = [
            [
                'key' => 'host',
                'label' => 'Create host',
                'description' => 'Add the venue or brand hosting your events',
                'done' => $hasHost,
                'cta' => 'Add host',
                'url' => route('organizer.host.create'),
            ],
            [
                'key' => 'event',
                'label' => 'Create event',
                'description' => 'Set the name, date, and details',
                'done' => $hasEvent,
                'cta' => 'Create event',
                'url' => route('organizer.events.create'),
                'locked' => ! $hasHost,
            ],
            [
                'key' => 'tickets',
                'label' => 'Add ticket categories',
                'description' => 'Define prices and inventory for sale',
                'done' => $hasTicketCategories,
                'cta' => 'Add tickets',
                'url' => $draftForCategories
                    ? route('organizer.ticket-categories.create', $draftForCategories)
                    : route('organizer.events.create'),
                'locked' => ! $hasEvent,
            ],
            [
                'key' => 'publish',
                'label' => 'Publish',
                'description' => 'Make an event visible so attendees can buy tickets',
                'done' => $hasPublished,
                'cta' => 'Publish event',
                'url' => $draftForCategories
                    ? route('organizer.events.show', $draftForCategories)
                    : route('organizer.events.index'),
                'locked' => ! $hasTicketCategories,
            ],
        ];

        $completedCount = collect($steps)->where('done', true)->count();
        $total = count($steps);

        return [
            'show' => $completedCount < $total,
            'completed_count' => $completedCount,
            'total' => $total,
            'steps' => $steps,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingEvents(int $organizerId): array
    {
        return Event::query()
            ->createdByOrganizer($organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING, Event::STATUS_POSTPONED])
            ->with('host')
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->orderByRaw("CASE WHEN status = 'postponed' AND date_tba = 1 THEN 1 ELSE 0 END")
            ->orderBy('date')
            ->orderBy('time')
            ->limit(6)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->hasDateYetToBeScheduled()
                    ? 'Date Yet To Be Scheduled'
                    : ($event->date ? Carbon::parse($event->date)->format('D, M d') : '—'),
                'day' => $event->hasDateYetToBeScheduled()
                    ? '—'
                    : ($event->date ? Carbon::parse($event->date)->format('d') : '—'),
                'month' => $event->hasDateYetToBeScheduled()
                    ? 'TBA'
                    : ($event->date ? Carbon::parse($event->date)->format('M') : '—'),
                'time' => $event->hasDateYetToBeScheduled()
                    ? null
                    : ($event->time ? Carbon::parse($event->time)->format('g:i A') : null),
                'place' => $event->place,
                'host' => $event->host?->name,
                'status' => $event->status,
                'date_tba' => $event->hasDateYetToBeScheduled(),
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
        return app(OrganizerSalesService::class)->recentPurchases($organizerId, 8);
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

        $artistIds = Artist::query()
            ->where('created_by', $organizerId)
            ->pluck('id');

        $auditItems = AuditLog::query()
            ->where('user_id', $organizerId)
            ->where(function ($query) use ($eventIds, $hostIds, $artistIds) {
                $query->where(function ($eventQuery) use ($eventIds) {
                    $eventQuery->where('model_type', Event::class)
                        ->whereIn('model_id', $eventIds);
                })->orWhere(function ($hostQuery) use ($hostIds) {
                    $hostQuery->where('model_type', Host::class)
                        ->whereIn('model_id', $hostIds);
                })->orWhere(function ($artistQuery) use ($artistIds) {
                    $artistQuery->where('model_type', Artist::class)
                        ->whereIn('model_id', $artistIds);
                });
            })
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (AuditLog $log) {
                $isEvent = $log->model_type === Event::class;
                $isArtist = $log->model_type === Artist::class;
                $label = $isEvent ? 'Event' : ($isArtist ? 'Artist' : 'Host');
                $name = $this->resolveAuditSubjectName($log);
                $action = strtolower((string) $log->action);

                [$icon, $color, $title] = match (true) {
                    $action === 'created' => ['bi-plus-circle-fill', 'violet', "{$label} Created"],
                    $action === 'deleted' => ['bi-trash-fill', 'rose', "{$label} Deleted"],
                    str_contains($action, 'postponed') => ['bi-exclamation-triangle-fill', 'amber', 'Event Postponed'],
                    str_contains($action, 'rescheduled') => ['bi-calendar-event', 'orange', 'Event Rescheduled'],
                    str_contains($action, 'refund because of postponement') => ['bi-arrow-counterclockwise', 'amber', 'Postponement Refund'],
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
                        : ($isEvent
                            ? route('organizer.events.index')
                            : ($isArtist ? route('organizer.artists') : route('organizer.hosts'))),
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

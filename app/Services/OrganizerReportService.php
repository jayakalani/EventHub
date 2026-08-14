<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Models\CartItem;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventView;
use App\Models\Like;
use App\Models\Rating;
use App\Models\RefundRequest;
use App\Models\SavedEvent;
use App\Models\ticketBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizerReportService
{
    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getAllReports(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return array_merge(
            $this->getReportShell($organizerId, $filters),
            $this->getTabReports($organizerId, $filters, 'revenue'),
            $this->getTabReports($organizerId, $filters, 'tickets'),
            $this->getTabReports($organizerId, $filters, 'events'),
            $this->getTabReports($organizerId, $filters, 'attendance'),
            $this->getTabReports($organizerId, $filters, 'audience'),
            $this->getTabReports($organizerId, $filters, 'engagement'),
            $this->getTabReports($organizerId, $filters, 'activity'),
        );
    }

    /**
     * Shared meta + KPI strip data for the reports shell (always loaded).
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getReportShell(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $netRevenue = $this->periodNetRevenue($organizerId, $filters);
        $grossRevenue = (float) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->sum('ticket_price');
        $ticketsSold = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();
        $totalEvents = (int) $this->organizerEventsQuery($organizerId, $filters)->count();
        $eventsWithSales = (int) $this->organizerEventsQuery($organizerId, $filters)
            ->whereHas('ticketBookings', fn ($query) => $this->applyBookingFilters(
                $query->where('status', BookingStatusEnum::Confirmed),
                $filters
            ))
            ->count();
        $totalAttendees = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');
        $averageRating = $this->periodAverageRating($organizerId, $filters);
        $likes = (int) Like::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            })
            ->count();
        $saves = (int) SavedEvent::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            })
            ->count();

        return [
            'summaryTrends' => $this->getSummaryTrends($organizerId, $filters),
            'filterOptions' => $this->getFilterOptions($organizerId),
            'filters' => $filters,
            'chartLabels' => $this->monthLabels($filters),
            // Lightweight KPI-facing shapes so the strip renders without full tab payloads.
            'ticketSales' => [
                'totalTicketsSold' => $ticketsSold,
                'totalEvents' => $totalEvents,
                'eventsWithSales' => $eventsWithSales,
                'salesTrend' => [],
                'topSellingEvents' => [],
                'salesByEvent' => [],
            ],
            'revenue' => [
                'grossRevenue' => $grossRevenue,
                'totalRefunded' => round($grossRevenue - $netRevenue, 2),
                'netRevenue' => $netRevenue,
                'averagePerEvent' => 0,
                'revenueByEvent' => [],
                'revenueTrend' => [],
                'cumulativeRevenueTrend' => [],
                'refundTrend' => [],
                'topRevenueEvents' => [],
                'refundRate' => $grossRevenue > 0 ? round((($grossRevenue - $netRevenue) / $grossRevenue) * 100, 1) : 0.0,
                'refundCount' => 0,
            ],
            'attendees' => [
                'totalAttendees' => $totalAttendees,
                'confirmedBookings' => $ticketsSold,
                'totalBookings' => $ticketsSold,
                'confirmationRate' => 0,
                'repeatBuyers' => 0,
                'returningRate' => 0,
                'avgSpendPerAttendee' => 0,
                'avgTicketsPerAttendee' => 0,
                'newAttendees' => 0,
                'repeatAttendees' => 0,
                'demographics' => ['age' => [], 'gender' => [], 'location' => [], 'available' => ['age' => false, 'gender' => false, 'location' => false, 'any' => false]],
                'topCustomers' => [],
                'attendeesByEvent' => [],
            ],
            'engagement' => [
                'totalLikes' => $likes,
                'totalComments' => 0,
                'totalRatings' => 0,
                'totalSaves' => $saves,
                'averageRating' => $averageRating,
                'popularityByEvent' => [],
                'topEvents' => [],
                'engagementTrend' => ['likes' => [], 'comments' => [], 'ratings' => [], 'saves' => []],
                'engagementBeforeEvent' => ['labels' => [], 'likes' => [], 'comments' => [], 'saves' => [], 'ratings' => [], 'tickets' => []],
                'engagementVsSales' => [],
                'engagementBreakdown' => [],
                'reviewQuality' => [
                    'averageRating' => $averageRating,
                    'totalRatings' => 0,
                    'averageTrend' => [],
                    'countTrend' => [],
                    'distribution' => [],
                    'lowRatedEvents' => [],
                    'responseRate' => null,
                    'topRatedEvents' => [],
                ],
            ],
        ];
    }

    /**
     * Heavy payload for a single reports tab.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getTabReports(int $organizerId, array $filters, string $tab): array
    {
        $filters = $this->normalizeFilters($filters);
        $tab = $this->normalizeReportTab($tab);

        return match ($tab) {
            'revenue' => [
                'revenue' => $this->getRevenueReport($organizerId, $filters),
                'refundAnalytics' => $this->getRefundAnalytics($organizerId, $filters),
            ],
            'tickets' => [
                'ticketSales' => $this->getTicketSalesReport($organizerId, $filters),
                'salesByCategory' => $this->getSalesByCategory($organizerId, $filters),
                'ticketTypeTrend' => $this->getTicketTypeTrend($organizerId, $filters),
                'conversionFunnel' => $this->getConversionFunnel($organizerId, $filters),
                'salesVelocity' => $this->getSalesVelocityBeforeEvent($organizerId, $filters),
            ],
            'events' => [
                'eventPerformance' => $this->getEventPerformance($organizerId, $filters),
                'eventsByStatus' => $this->getEventsByStatus($organizerId, $filters),
                'eventComparison' => $this->getEventComparison($organizerId, $filters),
                'salesHeatmap' => $this->getSalesHeatmap($organizerId, $filters),
                'peakSalesHeatmap' => $this->getPeakSalesHeatmap($organizerId, $filters),
            ],
            'attendance' => [
                'attendance' => $this->getAttendanceReport($organizerId, $filters),
            ],
            'audience' => [
                'attendees' => $this->getAttendeeReport($organizerId, $filters),
            ],
            'engagement' => [
                'engagement' => $this->getEngagementReport($organizerId, $filters),
            ],
            'activity' => [
                'recentTransactions' => $this->getRecentTransactions($organizerId, $filters),
            ],
            default => [],
        };
    }

    public function normalizeReportTab(string $tab): string
    {
        $tab = strtolower(trim($tab));

        return match ($tab) {
            'sales' => 'tickets',
            'attendees' => 'audience',
            'overview' => 'revenue',
            default => $tab,
        };
    }

    /**
     * @return list<string>
     */
    public function reportTabs(): array
    {
        return ['revenue', 'tickets', 'events', 'attendance', 'audience', 'engagement', 'activity'];
    }

    /**
     * Period-over-period change for top summary cards.
     *
     * Uses the filtered date range when present; otherwise month-to-date vs last calendar month.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     netRevenue: array{percent: float, up: bool, label: string},
     *     ticketsSold: array{percent: float, up: bool, label: string},
     *     events: array{percent: float, up: bool, label: string},
     *     attendees: array{percent: float, up: bool, label: string},
     *     engagement: array{percent: float, up: bool, label: string}
     * }
     */
    public function getSummaryTrends(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $periods = $this->resolveTrendComparisonPeriods($filters);

        $base = [
            'event_id' => $filters['event_id'],
            'status' => $filters['status'],
        ];

        $current = array_merge($base, [
            'from' => $periods['currentFrom'],
            'to' => $periods['currentTo'],
        ]);
        $previous = array_merge($base, [
            'from' => $periods['previousFrom'],
            'to' => $periods['previousTo'],
        ]);
        $label = $periods['label'];

        $currentNet = $this->periodNetRevenue($organizerId, $current);
        $previousNet = $this->periodNetRevenue($organizerId, $previous);

        $currentTickets = (int) $this->organizerBookingsQuery($organizerId, $current)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();
        $previousTickets = (int) $this->organizerBookingsQuery($organizerId, $previous)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

        $currentEvents = (int) $this->organizerEventsQuery($organizerId, $base)
            ->whereDate('created_at', '>=', $periods['currentFrom'])
            ->whereDate('created_at', '<=', $periods['currentTo'])
            ->count();
        $previousEvents = (int) $this->organizerEventsQuery($organizerId, $base)
            ->whereDate('created_at', '>=', $periods['previousFrom'])
            ->whereDate('created_at', '<=', $periods['previousTo'])
            ->count();

        $currentAttendees = (int) $this->organizerBookingsQuery($organizerId, $current)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');
        $previousAttendees = (int) $this->organizerBookingsQuery($organizerId, $previous)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');

        $currentRating = $this->periodAverageRating($organizerId, $current);
        $previousRating = $this->periodAverageRating($organizerId, $previous);

        return [
            'netRevenue' => $this->trendPayload($currentNet, $previousNet, $label),
            'ticketsSold' => $this->trendPayload($currentTickets, $previousTickets, $label),
            'events' => $this->trendPayload($currentEvents, $previousEvents, $label),
            'attendees' => $this->trendPayload($currentAttendees, $previousAttendees, $label),
            'engagement' => $this->trendPayload($currentRating ?? 0, $previousRating ?? 0, $label),
        ];
    }

    /**
     * Resolve current vs previous date windows for KPI trend badges.
     *
     * @param  array{from: ?string, to: ?string, event_id: ?int, status: ?string}  $filters
     * @return array{
     *     currentFrom: string,
     *     currentTo: string,
     *     previousFrom: string,
     *     previousTo: string,
     *     label: string
     * }
     */
    private function resolveTrendComparisonPeriods(array $filters): array
    {
        $today = now()->startOfDay();

        if (empty($filters['from']) && empty($filters['to'])) {
            return [
                'currentFrom' => $today->copy()->startOfMonth()->toDateString(),
                'currentTo' => $today->toDateString(),
                'previousFrom' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'previousTo' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'label' => 'vs last month',
            ];
        }

        $to = ! empty($filters['to'])
            ? Carbon::parse($filters['to'])->startOfDay()
            : $today->copy();

        $from = ! empty($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : $to->copy()->subDays(29);

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $inclusiveDays = (int) $from->diffInDays($to) + 1;

        // Full calendar year → previous calendar year
        if (
            $from->isSameDay($from->copy()->startOfYear())
            && $to->isSameDay($from->copy()->endOfYear())
        ) {
            $previousFrom = $from->copy()->subYearNoOverflow()->startOfYear();

            return [
                'currentFrom' => $from->toDateString(),
                'currentTo' => $to->toDateString(),
                'previousFrom' => $previousFrom->toDateString(),
                'previousTo' => $previousFrom->copy()->endOfYear()->toDateString(),
                'label' => 'vs previous year',
            ];
        }

        // Year-to-date (Jan 1 → today in that year) → same YTD last year
        if (
            $from->isSameDay($from->copy()->startOfYear())
            && $from->year === $to->year
            && $to->lte($today)
            && ($from->year < $today->year || $to->isSameDay($today))
        ) {
            return [
                'currentFrom' => $from->toDateString(),
                'currentTo' => $to->toDateString(),
                'previousFrom' => $from->copy()->subYearNoOverflow()->toDateString(),
                'previousTo' => $to->copy()->subYearNoOverflow()->toDateString(),
                'label' => 'vs prior year',
            ];
        }

        // Full calendar month → previous calendar month
        if (
            $from->isSameDay($from->copy()->startOfMonth())
            && $to->isSameDay($from->copy()->endOfMonth())
        ) {
            $previousFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();

            return [
                'currentFrom' => $from->toDateString(),
                'currentTo' => $to->toDateString(),
                'previousFrom' => $previousFrom->toDateString(),
                'previousTo' => $previousFrom->copy()->endOfMonth()->toDateString(),
                'label' => 'vs previous month',
            ];
        }

        // Month-to-date (1st → day within same month) → same days previous month
        if (
            $from->isSameDay($from->copy()->startOfMonth())
            && $from->isSameMonth($to)
        ) {
            $previousFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
            $day = min($to->day, $previousFrom->daysInMonth);

            return [
                'currentFrom' => $from->toDateString(),
                'currentTo' => $to->toDateString(),
                'previousFrom' => $previousFrom->toDateString(),
                'previousTo' => $previousFrom->copy()->day($day)->toDateString(),
                'label' => 'vs prior month',
            ];
        }

        // Rolling / custom range → immediately preceding window of equal length
        $previousTo = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($inclusiveDays - 1);

        $label = $inclusiveDays === 1
            ? 'vs prior day'
            : 'vs prior '.$inclusiveDays.' days';

        return [
            'currentFrom' => $from->toDateString(),
            'currentTo' => $to->toDateString(),
            'previousFrom' => $previousFrom->toDateString(),
            'previousTo' => $previousTo->toDateString(),
            'label' => $label,
        ];
    }
    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     */
    private function periodNetRevenue(int $organizerId, array $filters): float
    {
        $filters = $this->normalizeFilters($filters);

        $gross = (float) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->sum('ticket_price');

        $refundQuery = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        if (! empty($filters['from'])) {
            $refundQuery->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $refundQuery->whereDate('created_at', '<=', $filters['to']);
        }

        return round($gross - (float) $refundQuery->sum('refund_amount'), 2);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     */
    private function periodAverageRating(int $organizerId, array $filters): ?float
    {
        $filters = $this->normalizeFilters($filters);

        $query = Rating::query()
            ->whereHas('event', function ($eventQuery) use ($organizerId, $filters) {
                $eventQuery->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $eventQuery->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $eventQuery->where('events.status', $filters['status']);
                }
            });

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $avg = $query->avg('score');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * @return array{percent: float, up: bool, label: string}
     */
    private function trendPayload(float|int $current, float|int $previous, string $label): array
    {
        $percent = $this->percentChange($current, $previous);

        return [
            'percent' => abs($percent),
            'up' => $current >= $previous,
            'label' => $label,
        ];
    }

    private function percentChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous > 0) {
            return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
        }

        return (float) $current > 0 ? 100.0 : 0.0;
    }

    private function eventCapacity(Event $event): int
    {
        $fromCategories = (int) ($event->ticket_categories_sum_no_of_tickets ?? 0);

        if ($fromCategories <= 0 && $event->relationLoaded('ticketCategories')) {
            $fromCategories = (int) $event->ticketCategories->sum('no_of_tickets');
        }

        if ($fromCategories > 0) {
            return $fromCategories;
        }

        return max(0, (int) $event->no_of_tickets);
    }

    private function fillRate(int $sold, int $capacity): float
    {
        if ($capacity <= 0) {
            return 0.0;
        }

        return round(min(100, ($sold / $capacity) * 100), 1);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function withRevenueFillInsights(array $rows): array
    {
        $collection = collect($rows);
        $medianRevenue = $this->medianInt($collection->map(fn (array $row) => (int) round((float) ($row['revenue'] ?? 0)))->all());
        $medianFill = $this->medianInt($collection->map(fn (array $row) => (int) round((float) ($row['fill_rate'] ?? 0)))->all());

        return $collection
            ->map(function (array $row) use ($medianRevenue, $medianFill) {
                $row['insight'] = $this->revenueFillInsight(
                    (float) ($row['revenue'] ?? 0),
                    (float) ($row['fill_rate'] ?? 0),
                    (int) ($row['capacity'] ?? 0),
                    $medianRevenue,
                    $medianFill,
                );

                return $row;
            })
            ->all();
    }

    private function revenueFillInsight(
        float $revenue,
        float $fillRate,
        int $capacity,
        int $medianRevenue,
        int $medianFill,
    ): string {
        if ($revenue <= 0 && $fillRate <= 0) {
            return 'No sales yet';
        }

        if ($capacity <= 0) {
            return $revenue > 0 ? 'Revenue recorded, no capacity set' : 'No sales yet';
        }

        if ($fillRate <= 0 && $revenue > 0) {
            return 'Making money, lots of unsold seats';
        }

        $highRevenue = $revenue >= max(1, $medianRevenue);
        $highFill = $fillRate >= max(1, $medianFill);

        if ($highRevenue && $highFill) {
            return 'Strong seller';
        }

        if ($highRevenue) {
            return 'Making money, lots of unsold seats';
        }

        if ($highFill) {
            return 'Filling up, lower revenue';
        }

        return 'Needs more promotion';
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getTicketSalesReport(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $events = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ])
            ->withSum('ticketCategories', 'no_of_tickets')
            ->orderByDesc('tickets_sold')
            ->get();

        $salesByEvent = $events->map(function (Event $event) {
            $sold = (int) $event->tickets_sold;
            $capacity = $this->eventCapacity($event);

            return [
                'name' => $event->name,
                'sold' => $sold,
                'capacity' => $capacity,
                'fill_rate' => $this->fillRate($sold, $capacity),
            ];
        })->values()->all();

        return [
            'totalTicketsSold' => (int) $this->organizerBookingsQuery($organizerId, $filters)
                ->where('status', BookingStatusEnum::Confirmed)
                ->count(),
            'totalEvents' => $events->count(),
            'eventsWithSales' => $events->where('tickets_sold', '>', 0)->count(),
            'salesByEvent' => $salesByEvent,
            'salesTrend' => $this->monthlyBookingCounts($organizerId, $filters),
            'topSellingEvents' => collect($salesByEvent)->sortByDesc('sold')->take(5)->values()->all(),
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getRevenueReport(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $grossRevenue = (float) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->sum('ticket_price');

        $refundQuery = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        if (! empty($filters['from'])) {
            $refundQuery->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $refundQuery->whereDate('created_at', '<=', $filters['to']);
        }

        $totalRefunded = (float) $refundQuery->sum('refund_amount');

        $revenueByEvent = $this->organizerEventsQuery($organizerId, $filters)
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ], 'ticket_price')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (Event $event) => [
                'name' => $event->name,
                'revenue' => round((float) ($event->revenue ?? 0), 2),
                'status' => ucfirst($event->status),
            ])
            ->values()
            ->all();

        $revenueTrend = $this->monthlyRevenue($organizerId, $filters);
        $refundTrend = $this->monthlyRefunds($organizerId, $filters);

        $cumulative = [];
        $running = 0.0;
        foreach ($revenueTrend as $amount) {
            $running += (float) $amount;
            $cumulative[] = round($running, 2);
        }

        return [
            'grossRevenue' => $grossRevenue,
            'totalRefunded' => $totalRefunded,
            'netRevenue' => $grossRevenue - $totalRefunded,
            'averagePerEvent' => count($revenueByEvent) > 0
                ? round($grossRevenue / count($revenueByEvent), 2)
                : 0,
            'revenueByEvent' => $revenueByEvent,
            'revenueTrend' => $revenueTrend,
            'cumulativeRevenueTrend' => $cumulative,
            'refundTrend' => $refundTrend,
            'topRevenueEvents' => collect($revenueByEvent)->sortByDesc('revenue')->take(5)->values()->all(),
            'refundRate' => $grossRevenue > 0
                ? round(($totalRefunded / $grossRevenue) * 100, 1)
                : 0.0,
            'refundCount' => (int) (clone $refundQuery)->count(),
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getAttendeeReport(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $uniqueAttendees = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');

        $attendeesByEvent = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount([
                'ticketBookings as attendee_count' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ])
            ->orderByDesc('attendee_count')
            ->get()
            ->map(fn (Event $event) => [
                'name' => $event->name,
                'count' => (int) $event->attendee_count,
                'date' => $event->date ? Carbon::parse($event->date)->format('M d, Y') : '—',
                'status' => ucfirst($event->status),
            ])
            ->values()
            ->all();

        $recentAttendees = $this->organizerBookingsQuery($organizerId, $filters)
            ->with(['user', 'event'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (ticketBooking $booking) => [
                'name' => $booking->user?->full_name ?? 'Unknown',
                'email' => $booking->user?->email ?? '—',
                'event' => $booking->event?->name ?? '—',
                'ticket' => $booking->ticket_number,
                'status' => $booking->displayStatusLabel(),
                'booked' => $booking->created_at?->diffForHumans(),
            ])
            ->all();

        $totalBookings = (int) $this->organizerBookingsQuery($organizerId, $filters)->count();
        $confirmedBookings = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

        $repeatBuyers = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $avgTicketsPerAttendee = $uniqueAttendees > 0
            ? round($confirmedBookings / $uniqueAttendees, 1)
            : 0;

        $avgSpendPerAttendee = $uniqueAttendees > 0
            ? round((float) $this->organizerBookingsQuery($organizerId, $filters)
                ->where('status', BookingStatusEnum::Confirmed)
                ->sum('ticket_price') / $uniqueAttendees, 2)
            : 0;

        $confirmedWithUsers = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->with(['user:id,first_name,last_name,email,date_of_birth,gender,address,profile_photo'])
            ->get(['id', 'user_id', 'ticket_price', 'created_at']);

        $byUser = $confirmedWithUsers->groupBy('user_id');
        $newAttendees = $byUser->filter(fn ($group) => $group->count() === 1)->count();
        $repeatAttendees = $byUser->filter(fn ($group) => $group->count() > 1)->count();

        $topCustomers = $byUser
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection<int, ticketBooking> $group */
                $user = $group->first()?->user;

                return [
                    'name' => $user?->full_name ?? 'Unknown',
                    'email' => $user?->email ?? '—',
                    'tickets' => $group->count(),
                    'spend' => round((float) $group->sum('ticket_price'), 2),
                    'last_purchase' => optional($group->sortByDesc('created_at')->first()?->created_at)->diffForHumans() ?? '—',
                ];
            })
            ->sortByDesc('tickets')
            ->take(8)
            ->values()
            ->all();

        $uniqueUsers = $byUser
            ->map(fn ($group) => $group->first()?->user)
            ->filter();

        return [
            'totalAttendees' => $uniqueAttendees,
            'totalBookings' => $totalBookings,
            'confirmedBookings' => $confirmedBookings,
            'confirmationRate' => $totalBookings > 0
                ? round(($confirmedBookings / $totalBookings) * 100, 1)
                : 0,
            'repeatBuyers' => $repeatBuyers,
            'returningRate' => $uniqueAttendees > 0
                ? round(($repeatBuyers / $uniqueAttendees) * 100, 1)
                : 0,
            'newAttendees' => $newAttendees,
            'repeatAttendees' => $repeatAttendees,
            'avgTicketsPerAttendee' => $avgTicketsPerAttendee,
            'avgSpendPerAttendee' => $avgSpendPerAttendee,
            'attendeesByEvent' => $attendeesByEvent,
            'registrationTrend' => $this->monthlyBookingCounts($organizerId, $filters),
            'recentAttendees' => $recentAttendees,
            'demographics' => $this->buildDemographics($uniqueUsers),
            'topCustomers' => $topCustomers,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User|null>  $users
     * @return array{
     *     age: list<array{label: string, count: int}>,
     *     gender: list<array{label: string, count: int}>,
     *     location: list<array{label: string, count: int}>,
     *     available: array{age: bool, gender: bool, location: bool, any: bool}
     * }
     */
    private function buildDemographics($users): array
    {
        $ageBuckets = [
            'Under 18' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55+' => 0,
            'Unknown' => 0,
        ];
        $genderBuckets = [
            'Male' => 0,
            'Female' => 0,
            'Unknown' => 0,
        ];
        $locationBuckets = [];

        foreach ($users as $user) {
            if (! $user instanceof User) {
                $ageBuckets['Unknown']++;
                $genderBuckets['Unknown']++;
                $locationBuckets['Unknown'] = ($locationBuckets['Unknown'] ?? 0) + 1;
                continue;
            }

            if ($user->date_of_birth) {
                $age = Carbon::parse($user->date_of_birth)->age;
                $bucket = match (true) {
                    $age < 18 => 'Under 18',
                    $age <= 24 => '18-24',
                    $age <= 34 => '25-34',
                    $age <= 44 => '35-44',
                    $age <= 54 => '45-54',
                    default => '55+',
                };
                $ageBuckets[$bucket]++;
            } else {
                $ageBuckets['Unknown']++;
            }

            $genderValue = $user->gender?->value ?? null;
            if ($genderValue === 'male') {
                $genderBuckets['Male']++;
            } elseif ($genderValue === 'female') {
                $genderBuckets['Female']++;
            } else {
                $genderBuckets['Unknown']++;
            }

            $location = $this->locationLabel($user->address);
            $locationBuckets[$location] = ($locationBuckets[$location] ?? 0) + 1;
        }

        $toChart = fn (array $buckets) => collect($buckets)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();

        $locations = collect($locationBuckets)
            ->sortDesc()
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->values();

        if ($locations->count() > 6) {
            $top = $locations->take(5);
            $other = (int) $locations->slice(5)->sum('count');
            $locations = $top->push(['label' => 'Other', 'count' => $other]);
        }

        $age = $toChart($ageBuckets);
        $gender = $toChart($genderBuckets);
        $location = $locations->all();

        $knownCount = function (array $rows): int {
            return (int) collect($rows)
                ->reject(fn (array $row) => in_array($row['label'], ['Unknown', 'Other'], true))
                ->sum('count');
        };

        $ageAvailable = $knownCount($age) > 0;
        $genderAvailable = $knownCount($gender) > 0;
        $locationAvailable = $knownCount($location) > 0;

        return [
            'age' => $age,
            'gender' => $gender,
            'location' => $location,
            'available' => [
                'age' => $ageAvailable,
                'gender' => $genderAvailable,
                'location' => $locationAvailable,
                'any' => $ageAvailable || $genderAvailable || $locationAvailable,
            ],
        ];
    }

    private function locationLabel(?string $address): string
    {
        if (! filled($address)) {
            return 'Unknown';
        }

        $parts = preg_split('/[,\n]+/', $address) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if ($parts === []) {
            return 'Unknown';
        }

        $label = $parts[count($parts) - 1] ?: $parts[0];

        return Str::limit($label, 32, '') ?: 'Unknown';
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getEngagementReport(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $events = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount([
                'likes',
                'comments',
                'ratings',
                'saves',
                'ticketBookings as tickets_sold' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ])
            ->withAvg('ratings', 'score')
            ->get();

        $totalLikes = (int) $events->sum('likes_count');
        $totalComments = (int) $events->sum('comments_count');
        $totalRatings = (int) $events->sum('ratings_count');
        $totalSaves = (int) $events->sum('saves_count');

        $popularityByEvent = $events
            ->map(fn (Event $event) => [
                'name' => $event->name,
                'likes' => (int) $event->likes_count,
                'comments' => (int) $event->comments_count,
                'ratings' => (int) $event->ratings_count,
                'saves' => (int) $event->saves_count,
                'tickets_sold' => (int) $event->tickets_sold,
                'avg_rating' => $event->ratings_avg_score ? round((float) $event->ratings_avg_score, 1) : null,
                'score' => (int) $event->likes_count
                    + (int) $event->comments_count
                    + (int) $event->ratings_count
                    + (int) $event->saves_count,
            ])
            ->sortByDesc('score')
            ->values()
            ->all();

        $ratingsQuery = Rating::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        return [
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalRatings' => $totalRatings,
            'totalSaves' => $totalSaves,
            'averageRating' => $totalRatings > 0
                ? round((float) $ratingsQuery->avg('score'), 1)
                : null,
            'popularityByEvent' => $popularityByEvent,
            'topEvents' => collect($popularityByEvent)->take(5)->values()->all(),
            'engagementTrend' => $this->monthlyEngagement($organizerId, $filters),
            'engagementBeforeEvent' => $this->engagementBeforeEventDay($organizerId, $filters),
            'engagementVsSales' => $this->engagementVsSalesRows($popularityByEvent),
            'engagementBreakdown' => [
                ['label' => 'Likes', 'count' => $totalLikes],
                ['label' => 'Saves', 'count' => $totalSaves],
                ['label' => 'Comments', 'count' => $totalComments],
                ['label' => 'Ratings', 'count' => $totalRatings],
            ],
            'reviewQuality' => $this->getReviewQuality($organizerId, $filters, $popularityByEvent),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $popularityByEvent
     * @return list<array{name: string, engagement: int, tickets_sold: int, likes: int, comments: int, saves: int, ratings: int, insight: string}>
     */
    private function engagementVsSalesRows(array $popularityByEvent): array
    {
        $rows = collect($popularityByEvent)
            ->map(fn (array $event) => [
                'name' => (string) ($event['name'] ?? 'Event'),
                'engagement' => (int) ($event['score'] ?? 0),
                'tickets_sold' => (int) ($event['tickets_sold'] ?? 0),
                'likes' => (int) ($event['likes'] ?? 0),
                'comments' => (int) ($event['comments'] ?? 0),
                'saves' => (int) ($event['saves'] ?? 0),
                'ratings' => (int) ($event['ratings'] ?? 0),
            ])
            ->sortByDesc('tickets_sold')
            ->values();

        $medianEngagement = $this->medianInt($rows->pluck('engagement')->all());
        $medianTickets = $this->medianInt($rows->pluck('tickets_sold')->all());

        return $rows
            ->map(function (array $row) use ($medianEngagement, $medianTickets) {
                $row['insight'] = $this->engagementSalesInsight(
                    $row['engagement'],
                    $row['tickets_sold'],
                    $medianEngagement,
                    $medianTickets,
                );

                return $row;
            })
            ->all();
    }

    /**
     * @param  list<int>  $values
     */
    private function medianInt(array $values): int
    {
        $sorted = collect($values)->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (int) $sorted[$middle];
        }

        return (int) round(((int) $sorted[$middle - 1] + (int) $sorted[$middle]) / 2);
    }

    private function engagementSalesInsight(int $engagement, int $tickets, int $medianEngagement, int $medianTickets): string
    {
        if ($engagement === 0 && $tickets === 0) {
            return 'No activity yet';
        }

        if ($tickets === 0) {
            return 'Interest, no sales yet';
        }

        if ($engagement === 0) {
            return 'Selling with little buzz';
        }

        $highEngagement = $engagement >= max(1, $medianEngagement);
        $highTickets = $tickets >= max(1, $medianTickets);

        if ($highEngagement && $highTickets) {
            return 'Strong on both';
        }

        if ($highEngagement) {
            return 'High interest, low sales';
        }

        if ($highTickets) {
            return 'Sales without much buzz';
        }

        return 'Needs more promotion';
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array{label: string, count: int, percentage: float}>
     */
    public function getSalesByCategory(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $rows = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->join('ticket_categories', 'ticket_bookings.ticket_category_id', '=', 'ticket_categories.id')
            ->selectRaw('ticket_categories.name as label, COUNT(*) as count')
            ->groupBy('ticket_categories.id', 'ticket_categories.name')
            ->orderByDesc('count')
            ->get();

        $total = max(1, (int) $rows->sum('count'));

        return $rows->map(fn ($row) => [
            'label' => (string) $row->label,
            'count' => (int) $row->count,
            'percentage' => round(((int) $row->count / $total) * 100, 1),
        ])->values()->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array{label: string, data: list<int>}>
     */
    public function getTicketTypeTrend(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $rows = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('ticket_bookings.created_at', '>=', $since)
            ->join('ticket_categories', 'ticket_bookings.ticket_category_id', '=', 'ticket_categories.id')
            ->selectRaw("DATE_FORMAT(ticket_bookings.created_at, '%Y-%m') as month, ticket_categories.name as label, COUNT(*) as count")
            ->groupBy('month', 'ticket_categories.name')
            ->get();

        $totalsByLabel = $rows
            ->groupBy('label')
            ->map(fn ($group) => (int) $group->sum('count'))
            ->sortDesc();

        $topLabels = $totalsByLabel->keys()->take(5)->values();

        if ($topLabels->isEmpty()) {
            return [];
        }

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[(string) $row->label][(string) $row->month] = (int) $row->count;
        }

        return $topLabels->map(fn (string $label) => [
            'label' => $label,
            'data' => collect($keys)
                ->map(fn (string $month) => (int) ($lookup[$label][$month] ?? 0))
                ->values()
                ->all(),
        ])->values()->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array{label: string, count: int, rate: float|null}>
     */
    public function getConversionFunnel(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $scopeEvent = function ($query) use ($organizerId, $filters) {
            $query->createdByOrganizer($organizerId);

            if (! empty($filters['event_id'])) {
                $query->where('events.id', $filters['event_id']);
            }

            if (! empty($filters['status'])) {
                $query->where('events.status', $filters['status']);
            }
        };

        $viewsQuery = EventView::query()->whereHas('event', $scopeEvent);
        $savesQuery = SavedEvent::query()->whereHas('event', $scopeEvent);
        $cartQuery = CartItem::query()->whereHas('event', $scopeEvent);

        if (! empty($filters['from'])) {
            $viewsQuery->whereDate('event_views.created_at', '>=', $filters['from']);
            $savesQuery->whereDate('saved_events.created_at', '>=', $filters['from']);
            $cartQuery->whereDate('cart_items.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $viewsQuery->whereDate('event_views.created_at', '<=', $filters['to']);
            $savesQuery->whereDate('saved_events.created_at', '<=', $filters['to']);
            $cartQuery->whereDate('cart_items.created_at', '<=', $filters['to']);
        }

        $views = (int) $viewsQuery->count();
        $saves = (int) $savesQuery->count();
        $purchases = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

        // Cart stage = tickets still held in carts + confirmed purchases.
        // Purchased cart rows are deleted, so adding purchases keeps converted demand in the funnel.
        $cartHeld = (int) $cartQuery->sum('quantity');
        $cart = $cartHeld + $purchases;

        $rate = function (int $current, int $previous): ?float {
            if ($previous <= 0) {
                return null;
            }

            return round(($current / $previous) * 100, 1);
        };

        return [
            [
                'label' => 'Views',
                'count' => $views,
                'rate' => null,
            ],
            [
                'label' => 'Saves',
                'count' => $saves,
                'rate' => $rate($saves, $views),
            ],
            [
                'label' => 'Cart',
                'count' => $cart,
                'rate' => $rate($cart, max($saves, $views)),
            ],
            [
                'label' => 'Purchases',
                'count' => $purchases,
                'rate' => $rate($purchases, max($cart, $saves, $views)),
            ],
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array{key: string, label: string, count: int}>
     */
    public function getEventsByStatus(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $order = [
            Event::STATUS_UPCOMING,
            Event::STATUS_ONGOING,
            Event::STATUS_POSTPONED,
            Event::STATUS_COMPLETED,
            Event::STATUS_CANCELLED,
            Event::STATUS_UNPUBLISHED,
        ];

        $counts = $this->organizerEventsQuery($organizerId, $filters)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect($order)
            ->map(fn (string $status) => [
                'key' => $status,
                'label' => ucfirst($status),
                'count' => (int) ($counts[$status] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function getEventPerformance(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $events = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ], 'ticket_price')
            ->withSum('ticketCategories', 'no_of_tickets')
            ->withAvg('ratings', 'score')
            ->orderByDesc('revenue')
            ->get();

        $rows = $events->map(function (Event $event) {
            $sold = (int) $event->tickets_sold;
            $capacity = $this->eventCapacity($event);

            return [
                'id' => $event->id,
                'name' => $event->name,
                'tickets_sold' => $sold,
                'capacity' => $capacity,
                'revenue' => round((float) ($event->revenue ?? 0), 2),
                'fill_rate' => $this->fillRate($sold, $capacity),
                'rating' => $event->ratings_avg_score ? round((float) $event->ratings_avg_score, 1) : null,
                'status' => ucfirst((string) $event->status),
                'status_key' => strtolower((string) $event->status),
            ];
        })->values()->all();

        return $this->withRevenueFillInsights($rows);
    }

    /**
     * Check-in / attendance metrics for retained-sale tickets.
     *
     * No-shows are counted only for completed events (attendance is final).
     * Ongoing / upcoming events report checked-in vs still awaiting entry.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     ticketsEligible: int,
     *     checkedIn: int,
     *     noShows: int,
     *     awaitingCheckIn: int,
     *     attendanceRate: float|null,
     *     eventsWithTickets: int,
     *     eventsFinalized: int,
     *     peakTiming: array{label: string, count: int}|null,
     *     byEvent: list<array<string, mixed>>,
     *     checkInTiming: list<array{key: string, label: string, count: int}>,
     *     breakdown: list<array{label: string, count: int, key: string}>
     * }
     */
    public function getAttendanceReport(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $retainedStatuses = array_map(
            fn (BookingStatusEnum $status) => $status->value,
            BookingStatusEnum::retainedSaleStatuses()
        );

        $events = $this->organizerEventsQuery($organizerId, $filters)
            ->orderByDesc('date')
            ->orderBy('name')
            ->get(['id', 'name', 'date', 'time', 'status', 'date_tba']);

        $emptyTiming = $this->emptyCheckInTimingBuckets();

        if ($events->isEmpty()) {
            return [
                'ticketsEligible' => 0,
                'checkedIn' => 0,
                'noShows' => 0,
                'awaitingCheckIn' => 0,
                'attendanceRate' => null,
                'eventsWithTickets' => 0,
                'eventsFinalized' => 0,
                'peakTiming' => null,
                'byEvent' => [],
                'checkInTiming' => $emptyTiming,
                'breakdown' => [
                    ['key' => 'checked_in', 'label' => 'Checked in', 'count' => 0],
                    ['key' => 'no_shows', 'label' => 'No-shows', 'count' => 0],
                    ['key' => 'awaiting', 'label' => 'Awaiting check-in', 'count' => 0],
                ],
            ];
        }

        $eventIds = $events->pluck('id')->all();

        $statsQuery = ticketBooking::query()
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', $retainedStatuses);

        $this->applyBookingFilters($statsQuery, $filters);

        $perEventStats = (clone $statsQuery)
            ->selectRaw('event_id')
            ->selectRaw('COUNT(*) as tickets')
            ->selectRaw('SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in')
            ->groupBy('event_id')
            ->get()
            ->keyBy('event_id');

        $byEvent = [];
        $ticketsEligible = 0;
        $checkedIn = 0;
        $noShows = 0;
        $awaitingCheckIn = 0;
        $finalEligible = 0;
        $finalCheckedIn = 0;
        $eventsWithTickets = 0;
        $eventsFinalized = 0;

        foreach ($events as $event) {
            $row = $perEventStats->get($event->id);
            $tickets = (int) ($row->tickets ?? 0);
            $eventCheckedIn = (int) ($row->checked_in ?? 0);

            if ($tickets === 0) {
                continue;
            }

            $eventsWithTickets++;
            $isFinal = $event->isCompleted();
            $isCancelled = $event->isCancelled();
            $remaining = max(0, $tickets - $eventCheckedIn);
            $eventNoShows = $isFinal ? $remaining : 0;
            $eventAwaiting = (! $isFinal && ! $isCancelled) ? $remaining : 0;
            $rate = $tickets > 0 ? round(($eventCheckedIn / $tickets) * 100, 1) : 0.0;

            $ticketsEligible += $tickets;
            $checkedIn += $eventCheckedIn;
            $noShows += $eventNoShows;
            $awaitingCheckIn += $eventAwaiting;

            if ($isFinal) {
                $eventsFinalized++;
                $finalEligible += $tickets;
                $finalCheckedIn += $eventCheckedIn;
            }

            $byEvent[] = [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->formattedScheduleDate('M d, Y') ?? 'TBA',
                'status' => ucfirst((string) $event->status),
                'status_key' => strtolower((string) $event->status),
                'tickets' => $tickets,
                'checked_in' => $eventCheckedIn,
                'no_shows' => $eventNoShows,
                'awaiting_check_in' => $eventAwaiting,
                'attendance_rate' => $rate,
                'attendance_final' => $isFinal,
            ];
        }

        usort($byEvent, function (array $a, array $b) {
            $rateCmp = $b['attendance_rate'] <=> $a['attendance_rate'];

            return $rateCmp !== 0 ? $rateCmp : ($b['tickets'] <=> $a['tickets']);
        });

        $checkInTiming = $this->buildCheckInTiming(
            $filters,
            $events,
            $retainedStatuses
        );

        $peakTiming = collect($checkInTiming)
            ->filter(fn (array $bucket) => $bucket['count'] > 0)
            ->sortByDesc('count')
            ->map(fn (array $bucket) => [
                'label' => $bucket['label'],
                'count' => $bucket['count'],
            ])
            ->first();

        $attendanceRate = $finalEligible > 0
            ? round(($finalCheckedIn / $finalEligible) * 100, 1)
            : ($ticketsEligible > 0 ? round(($checkedIn / $ticketsEligible) * 100, 1) : null);

        return [
            'ticketsEligible' => $ticketsEligible,
            'checkedIn' => $checkedIn,
            'noShows' => $noShows,
            'awaitingCheckIn' => $awaitingCheckIn,
            'attendanceRate' => $attendanceRate,
            'eventsWithTickets' => $eventsWithTickets,
            'eventsFinalized' => $eventsFinalized,
            'peakTiming' => $peakTiming,
            'byEvent' => $byEvent,
            'checkInTiming' => $checkInTiming,
            'breakdown' => [
                ['key' => 'checked_in', 'label' => 'Checked in', 'count' => $checkedIn],
                ['key' => 'no_shows', 'label' => 'No-shows', 'count' => $noShows],
                ['key' => 'awaiting', 'label' => 'Awaiting check-in', 'count' => $awaitingCheckIn],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, min: ?int, max: ?int}>
     */
    private function emptyCheckInTimingBuckets(): array
    {
        return [
            ['key' => 'lt_minus_2h', 'label' => '2h+ before', 'count' => 0, 'min' => null, 'max' => -120],
            ['key' => 'minus_2h_1h', 'label' => '2h–1h before', 'count' => 0, 'min' => -120, 'max' => -60],
            ['key' => 'minus_1h_30m', 'label' => '1h–30m before', 'count' => 0, 'min' => -60, 'max' => -30],
            ['key' => 'minus_30m_0', 'label' => '30m before → start', 'count' => 0, 'min' => -30, 'max' => 0],
            ['key' => 'plus_0_30m', 'label' => 'Start → 30m', 'count' => 0, 'min' => 0, 'max' => 30],
            ['key' => 'plus_30m_1h', 'label' => '30m–1h after', 'count' => 0, 'min' => 30, 'max' => 60],
            ['key' => 'plus_1h_2h', 'label' => '1h–2h after', 'count' => 0, 'min' => 60, 'max' => 120],
            ['key' => 'gt_plus_2h', 'label' => '2h+ after', 'count' => 0, 'min' => 120, 'max' => null],
        ];
    }

    /**
     * Bucket check-ins by minutes relative to each event's start time.
     *
     * @param  \Illuminate\Support\Collection<int, Event>  $events
     * @param  list<string>  $retainedStatuses
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     * @return list<array{key: string, label: string, count: int}>
     */
    private function buildCheckInTiming(
        array $filters,
        $events,
        array $retainedStatuses
    ): array {
        $buckets = $this->emptyCheckInTimingBuckets();
        $eventsById = $events->keyBy('id');

        $query = ticketBooking::query()
            ->whereIn('event_id', $events->pluck('id')->all())
            ->whereIn('status', $retainedStatuses)
            ->whereNotNull('checked_in_at')
            ->select(['event_id', 'checked_in_at']);

        $this->applyBookingFilters($query, $filters);

        foreach ($query->cursor() as $booking) {
            $event = $eventsById->get($booking->event_id);

            if (! $event || $event->hasDateYetToBeScheduled() || blank($event->date)) {
                continue;
            }

            try {
                $startsAt = $event->startsAt();
                $checkedInAt = Carbon::parse($booking->checked_in_at);
            } catch (\Throwable) {
                continue;
            }

            $offsetMinutes = (int) round(($checkedInAt->getTimestamp() - $startsAt->getTimestamp()) / 60);

            foreach ($buckets as $index => $bucket) {
                $min = $bucket['min'];
                $max = $bucket['max'];
                $inRange = ($min === null || $offsetMinutes >= $min)
                    && ($max === null || $offsetMinutes < $max);

                if ($inRange) {
                    $buckets[$index]['count']++;
                    break;
                }
            }
        }

        return array_map(fn (array $bucket) => [
            'key' => $bucket['key'],
            'label' => $bucket['label'],
            'count' => $bucket['count'],
        ], $buckets);
    }

    /**
     * Daily confirmed sales for a calendar-month heatmap.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{month_label: string, year: int, month: int, start_weekday: int, max_sales: int, days: list<array<string, mixed>>}
     */
    public function getSalesHeatmap(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $focus = ! empty($filters['to'])
            ? Carbon::parse($filters['to'])->startOfMonth()
            : (! empty($filters['from'])
                ? Carbon::parse($filters['from'])->startOfMonth()
                : now()->startOfMonth());

        $start = $focus->copy()->startOfMonth();
        $end = $focus->copy()->endOfMonth();

        $rows = $this->organizerBookingsQuery($organizerId, [
            ...$filters,
            'from' => null,
            'to' => null,
        ])
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereBetween('ticket_bookings.created_at', [$start, $end])
            ->selectRaw('DATE(ticket_bookings.created_at) as day, COUNT(*) as count, SUM(ticket_bookings.ticket_price) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $maxSales = max(1, (int) $rows->max('count'));
        $daysInMonth = (int) $end->day;
        $days = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $focus->copy()->day($day);
            $key = $date->toDateString();
            $row = $rows->get($key);
            $count = (int) ($row->count ?? 0);
            $revenue = round((float) ($row->revenue ?? 0), 2);

            $days[] = [
                'date' => $key,
                'day' => $day,
                'weekday' => (int) $date->dayOfWeek,
                'count' => $count,
                'revenue' => $revenue,
                'intensity' => $count > 0 ? round($count / $maxSales, 2) : 0.0,
            ];
        }

        return [
            'month_label' => $focus->format('F Y'),
            'year' => (int) $focus->year,
            'month' => (int) $focus->month,
            'start_weekday' => (int) $start->dayOfWeek,
            'max_sales' => $maxSales,
            'days' => $days,
        ];
    }

    /**
     * Confirmed ticket sales by weekday × hour for peak-time analysis.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     day_labels: list<string>,
     *     hour_labels: list<string>,
     *     matrix: list<list<int>>,
     *     max_sales: int,
     *     peak: array{day: string, hour: string, count: int}|null
     * }
     */
    public function getPeakSalesHeatmap(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        // MySQL DAYOFWEEK: 1 = Sunday … 7 = Saturday. Reorder to Mon–Sun.
        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $dayIndexByDow = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];

        $rows = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->selectRaw('DAYOFWEEK(ticket_bookings.created_at) as dow, HOUR(ticket_bookings.created_at) as hour, COUNT(*) as count')
            ->groupBy('dow', 'hour')
            ->get();

        $matrix = array_fill(0, 24, array_fill(0, 7, 0));
        $maxSales = 0;
        $peak = null;

        foreach ($rows as $row) {
            $dow = (int) $row->dow;
            $hour = (int) $row->hour;
            $count = (int) $row->count;
            $dayIndex = $dayIndexByDow[$dow] ?? null;

            if ($dayIndex === null || $hour < 0 || $hour > 23) {
                continue;
            }

            $matrix[$hour][$dayIndex] = $count;
            $maxSales = max($maxSales, $count);

            if ($peak === null || $count > $peak['count']) {
                $peak = [
                    'day' => $dayLabels[$dayIndex],
                    'hour' => sprintf('%02d:00', $hour),
                    'count' => $count,
                ];
            }
        }

        $hourLabels = collect(range(0, 23))
            ->map(fn (int $hour) => sprintf('%02d:00', $hour))
            ->values()
            ->all();

        return [
            'day_labels' => $dayLabels,
            'hour_labels' => $hourLabels,
            'matrix' => $matrix,
            'max_sales' => max(1, $maxSales),
            'peak' => $peak,
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function getRecentTransactions(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return $this->organizerBookingsQuery($organizerId, $filters)
            ->with(['user', 'event', 'payment', 'ticketCategory'])
            ->latest('ticket_bookings.created_at')
            ->limit(12)
            ->get()
            ->map(fn (ticketBooking $booking) => [
                'customer' => $booking->user?->full_name ?? 'Unknown',
                'email' => $booking->user?->email ?? '—',
                'event' => $booking->event?->name ?? '—',
                'category' => $booking->ticketCategory?->name ?? 'General',
                'ticket' => $booking->ticket_number,
                'amount' => round((float) $booking->ticket_price, 2),
                'method' => $booking->payment?->payment_method?->value ?? '—',
                'status' => $booking->displayStatusLabel(),
                'status_key' => strtolower((string) ($booking->status?->value ?? $booking->status)),
                'date' => $booking->created_at?->format('M d, Y H:i') ?? '—',
                'relative' => $booking->created_at?->diffForHumans() ?? '—',
            ])
            ->all();
    }

    /**
     * @return array{events: list<array{id: int, name: string}>, statuses: list<string>}
     */
    private function getFilterOptions(int $organizerId): array
    {
        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
            ])
            ->values()
            ->all();

        return [
            'events' => $events,
            'statuses' => [
                Event::STATUS_UPCOMING,
                Event::STATUS_ONGOING,
                Event::STATUS_POSTPONED,
                Event::STATUS_COMPLETED,
                Event::STATUS_CANCELLED,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{from: ?string, to: ?string, event_id: ?int, status: ?string}
     */
    private function normalizeFilters(array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->toDateString() : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->toDateString() : null;
        $eventId = ! empty($filters['event_id']) ? (int) $filters['event_id'] : null;
        $status = ! empty($filters['status']) ? (string) $filters['status'] : null;

        return [
            'from' => $from,
            'to' => $to,
            'event_id' => $eventId,
            'status' => $status,
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     */
    private function organizerEventsQuery(int $organizerId, array $filters = [])
    {
        $query = Event::query()->createdByOrganizer($organizerId);

        if (! empty($filters['event_id'])) {
            $query->where('id', $filters['event_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     */
    private function organizerBookingsQuery(int $organizerId, array $filters = [])
    {
        $query = ticketBooking::query()
            ->whereHas('event', function ($eventQuery) use ($organizerId, $filters) {
                $eventQuery->createdByOrganizer($organizerId);

                if (! empty($filters['event_id'])) {
                    $eventQuery->where('events.id', $filters['event_id']);
                }

                if (! empty($filters['status'])) {
                    $eventQuery->where('events.status', $filters['status']);
                }
            });

        return $this->applyBookingFilters($query, $filters);
    }

    /**
     * @param  array{from?: string|null, to?: string|null}  $filters
     */
    private function applyBookingFilters($query, array $filters)
    {
        if (! empty($filters['from'])) {
            $query->whereDate('ticket_bookings.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ticket_bookings.created_at', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * @param  array{from?: string|null, to?: string|null}  $filters
     * @return list<string>
     */
    private function monthLabels(array $filters): array
    {
        return collect($this->monthKeys($filters))
            ->map(fn (string $key) => Carbon::createFromFormat('Y-m', $key)->format('M Y'))
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null}  $filters
     * @return list<string>
     */
    private function monthKeys(array $filters): array
    {
        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $start = ! empty($filters['from'])
                ? Carbon::parse($filters['from'])->startOfMonth()
                : Carbon::parse($filters['to'])->subMonths(5)->startOfMonth();
            $end = ! empty($filters['to'])
                ? Carbon::parse($filters['to'])->startOfMonth()
                : Carbon::parse($filters['from'])->startOfMonth();

            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            $keys = [];
            $cursor = $start->copy();
            while ($cursor->lte($end) && count($keys) < 12) {
                $keys[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $keys ?: $this->lastSixMonthKeys();
        }

        return $this->lastSixMonthKeys();
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthKeys(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'))
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     * @return list<int>
     */
    private function monthlyBookingCounts(int $organizerId, array $filters = []): array
    {
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $counts = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('ticket_bookings.created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(ticket_bookings.created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        return collect($keys)
            ->map(fn (string $key) => (int) ($counts[$key] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     * @return list<float>
     */
    private function monthlyRevenue(int $organizerId, array $filters = []): array
    {
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $totals = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('ticket_bookings.created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(ticket_bookings.created_at, '%Y-%m') as month, SUM(ticket_bookings.ticket_price) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect($keys)
            ->map(fn (string $key) => round((float) ($totals[$key] ?? 0), 2))
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     * @return list<float>
     */
    private function monthlyRefunds(int $organizerId, array $filters = []): array
    {
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $query = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', function ($eventQuery) use ($organizerId, $filters) {
                $eventQuery->createdByOrganizer($organizerId);

                if (! empty($filters['event_id'])) {
                    $eventQuery->where('events.id', $filters['event_id']);
                }

                if (! empty($filters['status'])) {
                    $eventQuery->where('events.status', $filters['status']);
                }
            })
            ->where('refund_requests.created_at', '>=', $since);

        if (! empty($filters['from'])) {
            $query->whereDate('refund_requests.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('refund_requests.created_at', '<=', $filters['to']);
        }

        $totals = $query
            ->selectRaw("DATE_FORMAT(refund_requests.created_at, '%Y-%m') as month, SUM(refund_requests.refund_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect($keys)
            ->map(fn (string $key) => round((float) ($totals[$key] ?? 0), 2))
            ->values()
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}  $filters
     * @return array{likes: list<int>, comments: list<int>, ratings: list<int>, saves: list<int>}
     */
    private function monthlyEngagement(int $organizerId, array $filters = []): array
    {
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $likes = $this->monthlyModelCounts(Like::class, $organizerId, $since, $filters);
        $comments = $this->monthlyModelCounts(Comment::class, $organizerId, $since, $filters);
        $ratings = $this->monthlyModelCounts(Rating::class, $organizerId, $since, $filters);
        $saves = $this->monthlyModelCounts(SavedEvent::class, $organizerId, $since, $filters);

        return [
            'likes' => collect($keys)->map(fn (string $key) => (int) ($likes[$key] ?? 0))->values()->all(),
            'comments' => collect($keys)->map(fn (string $key) => (int) ($comments[$key] ?? 0))->values()->all(),
            'ratings' => collect($keys)->map(fn (string $key) => (int) ($ratings[$key] ?? 0))->values()->all(),
            'saves' => collect($keys)->map(fn (string $key) => (int) ($saves[$key] ?? 0))->values()->all(),
        ];
    }

    /**
     * Ticket sales velocity in the 30 days leading up to event day (T-30 → T-0).
     *
     * Aggregates confirmed bookings across filtered events by days-until-event,
     * so organizers can see when demand ramps and plan promotions accordingly.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     windowDays: int,
     *     labels: list<string>,
     *     offsets: list<int>,
     *     tickets: list<int>,
     *     cumulative: list<int>,
     *     totalInWindow: int,
     *     peak: array{label: string, offset: int, count: int}|null,
     *     finalWeekShare: float|null,
     *     earlyShare: float|null,
     *     midShare: float|null
     * }
     */
    public function getSalesVelocityBeforeEvent(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $windowDays = 30;
        $offsets = range(-$windowDays, 0);
        $labels = collect($offsets)
            ->map(fn (int $day) => $day === 0 ? 'T-0' : 'T'.$day)
            ->values()
            ->all();

        $empty = array_fill(0, count($offsets), 0);
        $indexByOffset = array_flip($offsets);
        $raw = $this->relativeDayTicketCounts($organizerId, $filters, $windowDays);

        $tickets = $empty;
        foreach ($raw as $offset => $count) {
            $offset = (int) $offset;
            if (array_key_exists($offset, $indexByOffset)) {
                $tickets[$indexByOffset[$offset]] = (int) $count;
            }
        }

        $cumulative = [];
        $running = 0;
        foreach ($tickets as $count) {
            $running += $count;
            $cumulative[] = $running;
        }

        $totalInWindow = $running;
        $peak = null;
        $maxCount = max($tickets);

        if ($maxCount > 0) {
            $peakIndex = array_search($maxCount, $tickets, true);
            $peakOffset = $offsets[$peakIndex];
            $peak = [
                'label' => $labels[$peakIndex],
                'offset' => $peakOffset,
                'count' => $maxCount,
            ];
        }

        $sumRange = function (int $fromOffset, int $toOffset) use ($tickets, $indexByOffset): int {
            $sum = 0;
            for ($offset = $fromOffset; $offset <= $toOffset; $offset++) {
                if (array_key_exists($offset, $indexByOffset)) {
                    $sum += $tickets[$indexByOffset[$offset]];
                }
            }

            return $sum;
        };

        $share = function (int $part) use ($totalInWindow): ?float {
            if ($totalInWindow <= 0) {
                return null;
            }

            return round(($part / $totalInWindow) * 100, 1);
        };

        return [
            'windowDays' => $windowDays,
            'labels' => $labels,
            'offsets' => $offsets,
            'tickets' => $tickets,
            'cumulative' => $cumulative,
            'totalInWindow' => $totalInWindow,
            'peak' => $peak,
            'finalWeekShare' => $share($sumRange(-7, 0)),
            'earlyShare' => $share($sumRange(-30, -15)),
            'midShare' => $share($sumRange(-14, -8)),
        ];
    }

    /**
     * Aggregate engagement + ticket sales by days relative to each event's date (−28 … 0).
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{labels: list<string>, likes: list<int>, comments: list<int>, saves: list<int>, ratings: list<int>, tickets: list<int>}
     */
    private function engagementBeforeEventDay(int $organizerId, array $filters = []): array
    {
        $windowDays = 28;
        $offsets = range(-$windowDays, 0);
        $labels = collect($offsets)
            ->map(fn (int $day) => $day === 0 ? 'Event day' : (string) $day)
            ->values()
            ->all();

        $empty = array_fill(0, count($offsets), 0);
        $indexByOffset = array_flip($offsets);

        $fill = function (array $rows) use ($empty, $indexByOffset): array {
            $series = $empty;
            foreach ($rows as $offset => $count) {
                $offset = (int) $offset;
                if (array_key_exists($offset, $indexByOffset)) {
                    $series[$indexByOffset[$offset]] = (int) $count;
                }
            }

            return $series;
        };

        return [
            'labels' => $labels,
            'likes' => $fill($this->relativeDayCounts('likes', $organizerId, $filters, $windowDays)),
            'comments' => $fill($this->relativeDayCounts('comments', $organizerId, $filters, $windowDays)),
            'saves' => $fill($this->relativeDayCounts('saved_events', $organizerId, $filters, $windowDays)),
            'ratings' => $fill($this->relativeDayCounts('ratings', $organizerId, $filters, $windowDays)),
            'tickets' => $fill($this->relativeDayTicketCounts($organizerId, $filters, $windowDays)),
        ];
    }

    /**
     * @param  array{event_id?: int|string|null, status?: string|null}  $filters
     * @return array<int, int>
     */
    private function relativeDayCounts(string $table, int $organizerId, array $filters, int $windowDays): array
    {
        $query = DB::table($table)
            ->join('events', 'events.id', '=', "{$table}.event_id")
            ->where('events.created_by', $organizerId)
            ->whereNotNull('events.date')
            ->whereRaw("DATEDIFF(DATE({$table}.created_at), DATE(events.date)) BETWEEN ? AND 0", [-$windowDays]);

        if (! empty($filters['event_id'])) {
            $query->where('events.id', $filters['event_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('events.status', $filters['status']);
        }

        return $query
            ->selectRaw("DATEDIFF(DATE({$table}.created_at), DATE(events.date)) as day_offset, COUNT(*) as count")
            ->groupBy('day_offset')
            ->pluck('count', 'day_offset')
            ->all();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<int, int>
     */
    private function relativeDayTicketCounts(int $organizerId, array $filters, int $windowDays): array
    {
        $query = DB::table('ticket_bookings')
            ->join('events', 'events.id', '=', 'ticket_bookings.event_id')
            ->where('events.created_by', $organizerId)
            ->where('ticket_bookings.status', BookingStatusEnum::Confirmed->value)
            ->whereNotNull('events.date')
            ->whereRaw('DATEDIFF(DATE(ticket_bookings.created_at), DATE(events.date)) BETWEEN ? AND 0', [-$windowDays]);

        if (! empty($filters['event_id'])) {
            $query->where('events.id', $filters['event_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('events.status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('ticket_bookings.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ticket_bookings.created_at', '<=', $filters['to']);
        }

        return $query
            ->selectRaw('DATEDIFF(DATE(ticket_bookings.created_at), DATE(events.date)) as day_offset, COUNT(*) as count')
            ->groupBy('day_offset')
            ->pluck('count', 'day_offset')
            ->all();
    }

    /**
     * @param  class-string  $modelClass
     * @param  array{event_id?: int|null, status?: string|null}  $filters
     * @return array<string, int>
     */
    private function monthlyModelCounts(string $modelClass, int $organizerId, $since, array $filters = []): array
    {
        return $modelClass::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);

                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }

                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            })
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->all();
    }

    /**
     * Side-by-side metrics for event comparison (client picks 2–3).
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function getEventComparison(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $events = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
                'views as views_count' => function ($query) use ($filters) {
                    if (! empty($filters['from'])) {
                        $query->whereDate('event_views.created_at', '>=', $filters['from']);
                    }
                    if (! empty($filters['to'])) {
                        $query->whereDate('event_views.created_at', '<=', $filters['to']);
                    }
                },
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $this->applyBookingFilters(
                    $query->where('status', BookingStatusEnum::Confirmed),
                    $filters
                ),
            ], 'ticket_price')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->withSum('ticketCategories', 'no_of_tickets')
            ->orderByDesc('revenue')
            ->limit(24)
            ->get();

        return $events->map(function (Event $event) {
            $tickets = (int) $event->tickets_sold;
            $views = (int) $event->views_count;
            $capacity = $this->eventCapacity($event);
            $revenue = round((float) ($event->revenue ?? 0), 2);

            return [
                'id' => $event->id,
                'name' => $event->name,
                'status' => ucfirst((string) $event->status),
                'status_key' => strtolower((string) $event->status),
                'revenue' => $revenue,
                'tickets_sold' => $tickets,
                'capacity' => $capacity,
                'fill_rate' => $this->fillRate($tickets, $capacity),
                'views' => $views,
                'conversion_rate' => $views > 0 ? round(($tickets / $views) * 100, 1) : null,
                'rating' => $event->ratings_avg_score ? round((float) $event->ratings_avg_score, 1) : null,
                'ratings_count' => (int) $event->ratings_count,
            ];
        })->values()->all();
    }

    /**
     * Refund leakage: rate, by event, by ticket category, monthly trend.
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function getRefundAnalytics(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $gross = (float) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->sum('ticket_price');

        $baseRefundQuery = RefundRequest::query()
            ->where('refund_requests.status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        if (! empty($filters['from'])) {
            $baseRefundQuery->whereDate('refund_requests.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $baseRefundQuery->whereDate('refund_requests.created_at', '<=', $filters['to']);
        }

        $totalRefunded = round((float) (clone $baseRefundQuery)->sum('refund_amount'), 2);
        $refundCount = (int) (clone $baseRefundQuery)->count();
        $refundRate = $gross > 0 ? round(($totalRefunded / $gross) * 100, 1) : 0.0;

        $byEventRows = (clone $baseRefundQuery)
            ->join('ticket_bookings', 'ticket_bookings.id', '=', 'refund_requests.ticket_booking_id')
            ->join('events', 'events.id', '=', 'ticket_bookings.event_id')
            ->selectRaw('events.id as event_id, events.name as event_name, COUNT(*) as refund_count, SUM(refund_requests.refund_amount) as refunded')
            ->groupBy('events.id', 'events.name')
            ->orderByDesc('refunded')
            ->limit(12)
            ->get();

        $eventGross = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->selectRaw('event_id, SUM(ticket_price) as gross')
            ->groupBy('event_id')
            ->pluck('gross', 'event_id');

        $byEvent = $byEventRows->map(function ($row) use ($eventGross) {
            $refunded = round((float) $row->refunded, 2);
            $eventGrossAmount = (float) ($eventGross[$row->event_id] ?? 0);

            return [
                'id' => (int) $row->event_id,
                'name' => (string) $row->event_name,
                'refund_count' => (int) $row->refund_count,
                'refunded' => $refunded,
                'rate' => $eventGrossAmount > 0
                    ? round(($refunded / $eventGrossAmount) * 100, 1)
                    : null,
            ];
        })->values()->all();

        $byCategoryRows = (clone $baseRefundQuery)
            ->join('ticket_bookings', 'ticket_bookings.id', '=', 'refund_requests.ticket_booking_id')
            ->leftJoin('ticket_categories', 'ticket_categories.id', '=', 'ticket_bookings.ticket_category_id')
            ->selectRaw("COALESCE(ticket_categories.name, 'General') as label, COUNT(*) as refund_count, SUM(refund_requests.refund_amount) as refunded")
            ->groupBy('label')
            ->orderByDesc('refunded')
            ->get();

        $categoryGross = $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->leftJoin('ticket_categories', 'ticket_categories.id', '=', 'ticket_bookings.ticket_category_id')
            ->selectRaw("COALESCE(ticket_categories.name, 'General') as label, SUM(ticket_bookings.ticket_price) as gross")
            ->groupBy('label')
            ->pluck('gross', 'label');

        $byCategory = $byCategoryRows->map(function ($row) use ($categoryGross, $totalRefunded) {
            $refunded = round((float) $row->refunded, 2);
            $label = (string) $row->label;
            $grossAmount = (float) ($categoryGross[$label] ?? 0);

            return [
                'label' => $label,
                'refund_count' => (int) $row->refund_count,
                'refunded' => $refunded,
                'share' => $totalRefunded > 0 ? round(($refunded / $totalRefunded) * 100, 1) : 0.0,
                'rate' => $grossAmount > 0 ? round(($refunded / $grossAmount) * 100, 1) : null,
            ];
        })->values()->all();

        return [
            'grossRevenue' => round($gross, 2),
            'totalRefunded' => $totalRefunded,
            'refundCount' => $refundCount,
            'refundRate' => $refundRate,
            'refundTrend' => $this->monthlyRefunds($organizerId, $filters),
            'byEvent' => $byEvent,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * Rating quality: monthly avg score, distribution, low-rated events.
     * Response rate is reserved for future organizer replies (not stored yet).
     *
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @param  list<array<string, mixed>>  $popularityByEvent
     * @return array<string, mixed>
     */
    public function getReviewQuality(int $organizerId, array $filters = [], array $popularityByEvent = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $keys = $this->monthKeys($filters);
        $since = Carbon::createFromFormat('Y-m', $keys[0])->startOfMonth();

        $ratingsQuery = Rating::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);
                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }
                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        if (! empty($filters['from'])) {
            $ratingsQuery->whereDate('ratings.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $ratingsQuery->whereDate('ratings.created_at', '<=', $filters['to']);
        }

        $monthlyAvg = (clone $ratingsQuery)
            ->where('ratings.created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(ratings.created_at, '%Y-%m') as month, AVG(ratings.score) as avg_score, COUNT(*) as count")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $averageTrend = collect($keys)->map(fn (string $key) => isset($monthlyAvg[$key])
            ? round((float) $monthlyAvg[$key]->avg_score, 2)
            : null
        )->values()->all();

        $countTrend = collect($keys)->map(fn (string $key) => (int) ($monthlyAvg[$key]->count ?? 0)
        )->values()->all();

        $distributionRows = (clone $ratingsQuery)
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score');

        $distribution = [];
        for ($score = 1; $score <= 5; $score++) {
            $distribution[] = [
                'score' => $score,
                'label' => $score.'★',
                'count' => (int) ($distributionRows[$score] ?? 0),
            ];
        }

        $lowRatedEvents = $this->organizerEventsQuery($organizerId, $filters)
            ->withCount('ratings')
            ->withAvg('ratings', 'score')
            ->having('ratings_count', '>=', 2)
            ->having('ratings_avg_score', '<', 3.5)
            ->orderBy('ratings_avg_score')
            ->limit(8)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'rating' => round((float) $event->ratings_avg_score, 1),
                'ratings_count' => (int) $event->ratings_count,
                'status' => ucfirst((string) $event->status),
            ])
            ->values()
            ->all();

        $totalRatings = (int) (clone $ratingsQuery)->count();
        $averageRating = $totalRatings > 0
            ? round((float) (clone $ratingsQuery)->avg('score'), 1)
            : null;

        return [
            'averageRating' => $averageRating,
            'totalRatings' => $totalRatings,
            'averageTrend' => $averageTrend,
            'countTrend' => $countTrend,
            'distribution' => $distribution,
            'lowRatedEvents' => $lowRatedEvents,
            // Organizer replies to ratings are not supported yet.
            'responseRate' => null,
            'topRatedEvents' => collect($popularityByEvent)
                ->filter(fn (array $event) => ($event['avg_rating'] ?? null) !== null && ($event['ratings'] ?? 0) >= 1)
                ->sortByDesc('avg_rating')
                ->take(5)
                ->map(fn (array $event) => [
                    'name' => $event['name'],
                    'rating' => $event['avg_rating'],
                    'ratings_count' => $event['ratings'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Payload for the weekly organizer email/digest notification.
     *
     * @return array{
     *     weekLabel: string,
     *     from: string,
     *     to: string,
     *     netRevenue: float,
     *     ticketsSold: int,
     *     attendanceRate: float|null,
     *     checkedIn: int,
     *     topEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     bottomEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     reportsUrl: string
     * }
     */
    public function getWeeklyDigestPayload(int $organizerId): array
    {
        $to = now()->startOfDay();
        $from = $to->copy()->subDays(6);
        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'event_id' => null,
            'status' => null,
        ];

        $netRevenue = $this->periodNetRevenue($organizerId, $filters);
        $ticketsSold = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

        $attendance = $this->getAttendanceReport($organizerId, $filters);
        $performance = collect($this->getEventPerformance($organizerId, $filters))
            ->filter(fn (array $event) => ((int) $event['tickets_sold']) > 0 || ((float) $event['revenue']) > 0)
            ->values();

        $top = $performance->sortByDesc('revenue')->first();
        $bottom = $performance->sortBy('revenue')->first();

        $mapEvent = function (?array $event): ?array {
            if (! $event) {
                return null;
            }

            return [
                'name' => $event['name'],
                'revenue' => (float) $event['revenue'],
                'tickets_sold' => (int) $event['tickets_sold'],
            ];
        };

        return [
            'weekLabel' => $from->format('M j').' – '.$to->format('M j, Y'),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'netRevenue' => $netRevenue,
            'ticketsSold' => $ticketsSold,
            'attendanceRate' => $attendance['attendanceRate'],
            'checkedIn' => (int) ($attendance['checkedIn'] ?? 0),
            'topEvent' => $mapEvent($top),
            'bottomEvent' => $performance->count() > 1 ? $mapEvent($bottom) : null,
            'reportsUrl' => route('organizer.dashboard', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]).'#insights',
        ];
    }
}

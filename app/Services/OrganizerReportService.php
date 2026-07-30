<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
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

        return [
            'ticketSales' => $this->getTicketSalesReport($organizerId, $filters),
            'revenue' => $this->getRevenueReport($organizerId, $filters),
            'attendees' => $this->getAttendeeReport($organizerId, $filters),
            'engagement' => $this->getEngagementReport($organizerId, $filters),
            'salesByCategory' => $this->getSalesByCategory($organizerId, $filters),
            'ticketTypeTrend' => $this->getTicketTypeTrend($organizerId, $filters),
            'conversionFunnel' => $this->getConversionFunnel($organizerId, $filters),
            'eventPerformance' => $this->getEventPerformance($organizerId, $filters),
            'salesHeatmap' => $this->getSalesHeatmap($organizerId, $filters),
            'peakSalesHeatmap' => $this->getPeakSalesHeatmap($organizerId, $filters),
            'recentTransactions' => $this->getRecentTransactions($organizerId, $filters),
            'summaryTrends' => $this->getSummaryTrends($organizerId, $filters),
            'filterOptions' => $this->getFilterOptions($organizerId),
            'filters' => $filters,
            'chartLabels' => $this->monthLabels($filters),
        ];
    }

    /**
     * Month-over-month change for top summary cards.
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
        $label = 'vs last month';

        $currentFrom = now()->startOfMonth()->toDateString();
        $currentTo = now()->toDateString();
        $previousFrom = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $previousTo = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $base = [
            'event_id' => $filters['event_id'],
            'status' => $filters['status'],
        ];

        $current = array_merge($base, ['from' => $currentFrom, 'to' => $currentTo]);
        $previous = array_merge($base, ['from' => $previousFrom, 'to' => $previousTo]);

        $currentNet = $this->periodNetRevenue($organizerId, $current);
        $previousNet = $this->periodNetRevenue($organizerId, $previous);

        $currentTickets = (int) $this->organizerBookingsQuery($organizerId, $current)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();
        $previousTickets = (int) $this->organizerBookingsQuery($organizerId, $previous)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

        $currentEvents = (int) $this->organizerEventsQuery($organizerId, $base)
            ->whereDate('created_at', '>=', $currentFrom)
            ->whereDate('created_at', '<=', $currentTo)
            ->count();
        $previousEvents = (int) $this->organizerEventsQuery($organizerId, $base)
            ->whereDate('created_at', '>=', $previousFrom)
            ->whereDate('created_at', '<=', $previousTo)
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
            ->orderByDesc('tickets_sold')
            ->get();

        $salesByEvent = $events->map(fn (Event $event) => [
            'name' => $event->name,
            'sold' => (int) $event->tickets_sold,
            'capacity' => (int) $event->no_of_tickets,
            'fill_rate' => $event->no_of_tickets > 0
                ? round(($event->tickets_sold / $event->no_of_tickets) * 100, 1)
                : 0,
        ])->values()->all();

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
            'engagementVsSales' => collect($popularityByEvent)
                ->map(fn (array $event) => [
                    'name' => $event['name'],
                    'engagement' => (int) $event['score'],
                    'tickets_sold' => (int) $event['tickets_sold'],
                    'likes' => (int) $event['likes'],
                    'comments' => (int) $event['comments'],
                    'saves' => (int) $event['saves'],
                    'ratings' => (int) $event['ratings'],
                ])
                ->values()
                ->all(),
            'engagementBreakdown' => [
                ['label' => 'Likes', 'count' => $totalLikes],
                ['label' => 'Saves', 'count' => $totalSaves],
                ['label' => 'Comments', 'count' => $totalComments],
                ['label' => 'Ratings', 'count' => $totalRatings],
            ],
        ];
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

        $viewsQuery = EventView::query()
            ->whereHas('event', function ($query) use ($organizerId, $filters) {
                $query->createdByOrganizer($organizerId);

                if (! empty($filters['event_id'])) {
                    $query->where('events.id', $filters['event_id']);
                }

                if (! empty($filters['status'])) {
                    $query->where('events.status', $filters['status']);
                }
            });

        $savesQuery = SavedEvent::query()
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
            $viewsQuery->whereDate('event_views.created_at', '>=', $filters['from']);
            $savesQuery->whereDate('saved_events.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $viewsQuery->whereDate('event_views.created_at', '<=', $filters['to']);
            $savesQuery->whereDate('saved_events.created_at', '<=', $filters['to']);
        }

        $views = (int) $viewsQuery->count();
        $saves = (int) $savesQuery->count();
        $purchases = (int) $this->organizerBookingsQuery($organizerId, $filters)
            ->where('status', BookingStatusEnum::Confirmed)
            ->count();

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
                'label' => 'Purchases',
                'count' => $purchases,
                'rate' => $rate($purchases, max($saves, $views)),
            ],
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function getEventPerformance(int $organizerId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return $this->organizerEventsQuery($organizerId, $filters)
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
            ->withAvg('ratings', 'score')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'tickets_sold' => (int) $event->tickets_sold,
                'revenue' => round((float) ($event->revenue ?? 0), 2),
                'fill_rate' => $event->no_of_tickets > 0
                    ? round(($event->tickets_sold / $event->no_of_tickets) * 100, 1)
                    : 0,
                'rating' => $event->ratings_avg_score ? round((float) $event->ratings_avg_score, 1) : null,
                'status' => ucfirst((string) $event->status),
                'status_key' => strtolower((string) $event->status),
            ])
            ->values()
            ->all();
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
}

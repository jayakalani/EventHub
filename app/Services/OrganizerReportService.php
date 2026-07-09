<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Like;
use App\Models\Rating;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use Illuminate\Support\Facades\DB;

class OrganizerReportService
{
    /**
     * @return array<string, mixed>
     */
    public function getAllReports(int $organizerId): array
    {
        return [
            'ticketSales' => $this->getTicketSalesReport($organizerId),
            'revenue' => $this->getRevenueReport($organizerId),
            'attendees' => $this->getAttendeeReport($organizerId),
            'engagement' => $this->getEngagementReport($organizerId),
            'chartLabels' => $this->lastSixMonthLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTicketSalesReport(int $organizerId): array
    {
        $events = $this->organizerEventsQuery($organizerId)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
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
            'totalTicketsSold' => (int) $this->organizerBookingsQuery($organizerId)
                ->where('status', BookingStatusEnum::Confirmed)
                ->count(),
            'totalEvents' => $events->count(),
            'eventsWithSales' => $events->where('tickets_sold', '>', 0)->count(),
            'salesByEvent' => $salesByEvent,
            'salesTrend' => $this->monthlyBookingCounts($organizerId),
            'topSellingEvents' => collect($salesByEvent)->sortByDesc('sold')->take(5)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevenueReport(int $organizerId): array
    {
        $grossRevenue = (float) $this->organizerBookingsQuery($organizerId)
            ->where('status', BookingStatusEnum::Confirmed)
            ->sum('ticket_price');

        $totalRefunded = (float) RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->sum('refund_amount');

        $revenueByEvent = $this->organizerEventsQuery($organizerId)
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
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

        return [
            'grossRevenue' => $grossRevenue,
            'totalRefunded' => $totalRefunded,
            'netRevenue' => $grossRevenue - $totalRefunded,
            'averagePerEvent' => count($revenueByEvent) > 0
                ? round($grossRevenue / count($revenueByEvent), 2)
                : 0,
            'revenueByEvent' => $revenueByEvent,
            'revenueTrend' => $this->monthlyRevenue($organizerId),
            'topRevenueEvents' => collect($revenueByEvent)->sortByDesc('revenue')->take(5)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttendeeReport(int $organizerId): array
    {
        $uniqueAttendees = (int) $this->organizerBookingsQuery($organizerId)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');

        $attendeesByEvent = $this->organizerEventsQuery($organizerId)
            ->withCount([
                'ticketBookings as attendee_count' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ])
            ->orderByDesc('attendee_count')
            ->get()
            ->map(fn (Event $event) => [
                'name' => $event->name,
                'count' => (int) $event->attendee_count,
                'date' => $event->date ? \Carbon\Carbon::parse($event->date)->format('M d, Y') : '—',
                'status' => ucfirst($event->status),
            ])
            ->values()
            ->all();

        $recentAttendees = $this->organizerBookingsQuery($organizerId)
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

        return [
            'totalAttendees' => $uniqueAttendees,
            'totalBookings' => (int) $this->organizerBookingsQuery($organizerId)->count(),
            'confirmedBookings' => (int) $this->organizerBookingsQuery($organizerId)
                ->where('status', BookingStatusEnum::Confirmed)
                ->count(),
            'attendeesByEvent' => $attendeesByEvent,
            'registrationTrend' => $this->monthlyBookingCounts($organizerId),
            'recentAttendees' => $recentAttendees,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementReport(int $organizerId): array
    {
        $events = $this->organizerEventsQuery($organizerId)
            ->withCount(['likes', 'comments', 'ratings'])
            ->withAvg('ratings', 'score')
            ->get();

        $totalLikes = (int) $events->sum('likes_count');
        $totalComments = (int) $events->sum('comments_count');
        $totalRatings = (int) $events->sum('ratings_count');

        $popularityByEvent = $events
            ->map(fn (Event $event) => [
                'name' => $event->name,
                'likes' => (int) $event->likes_count,
                'comments' => (int) $event->comments_count,
                'ratings' => (int) $event->ratings_count,
                'avg_rating' => $event->ratings_avg_score ? round((float) $event->ratings_avg_score, 1) : null,
                'score' => (int) $event->likes_count + (int) $event->comments_count + (int) $event->ratings_count,
            ])
            ->sortByDesc('score')
            ->values()
            ->all();

        return [
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalRatings' => $totalRatings,
            'averageRating' => $totalRatings > 0
                ? round((float) Rating::query()
                    ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
                    ->avg('score'), 1)
                : null,
            'popularityByEvent' => $popularityByEvent,
            'topEvents' => collect($popularityByEvent)->take(5)->values()->all(),
            'engagementTrend' => $this->monthlyEngagement($organizerId),
            'engagementBreakdown' => [
                ['label' => 'Likes', 'count' => $totalLikes],
                ['label' => 'Comments', 'count' => $totalComments],
                ['label' => 'Ratings', 'count' => $totalRatings],
            ],
        ];
    }

    private function organizerEventsQuery(int $organizerId)
    {
        return Event::query()->createdByOrganizer($organizerId);
    }

    private function organizerBookingsQuery(int $organizerId)
    {
        return ticketBooking::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId));
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthLabels(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('M Y'))
            ->values()
            ->all();
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
     * @return list<int>
     */
    private function monthlyBookingCounts(int $organizerId): array
    {
        $keys = $this->lastSixMonthKeys();

        $counts = $this->organizerBookingsQuery($organizerId)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        return collect($keys)
            ->map(fn (string $key) => (int) ($counts[$key] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @return list<float>
     */
    private function monthlyRevenue(int $organizerId): array
    {
        $keys = $this->lastSixMonthKeys();

        $totals = $this->organizerBookingsQuery($organizerId)
            ->where('status', BookingStatusEnum::Confirmed)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(ticket_price) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect($keys)
            ->map(fn (string $key) => round((float) ($totals[$key] ?? 0), 2))
            ->values()
            ->all();
    }

    /**
     * @return array{likes: list<int>, comments: list<int>, ratings: list<int>}
     */
    private function monthlyEngagement(int $organizerId): array
    {
        $keys = $this->lastSixMonthKeys();
        $since = now()->subMonths(5)->startOfMonth();

        $likes = $this->monthlyModelCounts(Like::class, $organizerId, $since);
        $comments = $this->monthlyModelCounts(Comment::class, $organizerId, $since);
        $ratings = $this->monthlyModelCounts(Rating::class, $organizerId, $since);

        return [
            'likes' => collect($keys)->map(fn (string $key) => (int) ($likes[$key] ?? 0))->values()->all(),
            'comments' => collect($keys)->map(fn (string $key) => (int) ($comments[$key] ?? 0))->values()->all(),
            'ratings' => collect($keys)->map(fn (string $key) => (int) ($ratings[$key] ?? 0))->values()->all(),
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @return array<string, int>
     */
    private function monthlyModelCounts(string $modelClass, int $organizerId, $since): array
    {
        return $modelClass::query()
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->all();
    }
}

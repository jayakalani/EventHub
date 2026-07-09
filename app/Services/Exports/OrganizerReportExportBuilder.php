<?php

namespace App\Services\Exports;

use App\Services\OrganizerReportService;
use App\Services\ReportExportService;

class OrganizerReportExportBuilder
{
    public function __construct(
        protected OrganizerReportService $reportService,
        protected ReportExportService $exportService,
    ) {}

    /**
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(int $organizerId, string $section): array
    {
        $reports = $this->reportService->getAllReports($organizerId);
        $labels = $reports['chartLabels'];

        return match ($section) {
            'sales' => $this->buildSales($reports['ticketSales'], $labels),
            'revenue' => $this->buildRevenue($reports['revenue'], $labels),
            'attendees' => $this->buildAttendees($reports['attendees'], $labels),
            'engagement' => $this->buildEngagement($reports['engagement'], $labels),
            default => abort(404),
        };
    }

    private function buildSales(array $data, array $labels): array
    {
        return [
            'title' => 'Organizer Reports — Ticket Sales',
            'summary' => [
                ['label' => 'Tickets Sold', 'value' => $data['totalTicketsSold']],
                ['label' => 'My Events', 'value' => $data['totalEvents']],
                ['label' => 'Events with Sales', 'value' => $data['eventsWithSales']],
            ],
            'tables' => [
                [
                    'heading' => 'Ticket Sales Trend',
                    'headers' => ['Month', 'Tickets Sold'],
                    'rows' => $this->exportService->trendRows($labels, $data['salesTrend']),
                ],
                [
                    'heading' => 'Sales by Event',
                    'headers' => ['Event', 'Sold', 'Capacity', 'Fill Rate %'],
                    'rows' => collect($data['salesByEvent'])->map(fn ($r) => [
                        $r['name'], $r['sold'], $r['capacity'], $r['fill_rate'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildRevenue(array $data, array $labels): array
    {
        return [
            'title' => 'Organizer Reports — Revenue',
            'summary' => [
                ['label' => 'Gross Revenue (LKR)', 'value' => number_format($data['grossRevenue'], 2)],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($data['netRevenue'], 2)],
                ['label' => 'Refunded (LKR)', 'value' => number_format($data['totalRefunded'], 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Revenue Trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['revenueTrend']),
                ],
                [
                    'heading' => 'Revenue by Event',
                    'headers' => ['Event', 'Status', 'Revenue (LKR)'],
                    'rows' => collect($data['revenueByEvent'])->map(fn ($r) => [
                        $r['name'], $r['status'], number_format($r['revenue'], 2),
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildAttendees(array $data, array $labels): array
    {
        return [
            'title' => 'Organizer Reports — Attendees',
            'summary' => [
                ['label' => 'Unique Attendees', 'value' => $data['totalAttendees']],
                ['label' => 'Total Bookings', 'value' => $data['totalBookings']],
                ['label' => 'Confirmed Bookings', 'value' => $data['confirmedBookings']],
            ],
            'tables' => [
                [
                    'heading' => 'Booking Trend',
                    'headers' => ['Month', 'Bookings'],
                    'rows' => $this->exportService->trendRows($labels, $data['registrationTrend']),
                ],
                [
                    'heading' => 'Attendees by Event',
                    'headers' => ['Event', 'Date', 'Status', 'Attendees'],
                    'rows' => collect($data['attendeesByEvent'])->map(fn ($r) => [
                        $r['name'], $r['date'], $r['status'], $r['count'],
                    ])->all(),
                ],
                [
                    'heading' => 'Recent Attendees',
                    'headers' => ['Name', 'Email', 'Event', 'Ticket', 'Status', 'Booked'],
                    'rows' => collect($data['recentAttendees'])->map(fn ($r) => [
                        $r['name'], $r['email'], $r['event'], $r['ticket'], $r['status'], $r['booked'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildEngagement(array $data, array $labels): array
    {
        return [
            'title' => 'Organizer Reports — Engagement',
            'summary' => [
                ['label' => 'Total Likes', 'value' => $data['totalLikes']],
                ['label' => 'Total Comments', 'value' => $data['totalComments']],
                ['label' => 'Total Ratings', 'value' => $data['totalRatings']],
                ['label' => 'Average Rating', 'value' => $data['averageRating'] ?? '—'],
            ],
            'tables' => [
                [
                    'heading' => 'Engagement Trend',
                    'headers' => ['Month', 'Likes', 'Comments', 'Ratings'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        $data['engagementTrend']['likes'][$i] ?? 0,
                        $data['engagementTrend']['comments'][$i] ?? 0,
                        $data['engagementTrend']['ratings'][$i] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Engagement by Event',
                    'headers' => ['Event', 'Likes', 'Comments', 'Ratings', 'Avg Rating', 'Score'],
                    'rows' => collect($data['popularityByEvent'])->map(fn ($r) => [
                        $r['name'], $r['likes'], $r['comments'], $r['ratings'], $r['avg_rating'] ?? '—', $r['score'],
                    ])->all(),
                ],
            ],
        ];
    }
}

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
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(int $organizerId, string $section, array $filters = []): array
    {
        $reports = $this->reportService->getAllReports($organizerId, $filters);
        $labels = $reports['chartLabels'];

        return match ($section) {
            'overview' => $this->buildOverview($reports, $labels),
            'revenue' => $this->buildRevenue($reports, $labels),
            'tickets' => $this->buildTickets($reports, $labels),
            'events' => $this->buildEvents($reports),
            'engagement' => $this->buildEngagement($reports, $labels),
            'audience' => $this->buildAudience($reports),
            'activity' => $this->buildActivity($reports),
            // Legacy aliases from older export buttons
            'sales' => $this->buildTickets($reports, $labels),
            'attendees' => $this->buildAudience($reports),
            default => abort(404),
        };
    }

    private function buildOverview(array $reports, array $labels): array
    {
        $sales = $reports['ticketSales'];
        $revenue = $reports['revenue'];
        $attendees = $reports['attendees'];
        $engagement = $reports['engagement'];
        $salesByCategory = $reports['salesByCategory'] ?? [];

        return [
            'title' => 'Organizer Reports — Overview',
            'summary' => [
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($revenue['netRevenue'], 2)],
                ['label' => 'Tickets Sold', 'value' => $sales['totalTicketsSold']],
                ['label' => 'Events', 'value' => $sales['totalEvents']],
                ['label' => 'Attendees', 'value' => $attendees['totalAttendees']],
                ['label' => 'Avg Rating', 'value' => $engagement['averageRating'] ?? '—'],
            ],
            'tables' => [
                [
                    'heading' => 'Revenue Trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $revenue['revenueTrend']),
                ],
                [
                    'heading' => 'Ticket Sales by Category',
                    'headers' => ['Category', 'Tickets', 'Share %'],
                    'rows' => collect($salesByCategory)->map(fn ($r) => [
                        $r['label'], $r['count'], $r['percentage'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildRevenue(array $reports, array $labels): array
    {
        $data = $reports['revenue'];

        return [
            'title' => 'Organizer Reports — Revenue',
            'summary' => [
                ['label' => 'Gross Revenue (LKR)', 'value' => number_format($data['grossRevenue'], 2)],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($data['netRevenue'], 2)],
                ['label' => 'Refunded (LKR)', 'value' => number_format($data['totalRefunded'], 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Monthly Revenue',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['revenueTrend']),
                ],
                [
                    'heading' => 'Cumulative Revenue',
                    'headers' => ['Month', 'Cumulative (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['cumulativeRevenueTrend'] ?? []),
                ],
                [
                    'heading' => 'Refunds vs Sales',
                    'headers' => ['Month', 'Confirmed Sales (LKR)', 'Refunds (LKR)'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        number_format((float) ($data['revenueTrend'][$i] ?? 0), 2),
                        number_format((float) ($data['refundTrend'][$i] ?? 0), 2),
                    ])->all(),
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

    private function buildTickets(array $reports, array $labels): array
    {
        $sales = $reports['ticketSales'];
        $ticketTypeTrend = $reports['ticketTypeTrend'] ?? [];
        $conversionFunnel = $reports['conversionFunnel'] ?? [];

        $typeSeries = collect($ticketTypeTrend);
        $typeLabels = $typeSeries->pluck('label')->all();
        $typeTrendRows = collect($labels)->map(function ($month, $i) use ($typeSeries) {
            $row = [$month];
            foreach ($typeSeries as $series) {
                $row[] = $series['data'][$i] ?? 0;
            }

            return $row;
        })->all();

        return [
            'title' => 'Organizer Reports — Tickets',
            'summary' => [
                ['label' => 'Tickets Sold', 'value' => $sales['totalTicketsSold']],
                ['label' => 'Events', 'value' => $sales['totalEvents']],
                ['label' => 'Events with Sales', 'value' => $sales['eventsWithSales']],
            ],
            'tables' => [
                [
                    'heading' => 'Ticket Sales Over Time',
                    'headers' => ['Month', 'Tickets Sold'],
                    'rows' => $this->exportService->trendRows($labels, $sales['salesTrend']),
                ],
                [
                    'heading' => 'Ticket Type Trend',
                    'headers' => array_merge(['Month'], $typeLabels),
                    'rows' => count($typeLabels) ? $typeTrendRows : [['—', 'No ticket-type data']],
                ],
                [
                    'heading' => 'Conversion Funnel',
                    'headers' => ['Stage', 'Count', 'Rate %'],
                    'rows' => collect($conversionFunnel)->map(fn ($r) => [
                        $r['label'], $r['count'], $r['rate'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Sales by Event',
                    'headers' => ['Event', 'Sold', 'Capacity', 'Fill Rate %'],
                    'rows' => collect($sales['salesByEvent'])->map(fn ($r) => [
                        $r['name'], $r['sold'], $r['capacity'], $r['fill_rate'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildEvents(array $reports): array
    {
        $performance = $reports['eventPerformance'] ?? [];
        $top = collect($performance)
            ->sortByDesc(fn ($event) => ((float) $event['revenue']) + ((int) $event['tickets_sold'] * 100))
            ->take(10)
            ->values();

        return [
            'title' => 'Organizer Reports — Events',
            'summary' => [
                ['label' => 'Events Listed', 'value' => count($performance)],
                ['label' => 'Total Tickets Sold', 'value' => collect($performance)->sum('tickets_sold')],
                ['label' => 'Total Revenue (LKR)', 'value' => number_format((float) collect($performance)->sum('revenue'), 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Event Performance',
                    'headers' => ['Event', 'Tickets Sold', 'Revenue (LKR)', 'Fill Rate %', 'Rating', 'Status'],
                    'rows' => collect($performance)->map(fn ($r) => [
                        $r['name'],
                        $r['tickets_sold'],
                        number_format($r['revenue'], 2),
                        $r['fill_rate'],
                        $r['rating'] ?? '—',
                        $r['status'],
                    ])->all(),
                ],
                [
                    'heading' => 'Top Performing Events',
                    'headers' => ['Event', 'Tickets', 'Revenue (LKR)', 'Fill Rate %', 'Status'],
                    'rows' => $top->map(fn ($r) => [
                        $r['name'], $r['tickets_sold'], number_format($r['revenue'], 2), $r['fill_rate'], $r['status'],
                    ])->all(),
                ],
                [
                    'heading' => 'Revenue vs Fill Rate',
                    'headers' => ['Event', 'Fill Rate %', 'Revenue (LKR)'],
                    'rows' => collect($performance)->map(fn ($r) => [
                        $r['name'], $r['fill_rate'], number_format($r['revenue'], 2),
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildEngagement(array $reports, array $labels): array
    {
        $data = $reports['engagement'];
        $before = $data['engagementBeforeEvent'] ?? null;
        $vsSales = $data['engagementVsSales'] ?? [];

        $tables = [
            [
                'heading' => 'Engagement Over Time',
                'headers' => ['Month', 'Likes', 'Saves', 'Comments', 'Ratings'],
                'rows' => collect($labels)->map(fn ($label, $i) => [
                    $label,
                    $data['engagementTrend']['likes'][$i] ?? 0,
                    $data['engagementTrend']['saves'][$i] ?? 0,
                    $data['engagementTrend']['comments'][$i] ?? 0,
                    $data['engagementTrend']['ratings'][$i] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Engagement by Event',
                'headers' => ['Event', 'Likes', 'Saves', 'Comments', 'Ratings', 'Score', 'Tickets Sold'],
                'rows' => collect($data['popularityByEvent'])->map(fn ($r) => [
                    $r['name'],
                    $r['likes'],
                    $r['saves'] ?? 0,
                    $r['comments'],
                    $r['ratings'],
                    $r['score'],
                    $r['tickets_sold'] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Engagement vs Ticket Sales',
                'headers' => ['Event', 'Engagement Score', 'Tickets Sold', 'Likes', 'Comments', 'Saves'],
                'rows' => collect($vsSales)->map(fn ($r) => [
                    $r['name'],
                    $r['engagement'],
                    $r['tickets_sold'],
                    $r['likes'] ?? 0,
                    $r['comments'] ?? 0,
                    $r['saves'] ?? 0,
                ])->all(),
            ],
        ];

        if (is_array($before) && ! empty($before['labels'])) {
            $tables[] = [
                'heading' => 'Before Event Day (−28 → Event Day)',
                'headers' => ['Day Offset', 'Likes', 'Saves', 'Comments', 'Ratings', 'Tickets'],
                'rows' => collect($before['labels'])->map(fn ($label, $i) => [
                    $label,
                    $before['likes'][$i] ?? 0,
                    $before['saves'][$i] ?? 0,
                    $before['comments'][$i] ?? 0,
                    $before['ratings'][$i] ?? 0,
                    $before['tickets'][$i] ?? 0,
                ])->all(),
            ];
        }

        return [
            'title' => 'Organizer Reports — Engagement',
            'summary' => [
                ['label' => 'Total Likes', 'value' => $data['totalLikes']],
                ['label' => 'Total Saves', 'value' => $data['totalSaves'] ?? 0],
                ['label' => 'Total Comments', 'value' => $data['totalComments']],
                ['label' => 'Total Ratings', 'value' => $data['totalRatings']],
                ['label' => 'Average Rating', 'value' => $data['averageRating'] ?? '—'],
            ],
            'tables' => $tables,
        ];
    }

    private function buildAudience(array $reports): array
    {
        $data = $reports['attendees'];
        $demographics = $data['demographics'] ?? ['age' => [], 'gender' => [], 'location' => []];
        $topCustomers = $data['topCustomers'] ?? [];

        return [
            'title' => 'Organizer Reports — Audience',
            'summary' => [
                ['label' => 'Unique Attendees', 'value' => $data['totalAttendees']],
                ['label' => 'Confirmation Rate %', 'value' => $data['confirmationRate'] ?? 0],
                ['label' => 'New Attendees', 'value' => $data['newAttendees'] ?? 0],
                ['label' => 'Repeat Attendees', 'value' => $data['repeatAttendees'] ?? 0],
                ['label' => 'Avg Spend / Guest (LKR)', 'value' => number_format($data['avgSpendPerAttendee'] ?? 0, 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Age Groups',
                    'headers' => ['Group', 'Count'],
                    'rows' => collect($demographics['age'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Gender',
                    'headers' => ['Gender', 'Count'],
                    'rows' => collect($demographics['gender'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Location',
                    'headers' => ['Location', 'Count'],
                    'rows' => collect($demographics['location'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Top Customers',
                    'headers' => ['Name', 'Email', 'Tickets', 'Spend (LKR)'],
                    'rows' => collect($topCustomers)->map(fn ($r) => [
                        $r['name'], $r['email'], $r['tickets'], number_format($r['spend'], 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Attendees by Event',
                    'headers' => ['Event', 'Date', 'Status', 'Attendees'],
                    'rows' => collect($data['attendeesByEvent'] ?? [])->map(fn ($r) => [
                        $r['name'], $r['date'] ?? '—', $r['status'] ?? '—', $r['count'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildActivity(array $reports): array
    {
        $transactions = $reports['recentTransactions'] ?? [];

        return [
            'title' => 'Organizer Reports — Activity',
            'summary' => [
                ['label' => 'Recent Transactions', 'value' => count($transactions)],
            ],
            'tables' => [
                [
                    'heading' => 'Recent Transactions',
                    'headers' => ['Customer', 'Email', 'Event', 'Category', 'Amount (LKR)', 'Status', 'When'],
                    'rows' => collect($transactions)->map(fn ($r) => [
                        $r['customer'],
                        $r['email'] ?? '—',
                        $r['event'],
                        $r['category'] ?? '—',
                        number_format($r['amount'], 2),
                        $r['status'],
                        $r['relative'] ?? ($r['when'] ?? '—'),
                    ])->all(),
                ],
            ],
        ];
    }
}

<?php

namespace App\Services\Exports;

use App\Services\OrganizerDashboardService;

class OrganizerDashboardExportBuilder
{
    public function __construct(
        protected OrganizerDashboardService $dashboardService,
    ) {}

    /**
     * @param  array{
     *     kpi_event?: int|null,
     *     goal_event?: int|null,
     *     chart_event?: int|null,
     *     engagement_event?: int|null
     * }  $filters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(int $organizerId, array $filters = []): array
    {
        $dashboard = $this->dashboardService->getDashboardData(
            $organizerId,
            $filters['kpi_event'] ?? null,
            $filters['goal_event'] ?? null,
            $filters['chart_event'] ?? null,
            $filters['engagement_event'] ?? null,
        );

        $kpiFilter = $dashboard['kpiFilter'] ?? [];
        $chartFilter = $dashboard['chartFilter'] ?? [];
        $engagementFilter = $dashboard['engagement']['filter'] ?? [];
        $revenueGoal = $dashboard['revenueGoal'] ?? [];
        $kpis = $dashboard['kpis'] ?? [];
        $charts = $dashboard['charts']['periods']['month'] ?? [];
        $engagement = $dashboard['engagement'] ?? [];
        $performance = $dashboard['performance'] ?? [];
        $upcoming = $dashboard['upcomingEvents'] ?? [];
        $purchases = $dashboard['recentPurchases'] ?? [];
        $activity = $dashboard['recentActivity'] ?? [];
        $today = $dashboard['todaySummary'] ?? [];
        $statusSummary = $dashboard['statusSummary'] ?? [];

        $kpiScope = ! empty($kpiFilter['selectedEventName'])
            ? $kpiFilter['selectedEventName']
            : 'All Events';
        $chartScope = ! empty($chartFilter['selectedEventName'])
            ? $chartFilter['selectedEventName']
            : 'All Events';
        $engagementScope = ! empty($engagementFilter['selectedEventName'])
            ? $engagementFilter['selectedEventName']
            : 'All Events';
        $goalScope = ! empty($revenueGoal['selectedEventName'])
            ? $revenueGoal['selectedEventName']
            : ($revenueGoal['label'] ?? 'Monthly');

        return [
            'title' => 'Organizer Dashboard',
            'subtitle' => 'Events, ticket sales, revenue, and engagement snapshot',
            'filters' => [
                ['label' => 'KPI scope', 'value' => $kpiScope],
                ['label' => 'Analytics scope', 'value' => $chartScope],
                ['label' => 'Engagement scope', 'value' => $engagementScope],
                ['label' => 'Revenue goal scope', 'value' => $goalScope],
            ],
            'kpis' => [
                ['label' => 'Today — Events', 'value' => $today['eventsToday'] ?? 0],
                ['label' => 'Today — Tickets', 'value' => $today['ticketsSold'] ?? 0],
                ['label' => 'Today — Revenue', 'value' => 'LKR '.number_format((float) ($today['revenue'] ?? 0), 0)],
                ...collect($kpis)->take(5)->map(fn ($kpi) => [
                    'label' => $kpi['label'] ?? 'KPI',
                    'value' => $kpi['value'] ?? '—',
                ])->all(),
            ],
            'summary' => [
                ['label' => 'KPI scope', 'value' => $kpiScope],
                ['label' => 'Analytics scope', 'value' => $chartScope],
                ['label' => 'Engagement scope', 'value' => $engagementScope],
                ['label' => 'Revenue goal scope', 'value' => $goalScope],
                ['label' => 'Today — Events', 'value' => $today['eventsToday'] ?? 0],
                ['label' => 'Today — Tickets', 'value' => $today['ticketsSold'] ?? 0],
                ['label' => 'Today — Revenue (LKR)', 'value' => number_format((float) ($today['revenue'] ?? 0), 2)],
                ...collect($kpis)->map(fn ($kpi) => [
                    'label' => $kpi['label'] ?? 'KPI',
                    'value' => $kpi['value'] ?? '—',
                ])->all(),
                [
                    'label' => 'Revenue goal progress',
                    'value' => number_format((float) ($revenueGoal['progress'] ?? 0), 1).'% · LKR '
                        .number_format((float) ($revenueGoal['current'] ?? 0), 0)
                        .' / '.number_format((float) ($revenueGoal['goal'] ?? 0), 0),
                ],
                [
                    'label' => 'Avg rating',
                    'value' => $engagement['average_rating'] !== null
                        ? number_format((float) $engagement['average_rating'], 1)
                        : '—',
                ],
                ['label' => 'Likes', 'value' => $engagement['likes'] ?? 0],
                ['label' => 'Saves', 'value' => $engagement['saves'] ?? 0],
                ['label' => 'Comments', 'value' => $engagement['comments'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'Event status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($statusSummary)->map(fn ($row) => [
                        $row['label'] ?? '',
                        $row['count'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Analytics — '.$chartScope.' ('.($charts['label'] ?? 'This Month').')',
                    'headers' => ['Period', 'Revenue (LKR)', 'Tickets'],
                    'rows' => collect($charts['revenue']['labels'] ?? [])->map(function ($label, $index) use ($charts) {
                        return [
                            $label,
                            number_format((float) ($charts['revenue']['series'][$index] ?? 0), 2),
                            (int) ($charts['tickets']['series'][$index] ?? 0),
                        ];
                    })->all(),
                ],
                [
                    'heading' => 'Event performance',
                    'headers' => ['Event', 'Date', 'Status', 'Sold', 'Capacity', 'Fill %', 'Revenue (LKR)'],
                    'rows' => collect($performance)->map(fn ($event) => [
                        $event['name'] ?? '',
                        $event['date'] ?? '—',
                        $event['status'] ?? '',
                        $event['sold'] ?? 0,
                        $event['capacity'] ?? 0,
                        $event['fill_rate'] ?? 0,
                        number_format((float) ($event['revenue'] ?? 0), 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Upcoming events',
                    'headers' => ['Event', 'Date', 'Time', 'Place', 'Sold', 'Capacity'],
                    'rows' => collect($upcoming)->map(fn ($event) => [
                        $event['name'] ?? '',
                        $event['date'] ?? '—',
                        $event['time'] ?? '—',
                        $event['place'] ?? '—',
                        $event['sold'] ?? 0,
                        $event['capacity'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Recent ticket purchases',
                    'headers' => ['Customer', 'Event', 'Amount (LKR)', 'When'],
                    'rows' => collect($purchases)->map(fn ($purchase) => [
                        $purchase['buyer'] ?? '—',
                        $purchase['event'] ?? '—',
                        number_format((float) ($purchase['amount'] ?? 0), 2),
                        $purchase['booked_at'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Recent activity',
                    'headers' => ['Activity', 'Detail', 'When'],
                    'rows' => collect($activity)->map(fn ($item) => [
                        $item['title'] ?? ($item['label'] ?? '—'),
                        $item['description'] ?? ($item['detail'] ?? '—'),
                        $item['time'] ?? '—',
                    ])->all(),
                ],
            ],
        ];
    }
}

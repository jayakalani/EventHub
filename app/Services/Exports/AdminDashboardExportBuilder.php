<?php

namespace App\Services\Exports;

use App\Services\AdminReportService;

class AdminDashboardExportBuilder
{
    public function __construct(
        protected AdminReportService $adminReportService,
    ) {}

    /**
     * @param  array{
     *     organizer?: int|null,
     *     event?: int|null,
     *     cro?: int|null
     * }  $filters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(array $filters = []): array
    {
        $organizerId = $filters['organizer'] ?? null;
        $eventId = $filters['event'] ?? null;
        $croId = $filters['cro'] ?? null;

        $dashboard = $this->adminReportService->getDashboardData(
            $organizerId,
            $eventId,
            $organizerId,
            $eventId,
            $croId,
            $eventId,
        );

        $scopeFilter = $dashboard['scopeFilter'] ?? [];
        $paymentScopeFilter = $dashboard['paymentScopeFilter'] ?? [];
        $supportScopeFilter = $dashboard['supportScopeFilter'] ?? [];
        $kpis = $dashboard['kpis'] ?? [];
        $platformAnalytics = $dashboard['platformAnalytics'] ?? [];
        $payments = $dashboard['payments'] ?? [];
        $support = $dashboard['support'] ?? [];
        $today = $dashboard['todaySummary'] ?? [];
        $users = $dashboard['users'] ?? [];
        $organizerPerformance = $dashboard['organizerPerformance'] ?? [];
        $chartLabels = $dashboard['chartLabels'] ?? [];
        $charts = $dashboard['charts'] ?? [];

        $kpiScope = match ($scopeFilter['scope'] ?? 'global') {
            'event' => 'Event: '.($scopeFilter['selectedEventName'] ?? '—'),
            'organizer' => 'Organizer: '.($scopeFilter['selectedOrganizerName'] ?? '—'),
            default => 'All (platform-wide)',
        };
        $paymentScope = match ($paymentScopeFilter['scope'] ?? 'global') {
            'event' => 'Event: '.($paymentScopeFilter['selectedEventName'] ?? '—'),
            'organizer' => 'Organizer: '.($paymentScopeFilter['selectedOrganizerName'] ?? '—'),
            default => 'All (platform-wide)',
        };
        $supportScope = match ($supportScopeFilter['scope'] ?? 'global') {
            'event' => 'Event: '.($supportScopeFilter['selectedEventName'] ?? '—'),
            'cro' => 'CRO: '.($supportScopeFilter['selectedCroName'] ?? '—'),
            default => 'All (platform-wide)',
        };

        return [
            'title' => 'Administrator Dashboard',
            'summary' => [
                ['label' => 'KPI / analytics scope', 'value' => $kpiScope],
                ['label' => 'Payment scope', 'value' => $paymentScope],
                ['label' => 'Support scope', 'value' => $supportScope],
                ['label' => 'Today — Organizers', 'value' => $today['newOrganizers'] ?? 0],
                ['label' => 'Today — Events', 'value' => $today['newEvents'] ?? 0],
                ['label' => 'Today — Tickets', 'value' => $today['ticketsSold'] ?? 0],
                ['label' => 'Today — Support', 'value' => $today['supportRequests'] ?? 0],
                ...collect($kpis)->map(fn ($kpi) => [
                    'label' => ($kpi['label'] ?? 'KPI').(isset($kpi['sub']) ? ' ('.$kpi['sub'].')' : ''),
                    'value' => $kpi['value'] ?? '—',
                ])->all(),
                ['label' => 'Active events', 'value' => $platformAnalytics['active'] ?? 0],
                ['label' => 'Upcoming events', 'value' => $platformAnalytics['upcoming'] ?? 0],
                ['label' => 'Postponed events', 'value' => $platformAnalytics['postponed'] ?? 0],
                ['label' => 'Completed events', 'value' => $platformAnalytics['completed'] ?? 0],
                ['label' => 'Cancelled events', 'value' => $platformAnalytics['cancelled'] ?? 0],
                ['label' => 'Payments — Successful', 'value' => $payments['completed'] ?? 0],
                ['label' => 'Payments — Pending', 'value' => $payments['pending'] ?? 0],
                ['label' => 'Payments — Refunded', 'value' => $payments['refunded'] ?? 0],
                ['label' => 'Payments — Failed', 'value' => $payments['failed'] ?? 0],
                ['label' => 'Open inquiries', 'value' => $support['openInquiries'] ?? 0],
                ['label' => 'Open complaints', 'value' => $support['openComplaints'] ?? 0],
                ['label' => 'Resolved today', 'value' => $support['resolvedToday'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'Users by role',
                    'headers' => ['Role', 'Count'],
                    'rows' => collect($users['byRole'] ?? [])->map(fn ($role) => [
                        $role['label'] ?? '',
                        $role['count'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Organizer performance',
                    'headers' => ['Organizer', 'Events', 'Tickets sold', 'Revenue'],
                    'rows' => collect($organizerPerformance)->map(fn ($row) => [
                        $row['name'] ?? '',
                        $row['events'] ?? 0,
                        $row['ticketsSold'] ?? 0,
                        $row['revenueLabel'] ?? number_format((float) ($row['revenue'] ?? 0), 2),
                    ])->all(),
                ],
                [
                    'heading' => 'User growth (6 months)',
                    'headers' => ['Month', 'New users'],
                    'rows' => collect($chartLabels)->map(fn ($label, $index) => [
                        $label,
                        $charts['userGrowth'][$index] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Revenue trend (6 months)',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => collect($chartLabels)->map(fn ($label, $index) => [
                        $label,
                        number_format((float) ($charts['revenue'][$index] ?? 0), 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Ticket sales by category',
                    'headers' => ['Category', 'Tickets'],
                    'rows' => collect($charts['ticketSalesByCategory'] ?? [])->map(fn ($row) => [
                        $row['label'] ?? ($row['name'] ?? '—'),
                        $row['count'] ?? ($row['value'] ?? 0),
                    ])->all(),
                ],
                [
                    'heading' => 'Events by category',
                    'headers' => ['Category', 'Events'],
                    'rows' => collect($charts['eventsByCategory'] ?? [])->map(fn ($row) => [
                        $row['label'] ?? ($row['name'] ?? '—'),
                        $row['count'] ?? ($row['value'] ?? 0),
                    ])->all(),
                ],
            ],
        ];
    }
}

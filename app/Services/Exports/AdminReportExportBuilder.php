<?php

namespace App\Services\Exports;

use App\Http\Controllers\Admin\SupportReportController;
use App\Services\AdminReportService;
use App\Services\ReportExportService;

class AdminReportExportBuilder
{
    public function __construct(
        protected AdminReportService $reportService,
        protected ReportExportService $exportService,
        protected AdminDashboardExportBuilder $dashboardExportBuilder,
        protected SupportReportController $supportReportController,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null}|null  $dateFilters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(string $section, ?int $organizerId = null, ?int $eventId = null, ?array $dateFilters = null, ?int $croId = null): array
    {
        $section = match ($section) {
            'admin' => 'events',
            'full', '' => 'all',
            default => $section,
        };

        $needsInsights = in_array($section, ['overview', 'activity', 'events', 'users', 'payments', 'all'], true);
        $reports = $needsInsights
            ? $this->reportService->getAllReports(
                $organizerId,
                $eventId,
                $dateFilters['from'] ?? null,
                $dateFilters['to'] ?? null,
            )
            : null;
        $labels = $reports['chartLabels'] ?? [];
        $scopeFilter = $reports['scopeFilter'] ?? [];
        $scopeSuffix = match ($scopeFilter['scope'] ?? 'global') {
            'event' => ' — '.($scopeFilter['selectedEventName'] ?? 'Event'),
            'organizer' => ' — '.($scopeFilter['selectedOrganizerName'] ?? 'Organizer'),
            default => '',
        };

        $payload = match ($section) {
            'all' => $this->buildAll($organizerId, $eventId, $dateFilters, $croId, $reports ?? [], $labels),
            'performance' => $this->buildPerformance($organizerId, $eventId, $dateFilters),
            'support' => $this->buildSupport($croId, $eventId, $dateFilters),
            'overview' => $this->buildOverview($reports['overview'] ?? [], $labels),
            'activity' => $this->buildActivity($reports['overview'] ?? [], $labels),
            'events' => $this->buildAdmin($reports['admin'] ?? [], $labels),
            'users' => $this->buildUsers($reports['users'] ?? [], $labels),
            'payments' => $this->buildPayments($reports['payments'] ?? [], $labels),
            default => abort(404),
        };

        if ($scopeSuffix !== '' && in_array($section, ['overview', 'activity', 'events', 'payments', 'all'], true)) {
            $payload['title'] .= $scopeSuffix;
        }

        $alreadyHasDateRange = collect($payload['summary'] ?? [])
            ->contains(fn (array $row) => ($row['label'] ?? '') === 'Date range');

        if (! $alreadyHasDateRange) {
            $from = $dateFilters['from'] ?? null;
            $to = $dateFilters['to'] ?? null;
            $dateLabel = match (true) {
                filled($from) && filled($to) => $from.' → '.$to,
                filled($from) => 'From '.$from,
                filled($to) => 'Until '.$to,
                default => 'All time',
            };
            array_unshift($payload['summary'], ['label' => 'Date range', 'value' => $dateLabel]);
        }

        return $payload;
    }

    /**
     * @param  array{from?: string|null, to?: string|null}|null  $dateFilters
     * @param  array<string, mixed>  $reports
     * @param  list<string>  $labels
     * @return array{
     *     title: string,
     *     subtitle: string,
     *     summary: list<array{label: string, value: string|int|float}>,
     *     sections: list<array{key: string, title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>>}>,
     *     tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>>}
     * }
     */
    private function buildAll(
        ?int $organizerId,
        ?int $eventId,
        ?array $dateFilters,
        ?int $croId,
        array $reports,
        array $labels,
    ): array {
        $sectionPayloads = [
            'performance' => $this->buildPerformance($organizerId, $eventId, $dateFilters),
            'support' => $this->buildSupport($croId, $eventId, $dateFilters),
            'overview' => $this->buildOverview($reports['overview'] ?? [], $labels),
            'activity' => $this->buildActivity($reports['overview'] ?? [], $labels),
            'events' => $this->buildAdmin($reports['admin'] ?? [], $labels),
            'users' => $this->buildUsers($reports['users'] ?? [], $labels),
            'payments' => $this->buildPayments($reports['payments'] ?? [], $labels),
        ];

        $sections = [];
        $tables = [];

        foreach ($sectionPayloads as $key => $sectionPayload) {
            $sectionTitle = trim(str_ireplace('Admin Reports — ', '', (string) ($sectionPayload['title'] ?? 'Section'))) ?: 'Section';
            $sectionTables = $sectionPayload['tables'] ?? [];

            $sections[] = [
                'key' => $key,
                'title' => $sectionTitle,
                'summary' => $sectionPayload['summary'] ?? [],
                'tables' => $sectionTables,
            ];

            foreach ($sectionTables as $table) {
                $tables[] = [
                    'heading' => $sectionTitle.' — '.($table['heading'] ?? 'Data'),
                    'headers' => $table['headers'] ?? [],
                    'rows' => $table['rows'] ?? [],
                ];
            }
        }

        return [
            'title' => 'Admin Reports — Full Dashboard',
            'subtitle' => 'Performance, Support, Overview, Activity, Events, Users, and Payments',
            'summary' => $sectionPayloads['overview']['summary'] ?? [],
            'sections' => $sections,
            'tables' => $tables,
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null}|null  $dateFilters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    private function buildPerformance(?int $organizerId, ?int $eventId, ?array $dateFilters): array
    {
        $payload = $this->dashboardExportBuilder->build([
            'organizer' => $organizerId,
            'event' => $eventId,
            'from' => $dateFilters['from'] ?? null,
            'to' => $dateFilters['to'] ?? null,
        ]);
        $payload['title'] = 'Admin Reports — Performance';

        return $payload;
    }

    /**
     * @param  array{from?: string|null, to?: string|null}|null  $dateFilters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    private function buildSupport(?int $croId, ?int $eventId, ?array $dateFilters): array
    {
        $report = $this->supportReportController->buildReportData(
            $croId,
            null,
            $eventId,
            $dateFilters['from'] ?? null,
            $dateFilters['to'] ?? null,
        );

        $ticketRows = function ($tickets): array {
            return collect($tickets)
                ->take(10)
                ->map(fn ($ticket) => [
                    (string) ($ticket->subject ?? '—'),
                    (string) ($ticket->user?->full_name ?? '—'),
                    (string) ($ticket->event?->name ?? 'General'),
                    $ticket->status?->label() ?? '—',
                    $ticket->created_at?->format('d M Y, H:i') ?? '—',
                ])
                ->all();
        };

        return [
            'title' => 'Admin Reports — Support',
            'summary' => [
                ['label' => 'Scope', 'value' => $report['scopeCaption'] ?? 'All CROs'],
                ['label' => 'Total inquiries', 'value' => $report['totalInquiries'] ?? 0],
                ['label' => 'Total complaints', 'value' => $report['totalComplaints'] ?? 0],
                ['label' => 'Resolved jobs', 'value' => $report['resolvedCount'] ?? 0],
                ['label' => 'Pending jobs', 'value' => $report['pendingCount'] ?? 0],
                ...collect($report['slaAging'] ?? [])->map(fn ($bucket) => [
                    'label' => 'SLA — '.($bucket['label'] ?? 'Open'),
                    'value' => $bucket['count'] ?? 0,
                ])->all(),
            ],
            'tables' => [
                [
                    'heading' => 'Support volume (weekly)',
                    'headers' => ['Week', 'Inquiries', 'Complaints'],
                    'rows' => collect($report['volumeTrend']['labels'] ?? [])->map(fn ($label, $index) => [
                        $label,
                        $report['volumeTrend']['inquiries'][$index] ?? 0,
                        $report['volumeTrend']['complaints'][$index] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Open ticket SLA',
                    'headers' => ['Level', 'Open tickets'],
                    'rows' => collect($report['slaAging'] ?? [])->map(fn ($bucket) => [
                        $bucket['label'] ?? '',
                        $bucket['count'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Recent inquiries',
                    'headers' => ['Subject', 'User', 'Event', 'Status', 'Submitted'],
                    'rows' => $ticketRows($report['recentInquiries'] ?? []),
                ],
                [
                    'heading' => 'Recent complaints',
                    'headers' => ['Subject', 'User', 'Event', 'Status', 'Submitted'],
                    'rows' => $ticketRows($report['recentComplaints'] ?? []),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  list<string>  $labels
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>>}}
     */
    private function buildOverview(array $overview, array $labels): array
    {
        $kpis = $overview['kpis'] ?? [];
        $highlights = $overview['highlights'] ?? [];

        return [
            'title' => 'Admin Reports — Overview',
            'summary' => [
                ['label' => $kpis['usersLabel'] ?? 'Total Users', 'value' => $kpis['totalUsers'] ?? 0],
                ['label' => 'Total Events', 'value' => $kpis['totalEvents'] ?? 0],
                ['label' => 'Tickets Sold', 'value' => $kpis['ticketsSold'] ?? 0],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format((float) ($kpis['platformRevenue'] ?? 0), 2)],
                ['label' => 'Today — Users / attendees', 'value' => $highlights['newUsers'] ?? 0],
                ['label' => 'Today — Events', 'value' => $highlights['newEvents'] ?? 0],
                ['label' => 'Today — Tickets', 'value' => $highlights['ticketsSold'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'User growth',
                    'headers' => ['Month', 'New users'],
                    'rows' => $this->exportService->trendRows($labels, $overview['userGrowth'] ?? []),
                ],
                [
                    'heading' => 'Users by role',
                    'headers' => ['Role', 'Count', 'Share'],
                    'rows' => collect($overview['userDistribution'] ?? [])->map(fn ($row) => [
                        $row['label'] ?? '',
                        $row['count'] ?? 0,
                        number_format((float) ($row['percent'] ?? 0), 1).'%',
                    ])->all(),
                ],
                [
                    'heading' => 'Revenue trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => collect($overview['revenueTrend']['formatted'] ?? [])->map(fn ($row) => [
                        $row['month'] ?? '',
                        number_format((float) ($row['amount'] ?? 0), 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Events by category',
                    'headers' => ['Category', 'Events'],
                    'rows' => collect($overview['eventsByCategory'] ?? [])->map(fn ($row) => [
                        $row['label'] ?? ($row['name'] ?? '—'),
                        $row['count'] ?? ($row['value'] ?? 0),
                    ])->all(),
                ],
                [
                    'heading' => 'Organizer performance',
                    'headers' => ['Organizer', 'Events', 'Tickets sold', 'Revenue'],
                    'rows' => collect($overview['organizerPerformance'] ?? [])->map(fn ($row) => [
                        $row['name'] ?? '',
                        $row['events'] ?? 0,
                        $row['ticketsSold'] ?? 0,
                        $row['revenueLabel'] ?? number_format((float) ($row['revenue'] ?? 0), 2),
                    ])->all(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  list<string>  $labels
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>>}}
     */
    private function buildActivity(array $overview, array $labels): array
    {
        $system = $this->reportService->getSystemReports();

        return [
            'title' => 'Admin Reports — Activity',
            'summary' => [
                ['label' => 'Audit logs', 'value' => $system['totalAuditLogs'] ?? 0],
                ['label' => 'Audit logs today', 'value' => $system['auditLogsToday'] ?? 0],
                ['label' => 'Audit logs this week', 'value' => $system['auditLogsThisWeek'] ?? 0],
                ['label' => 'Total inquiries', 'value' => $system['totalInquiries'] ?? 0],
                ['label' => 'Total complaints', 'value' => $system['totalComplaints'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'Recent payments',
                    'headers' => ['Customer', 'Event', 'Amount (LKR)', 'Status'],
                    'rows' => collect($overview['recentPayments'] ?? [])->map(fn ($row) => [
                        $row['customer'] ?? '',
                        $row['event'] ?? '—',
                        number_format((float) ($row['amount'] ?? 0), 2),
                        $row['statusLabel'] ?? ($row['status'] ?? ''),
                    ])->all(),
                ],
                [
                    'heading' => 'Activity trend',
                    'headers' => ['Month', 'Audit events'],
                    'rows' => $this->exportService->trendRows($labels, $system['activityTrend'] ?? []),
                ],
                [
                    'heading' => 'Audit by action',
                    'headers' => ['Action', 'Count'],
                    'rows' => collect($system['auditByAction'] ?? [])->map(fn ($row) => [
                        $row['label'] ?? '',
                        $row['count'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Recent audit logs',
                    'headers' => ['User', 'Action', 'Model', 'IP', 'When'],
                    'rows' => collect($system['recentAuditLogs'] ?? [])->map(fn ($row) => [
                        $row['user'] ?? '',
                        $row['action'] ?? '',
                        $row['model'] ?? '',
                        $row['ip'] ?? '—',
                        $row['time'] ?? '—',
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildAdmin(array $data, array $labels): array
    {
        return [
            'title' => 'Admin Reports — Events',
            'summary' => [
                ['label' => 'Total Users', 'value' => $data['totalUsers'] ?? 0],
                ['label' => 'Total Events', 'value' => $data['totalEvents'] ?? 0],
                ['label' => 'Tickets Sold', 'value' => $data['totalTicketsSold'] ?? 0],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format((float) ($data['netRevenue'] ?? 0), 2)],
                ['label' => 'Artists', 'value' => $data['totalArtists'] ?? 0],
                ['label' => 'Categories', 'value' => $data['totalCategories'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'Events by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['eventsByStatus'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Top Event Categories',
                    'headers' => ['Category', 'Events'],
                    'rows' => collect($data['topCategories'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Ticket Sales Trend',
                    'headers' => ['Month', 'Tickets Sold'],
                    'rows' => $this->exportService->trendRows($labels, $data['ticketSalesTrend'] ?? []),
                ],
                [
                    'heading' => 'Platform Growth',
                    'headers' => ['Month', 'New Users', 'New Events'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        $data['platformGrowth'][$i] ?? 0,
                        $data['eventGrowth'][$i] ?? 0,
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildUsers(array $data, array $labels): array
    {
        return [
            'title' => 'Admin Reports — Users',
            'summary' => [
                ['label' => 'Total Users', 'value' => $data['totalUsers'] ?? 0],
                ['label' => 'Active Users', 'value' => $data['activeUsers'] ?? 0],
                ['label' => 'Verified Users', 'value' => $data['verifiedUsers'] ?? 0],
                ['label' => 'New This Month', 'value' => $data['newUsersThisMonth'] ?? 0],
            ],
            'tables' => [
                [
                    'heading' => 'User Registrations Trend',
                    'headers' => ['Month', 'Registrations'],
                    'rows' => $this->exportService->trendRows($labels, $data['registrationTrend'] ?? []),
                ],
                [
                    'heading' => 'Users by Role',
                    'headers' => ['Role', 'Count'],
                    'rows' => collect($data['usersByRole'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Users',
                    'headers' => ['Name', 'Email', 'Role', 'Status', 'Joined'],
                    'rows' => collect($data['recentUsers'] ?? [])->map(fn ($r) => [
                        $r['name'], $r['email'], $r['role'], $r['status'], $r['joined'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildPayments(array $data, array $labels): array
    {
        return [
            'title' => 'Admin Reports — Payments',
            'summary' => [
                ['label' => 'Total Revenue (LKR)', 'value' => number_format((float) ($data['totalRevenue'] ?? 0), 2)],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format((float) ($data['netRevenue'] ?? 0), 2)],
                ['label' => 'Tickets Sold', 'value' => $data['ticketsSold'] ?? 0],
                ['label' => 'Total Refunded (LKR)', 'value' => number_format((float) ($data['totalRefunded'] ?? 0), 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Revenue Trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['revenueTrend'] ?? []),
                ],
                [
                    'heading' => 'Payments by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['paymentsByStatus'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Payments by Method',
                    'headers' => ['Method', 'Count'],
                    'rows' => collect($data['paymentsByMethod'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Transactions',
                    'headers' => ['Reference', 'User', 'Amount (LKR)', 'Status', 'Method', 'Date'],
                    'rows' => collect($data['recentPayments'] ?? [])->map(fn ($r) => [
                        $r['reference'], $r['user'], number_format($r['amount'], 2), ucfirst($r['status']), ucfirst($r['method']), $r['date'],
                    ])->all(),
                ],
            ],
        ];
    }
}

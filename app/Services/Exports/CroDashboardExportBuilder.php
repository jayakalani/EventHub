<?php

namespace App\Services\Exports;

use App\Services\CroDashboardService;

class CroDashboardExportBuilder
{
    public function __construct(
        protected CroDashboardService $dashboardService,
    ) {}

    /**
     * @param  array{event?: int|null, from?: string|null, to?: string|null, range?: string|null}  $filters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(array $filters = [], ?int $croId = null): array
    {
        $dashboard = $this->dashboardService->getDashboardData($filters, $croId);

        $eventFilter = $dashboard['eventFilter'] ?? [];
        $filterMeta = $dashboard['filters'] ?? [];
        $dashboardKpis = $dashboard['kpis'] ?? [];
        $personal = $dashboard['personalKpis'] ?? [];
        $today = $dashboard['todayTasks'] ?? [];
        $satisfaction = $dashboard['satisfaction'] ?? [];
        $weekTrend = $dashboard['charts']['periods']['week'] ?? [];
        $monthTrend = $dashboard['charts']['periods']['month'] ?? [];
        $complaintStatus = $dashboard['charts']['complaintStatus'] ?? [];
        $supportCategories = $dashboard['charts']['supportCategories'] ?? [];
        $ratingDist = $dashboard['charts']['satisfactionDistribution'] ?? [];
        $themes = $dashboard['feedbackThemes'] ?? [];
        $attendance = $dashboard['attendance'] ?? [];

        $scope = ! empty($eventFilter['selectedEventName'])
            ? $eventFilter['selectedEventName']
            : 'All events';

        $dateRange = trim(($filterMeta['from'] ?? '—').' → '.($filterMeta['to'] ?? '—'));

        $todayKpis = $this->todayKpiCards($today);
        $performanceKpis = $this->performanceKpiCards($personal, $dashboardKpis);
        $attendanceKpis = $this->attendanceKpiCards($attendance);

        $todayTables = [
            [
                'heading' => 'Today’s work',
                'headers' => ['Type', 'Title', 'Detail', 'Age', 'Action'],
                'rows' => collect($dashboard['todayWork'] ?? [])->map(fn ($row) => [
                    ucfirst((string) ($row['type'] ?? '—')),
                    $row['title'] ?? '—',
                    $row['meta'] ?? '—',
                    $row['age'] ?? '—',
                    $row['actionLabel'] ?? 'Open',
                ])->all(),
            ],
            [
                'heading' => 'Event handoffs',
                'headers' => ['Event', 'Status', 'Open inquiries', 'Pending refunds'],
                'rows' => collect($dashboard['handoffs'] ?? [])->map(fn ($row) => [
                    $row['event']['name'] ?? '—',
                    $row['event']['statusLabel'] ?? ($row['type'] ?? '—'),
                    $row['summary']['openInquiries'] ?? 0,
                    $row['summary']['pendingRefunds'] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Pending refunds',
                'headers' => ['Customer', 'Event', 'Amount', 'Status'],
                'rows' => collect($dashboard['pendingRefunds'] ?? [])->map(fn ($row) => [
                    $row['customer'] ?? '—',
                    $row['event'] ?? '—',
                    $row['amount'] ?? '—',
                    $row['status'] ?? '—',
                ])->all(),
            ],
            [
                'heading' => 'Event support overview (today)',
                'headers' => ['Event', 'Attendees', 'Open inquiries', 'Pending refunds'],
                'rows' => collect($dashboard['eventsToday'] ?? [])->map(fn ($row) => [
                    $row['name'] ?? '—',
                    $row['attendees'] ?? 0,
                    $row['openInquiries'] ?? 0,
                    $row['pendingRefunds'] ?? 0,
                ])->all(),
            ],
        ];

        $performanceTables = [
            [
                'heading' => 'Satisfaction rating distribution',
                'headers' => ['Rating', 'Count', 'Percent'],
                'rows' => collect($ratingDist['labels'] ?? [])->map(fn ($label, $index) => [
                    $label,
                    $ratingDist['counts'][$index] ?? 0,
                    number_format((float) ($ratingDist['percents'][$index] ?? 0), 1).'%',
                ])->all(),
            ],
            [
                'heading' => 'High priority cases',
                'headers' => ['Title', 'Detail', 'Type'],
                'rows' => collect($dashboard['highPriority'] ?? [])->map(fn ($row) => [
                    $row['title'] ?? '—',
                    $row['meta'] ?? '—',
                    $row['type'] ?? '—',
                ])->all(),
            ],
        ];

        $supportTables = [
            [
                'heading' => 'Support trend — weekly',
                'headers' => ['Period', 'Inquiries', 'Complaints', 'Refunds'],
                'rows' => collect($weekTrend['labels'] ?? [])->map(fn ($label, $index) => [
                    $label,
                    $weekTrend['inquiries'][$index] ?? 0,
                    $weekTrend['complaints'][$index] ?? 0,
                    $weekTrend['refunds'][$index] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Support trend — monthly',
                'headers' => ['Period', 'Inquiries', 'Complaints', 'Refunds'],
                'rows' => collect($monthTrend['labels'] ?? [])->map(fn ($label, $index) => [
                    $label,
                    $monthTrend['inquiries'][$index] ?? 0,
                    $monthTrend['complaints'][$index] ?? 0,
                    $monthTrend['refunds'][$index] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Complaint resolution status',
                'headers' => ['Status', 'Count', 'Percent'],
                'rows' => collect($complaintStatus['labels'] ?? [])->map(fn ($label, $index) => [
                    $label,
                    $complaintStatus['counts'][$index] ?? 0,
                    number_format((float) ($complaintStatus['percents'][$index] ?? 0), 1).'%',
                ])->all(),
            ],
            [
                'heading' => 'Top feedback themes',
                'headers' => ['Theme', 'Count', 'Percent'],
                'rows' => collect($themes)->map(fn ($row) => [
                    $row['label'] ?? '—',
                    $row['count'] ?? 0,
                    number_format((float) ($row['percent'] ?? 0), 1).'%',
                ])->all(),
            ],
            [
                'heading' => 'Support categories',
                'headers' => ['Category', 'Count'],
                'rows' => collect($supportCategories['labels'] ?? [])->map(fn ($label, $index) => [
                    $label,
                    $supportCategories['counts'][$index] ?? 0,
                ])->all(),
            ],
        ];

        $inquiryTables = [
            [
                'heading' => 'Recent inquiries',
                'headers' => ['Customer', 'Subject', 'Event', 'Status', 'When'],
                'rows' => collect($dashboard['recentInquiries'] ?? [])->map(fn ($row) => [
                    $row['customer'] ?? '—',
                    $row['subject'] ?? '—',
                    $row['event'] ?? '—',
                    $row['status'] ?? '—',
                    $row['time'] ?? '—',
                ])->all(),
            ],
        ];

        $attendanceTables = [
            [
                'heading' => 'Attendance by assigned event',
                'headers' => ['Event', 'Date', 'Tickets', 'Checked in', 'No-shows', 'Awaiting', 'Rate %', 'Final'],
                'rows' => collect($attendance['byEvent'] ?? [])->map(fn ($row) => [
                    $row['name'] ?? '—',
                    $row['date'] ?? '—',
                    $row['tickets'] ?? 0,
                    $row['checked_in'] ?? 0,
                    ($row['attendance_final'] ?? false) ? ($row['no_shows'] ?? 0) : '—',
                    ($row['attendance_final'] ?? false) ? '—' : ($row['awaiting_check_in'] ?? 0),
                    $row['attendance_rate'] ?? 0,
                    ($row['attendance_final'] ?? false) ? 'Yes' : 'No',
                ])->all(),
            ],
        ];

        $sections = [
            ['key' => 'today', 'title' => 'Today', 'summary' => $todayKpis, 'tables' => $todayTables],
            ['key' => 'attendance', 'title' => 'Attendance', 'summary' => $attendanceKpis, 'tables' => $attendanceTables],
            ['key' => 'performance', 'title' => 'Performance', 'summary' => $performanceKpis, 'tables' => $performanceTables],
            ['key' => 'support', 'title' => 'Support', 'summary' => [], 'tables' => $supportTables],
            ['key' => 'inquiry', 'title' => 'Inquiry', 'summary' => [], 'tables' => $inquiryTables],
            ['key' => 'complaints', 'title' => 'Complaints', 'summary' => [], 'tables' => []],
        ];

        $coverKpis = array_merge($todayKpis, $attendanceKpis, $performanceKpis);

        return [
            'title' => 'CRO Dashboard',
            'subtitle' => 'Today, attendance, performance, support, inquiries, and complaints',
            'filters' => [
                ['label' => 'Event scope', 'value' => $scope],
                ['label' => 'Date range', 'value' => $dateRange],
            ],
            'kpis' => $coverKpis,
            'summary' => [
                ['label' => 'Event scope', 'value' => $scope],
                ['label' => 'Date range', 'value' => $dateRange],
                ['label' => 'Today’s work queue', 'value' => $today['queueTotal'] ?? 0],
                ['label' => 'Open inquiries', 'value' => $dashboardKpis['openInquiries'] ?? 0],
                ['label' => 'Active complaints', 'value' => $dashboardKpis['activeComplaints'] ?? 0],
                ['label' => 'Resolved today', 'value' => $dashboardKpis['resolvedToday'] ?? 0],
                ['label' => 'Avg response time', 'value' => $dashboardKpis['avgResponseLabel'] ?? '—'],
                ['label' => 'My avg first response', 'value' => $personal['avgFirstResponseLabel'] ?? '—'],
                ['label' => 'My avg resolution', 'value' => $personal['avgResolutionLabel'] ?? '—'],
                ['label' => 'My refund approve rate', 'value' => isset($personal['refundApproveRate']) ? $personal['refundApproveRate'].'%' : '—'],
                ['label' => 'My refund decline rate', 'value' => isset($personal['refundDeclineRate']) ? $personal['refundDeclineRate'].'%' : '—'],
                ['label' => 'My events satisfaction', 'value' => isset($personal['satisfactionAverage'])
                    ? number_format((float) $personal['satisfactionAverage'], 1).'/5 ('.$personal['satisfactionCount'].')'
                    : '—'],
                ['label' => 'Today — New inquiries', 'value' => $today['newInquiries'] ?? 0],
                ['label' => 'Today — Pending refunds', 'value' => $today['refundRequests'] ?? 0],
                ['label' => 'Today — Urgent complaints', 'value' => $today['urgentComplaints'] ?? 0],
                ['label' => 'Today — Events', 'value' => $today['eventsToday'] ?? 0],
                [
                    'label' => 'Customer satisfaction',
                    'value' => isset($satisfaction['average'])
                        ? number_format((float) $satisfaction['average'], 1).'/5 · '
                            .number_format((float) ($satisfaction['happyPercent'] ?? 0), 1).'% positive'
                        : '—',
                ],
                ['label' => 'Event ratings', 'value' => $satisfaction['reviewCount'] ?? 0],
            ],
            'sections' => $sections,
            'tables' => collect($sections)
                ->flatMap(function (array $section) {
                    $sectionTitle = $section['title'] ?? 'Section';

                    return collect($section['tables'] ?? [])->map(fn (array $table) => [
                        'heading' => $sectionTitle.' — '.($table['heading'] ?? 'Data'),
                        'headers' => $table['headers'] ?? [],
                        'rows' => $table['rows'] ?? [],
                    ]);
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $today
     * @return list<array{label: string, value: string|int|float, sub?: string}>
     */
    private function todayKpiCards(array $today): array
    {
        return [
            $this->kpiCard('New inquiries', $today['newInquiries'] ?? 0),
            $this->kpiCard('Refunds', $today['refundRequests'] ?? 0),
            $this->kpiCard('Urgent', $today['urgentComplaints'] ?? 0),
            $this->kpiCard('Events today', $today['eventsToday'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $personal
     * @param  array<string, mixed>  $dashboardKpis
     * @return list<array{label: string, value: string|int|float, sub?: string}>
     */
    private function performanceKpiCards(array $personal, array $dashboardKpis): array
    {
        $refundValue = isset($personal['refundApproveRate'])
            ? number_format((float) $personal['refundApproveRate'], 0).'% / '.number_format((float) ($personal['refundDeclineRate'] ?? 0), 0).'%'
            : '—';

        $satisfactionValue = isset($personal['satisfactionAverage'])
            ? number_format((float) $personal['satisfactionAverage'], 1).'/5'
            : '—';

        return [
            $this->kpiCard('Avg first response', $personal['avgFirstResponseLabel'] ?? '—', 'On cases assigned to you'),
            $this->kpiCard('Avg resolution', $personal['avgResolutionLabel'] ?? '—', 'Inquiries & complaints'),
            $this->kpiCard(
                'Refund approve / decline',
                $refundValue,
                number_format((int) ($personal['refundReviewed'] ?? 0)).' reviewed in range',
            ),
            $this->kpiCard(
                'Event satisfaction',
                $satisfactionValue,
                number_format((int) ($personal['satisfactionCount'] ?? 0)).' ratings on your events',
            ),
            $this->kpiCard('Open inquiries', $dashboardKpis['openInquiries'] ?? 0, 'Waiting for response'),
            $this->kpiCard('Active complaints', $dashboardKpis['activeComplaints'] ?? 0, 'Open & in progress'),
            $this->kpiCard('Resolved today', $dashboardKpis['resolvedToday'] ?? 0, 'Cases completed today'),
            $this->kpiCard('Avg. response time', $dashboardKpis['avgResponseLabel'] ?? '—', 'First response speed'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attendance
     * @return list<array{label: string, value: string|int|float, sub?: string}>
     */
    private function attendanceKpiCards(array $attendance): array
    {
        return [
            $this->kpiCard(
                'Attendance rate',
                isset($attendance['attendanceRate'])
                    ? number_format((float) $attendance['attendanceRate'], 1).'%'
                    : '—',
                ((int) ($attendance['eventsFinalized'] ?? 0)) > 0
                    ? number_format((int) $attendance['eventsFinalized']).' completed events'
                    : 'Based on eligible tickets',
            ),
            $this->kpiCard(
                'Checked in',
                $attendance['checkedIn'] ?? 0,
                number_format((int) ($attendance['ticketsEligible'] ?? 0)).' eligible tickets',
            ),
            $this->kpiCard('No-shows', $attendance['noShows'] ?? 0, 'Completed events only'),
            $this->kpiCard('Awaiting check-in', $attendance['awaitingCheckIn'] ?? 0, 'Upcoming & ongoing'),
        ];
    }

    /**
     * @return array{label: string, value: string|int|float, sub?: string}
     */
    private function kpiCard(string $label, string|int|float $value, ?string $sub = null): array
    {
        $card = [
            'label' => $label,
            'value' => $value,
        ];

        if ($sub !== null && $sub !== '') {
            $card['sub'] = $sub;
        }

        return $card;
    }
}

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
        $kpis = $dashboard['kpis'] ?? [];
        $personal = $dashboard['personalKpis'] ?? [];
        $today = $dashboard['todayTasks'] ?? [];
        $satisfaction = $dashboard['satisfaction'] ?? [];
        $weekTrend = $dashboard['charts']['periods']['week'] ?? [];
        $monthTrend = $dashboard['charts']['periods']['month'] ?? [];
        $complaintStatus = $dashboard['charts']['complaintStatus'] ?? [];
        $supportCategories = $dashboard['charts']['supportCategories'] ?? [];
        $ratingDist = $dashboard['charts']['satisfactionDistribution'] ?? [];
        $themes = $dashboard['feedbackThemes'] ?? [];

        $scope = ! empty($eventFilter['selectedEventName'])
            ? $eventFilter['selectedEventName']
            : 'All events';

        $dateRange = trim(($filterMeta['from'] ?? '—').' → '.($filterMeta['to'] ?? '—'));

        return [
            'title' => 'CRO Dashboard',
            'summary' => [
                ['label' => 'Event scope', 'value' => $scope],
                ['label' => 'Date range', 'value' => $dateRange],
                ['label' => 'Today’s work queue', 'value' => $today['queueTotal'] ?? 0],
                ['label' => 'Open inquiries', 'value' => $kpis['openInquiries'] ?? 0],
                ['label' => 'Active complaints', 'value' => $kpis['activeComplaints'] ?? 0],
                ['label' => 'Resolved today', 'value' => $kpis['resolvedToday'] ?? 0],
                ['label' => 'Avg response time', 'value' => $kpis['avgResponseLabel'] ?? '—'],
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
            'tables' => [
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
                    'heading' => 'Satisfaction rating distribution',
                    'headers' => ['Rating', 'Count', 'Percent'],
                    'rows' => collect($ratingDist['labels'] ?? [])->map(fn ($label, $index) => [
                        $label,
                        $ratingDist['counts'][$index] ?? 0,
                        number_format((float) ($ratingDist['percents'][$index] ?? 0), 1).'%',
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
                [
                    'heading' => 'High priority cases',
                    'headers' => ['Title', 'Detail', 'Type'],
                    'rows' => collect($dashboard['highPriority'] ?? [])->map(fn ($row) => [
                        $row['title'] ?? '—',
                        $row['meta'] ?? '—',
                        $row['type'] ?? '—',
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
            ],
        ];
    }
}

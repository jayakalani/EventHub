<?php

namespace App\Services\Exports;

use App\Services\CroReportService;
use App\Services\ReportExportService;
use Illuminate\Support\Facades\Auth;

class CroReportExportBuilder
{
    public function __construct(
        protected CroReportService $reportService,
        protected ReportExportService $exportService,
    ) {}

    /**
     * @param  array{event?: int|null, cro?: int|null, range?: string|null, from?: string|null, to?: string|null}  $filters
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(string $section, array $filters = []): array
    {
        $reports = $this->reportService->getAllReports($filters, Auth::id() ? (int) Auth::id() : null);
        $labels = $reports['chartLabels'];
        $filterMeta = $reports['filters'] ?? [];
        $summary = $reports['summary'] ?? [];
        $personal = $reports['personalKpis'] ?? [];

        return match ($section) {
            'inquiries' => $this->buildInquiries($reports['inquiries'], $labels, $filterMeta, $summary, $reports['satisfaction'] ?? [], $personal),
            'complaints' => $this->buildComplaints($reports['complaints'], $labels, $filterMeta, $summary, $reports['satisfaction'] ?? [], $personal),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $labels
     * @param  array<string, mixed>  $filterMeta
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $satisfaction
     * @param  array<string, mixed>  $personal
     */
    private function buildInquiries(array $data, array $labels, array $filterMeta, array $summary, array $satisfaction, array $personal = []): array
    {
        return [
            'title' => 'CRO Reports — Inquiry Resolution',
            'summary' => array_merge([
                ['label' => 'Event scope', 'value' => $this->eventScopeLabel($filterMeta)],
                ['label' => 'CRO scope', 'value' => $this->croScopeLabel($filterMeta)],
                ['label' => 'Date range', 'value' => ($filterMeta['from'] ?? '—').' → '.($filterMeta['to'] ?? '—')],
                ['label' => 'Resolved', 'value' => $summary['resolved'] ?? 0],
                ['label' => 'Pending', 'value' => $summary['pending'] ?? 0],
                ['label' => 'Avg response', 'value' => $summary['avgResponseLabel'] ?? ($data['avgResponseLabel'] ?? '—')],
                ['label' => 'Total inquiries', 'value' => $data['total']],
                ['label' => 'Resolution rate', 'value' => $data['resolutionRate'].'%'],
                ['label' => 'Active', 'value' => $data['active']],
                ['label' => 'Resolved / closed', 'value' => $data['resolvedOrClosed']],
                [
                    'label' => 'CSAT average',
                    'value' => isset($satisfaction['average'])
                        ? number_format((float) $satisfaction['average'], 1).'/5'
                        : '—',
                ],
            ], $this->personalKpiSummaryRows($personal)),
            'tables' => [
                [
                    'heading' => 'Status breakdown',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['statusBreakdown'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Inquiry vs resolution trend',
                    'headers' => ['Period', 'Submitted', 'Resolved', 'Rate %'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        $data['resolutionTrend']['submitted'][$i] ?? 0,
                        $data['resolutionTrend']['resolved'][$i] ?? 0,
                        $data['resolutionTrend']['resolutionRate'][$i] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Average response time trend',
                    'headers' => ['Period', 'Avg minutes'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        $data['responseTimeTrend'][$i] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'CSAT distribution',
                    'headers' => ['Rating', 'Count', 'Percent'],
                    'rows' => collect($satisfaction['distribution']['labels'] ?? [])->map(fn ($label, $i) => [
                        $label,
                        $satisfaction['distribution']['counts'][$i] ?? 0,
                        number_format((float) ($satisfaction['distribution']['percents'][$i] ?? 0), 1).'%',
                    ])->all(),
                ],
                [
                    'heading' => 'Inquiries by event',
                    'headers' => ['Event', 'Inquiries'],
                    'rows' => collect($data['byEvent'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent inquiries',
                    'headers' => ['Subject', 'User', 'Event', 'Status', 'Assignee', 'Submitted'],
                    'rows' => collect($data['recentInquiries'])->map(fn ($r) => [
                        $r['subject'], $r['user'], $r['event'], $r['status'], $r['assignee'], $r['submitted'],
                    ])->all(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $labels
     * @param  array<string, mixed>  $filterMeta
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $satisfaction
     * @param  array<string, mixed>  $personal
     */
    private function buildComplaints(array $data, array $labels, array $filterMeta, array $summary, array $satisfaction, array $personal = []): array
    {
        return [
            'title' => 'CRO Reports — Complaint Statistics',
            'summary' => array_merge([
                ['label' => 'Event scope', 'value' => $this->eventScopeLabel($filterMeta)],
                ['label' => 'CRO scope', 'value' => $this->croScopeLabel($filterMeta)],
                ['label' => 'Date range', 'value' => ($filterMeta['from'] ?? '—').' → '.($filterMeta['to'] ?? '—')],
                ['label' => 'Resolved', 'value' => $summary['resolved'] ?? 0],
                ['label' => 'Pending', 'value' => $summary['pending'] ?? 0],
                ['label' => 'Avg response', 'value' => $summary['avgResponseLabel'] ?? '—'],
                ['label' => 'Total complaints', 'value' => $data['total']],
                ['label' => 'Open', 'value' => $data['open']],
                ['label' => 'In progress', 'value' => $data['inProgress']],
                ['label' => 'Resolved / closed', 'value' => $data['resolved'] + $data['closed']],
                [
                    'label' => 'CSAT average',
                    'value' => isset($satisfaction['average'])
                        ? number_format((float) $satisfaction['average'], 1).'/5'
                        : '—',
                ],
            ], $this->personalKpiSummaryRows($personal)),
            'tables' => [
                [
                    'heading' => 'Complaints by status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['statusBreakdown'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Complaint categories',
                    'headers' => ['Category', 'Count'],
                    'rows' => collect($data['categoryBreakdown'] ?? $data['typeBreakdown'])->map(fn ($r) => [
                        $r['label'], $r['count'],
                    ])->all(),
                ],
                [
                    'heading' => 'Submission trend',
                    'headers' => ['Period', 'Complaints'],
                    'rows' => $this->exportService->trendRows($labels, $data['submissionsTrend']),
                ],
                [
                    'heading' => 'Status by type',
                    'headers' => ['Type', 'Open', 'In Progress', 'Resolved', 'Closed'],
                    'rows' => collect($data['statusByType'])->map(fn ($r) => [
                        $r['label'], $r['open'], $r['in_progress'], $r['resolved'], $r['closed'],
                    ])->all(),
                ],
                [
                    'heading' => 'CSAT distribution',
                    'headers' => ['Rating', 'Count', 'Percent'],
                    'rows' => collect($satisfaction['distribution']['labels'] ?? [])->map(fn ($label, $i) => [
                        $label,
                        $satisfaction['distribution']['counts'][$i] ?? 0,
                        number_format((float) ($satisfaction['distribution']['percents'][$i] ?? 0), 1).'%',
                    ])->all(),
                ],
                [
                    'heading' => 'Recent complaints',
                    'headers' => ['Subject', 'User', 'Type', 'Status', 'Assignee', 'Submitted'],
                    'rows' => collect($data['recentComplaints'])->map(fn ($r) => [
                        $r['subject'], $r['user'], $r['type'], $r['status'], $r['assignee'], $r['submitted'],
                    ])->all(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $personal
     * @return list<array{label: string, value: string|int|float}>
     */
    private function personalKpiSummaryRows(array $personal): array
    {
        if ($personal === []) {
            return [];
        }

        return [
            ['label' => 'My avg first response', 'value' => $personal['avgFirstResponseLabel'] ?? '—'],
            ['label' => 'My avg resolution', 'value' => $personal['avgResolutionLabel'] ?? '—'],
            ['label' => 'My refund approve rate', 'value' => isset($personal['refundApproveRate']) ? $personal['refundApproveRate'].'%' : '—'],
            ['label' => 'My refund decline rate', 'value' => isset($personal['refundDeclineRate']) ? $personal['refundDeclineRate'].'%' : '—'],
            ['label' => 'My events satisfaction', 'value' => isset($personal['satisfactionAverage'])
                ? number_format((float) $personal['satisfactionAverage'], 1).'/5 ('.$personal['satisfactionCount'].')'
                : '—'],
        ];
    }

    private function eventScopeLabel(array $filterMeta): string
    {
        return $filterMeta['selectedEventName'] ?? (empty($filterMeta['event']) ? 'All events' : 'Event #'.$filterMeta['event']);
    }

    private function croScopeLabel(array $filterMeta): string
    {
        return $filterMeta['selectedCroName'] ?? (empty($filterMeta['cro']) ? 'All CROs' : 'CRO #'.$filterMeta['cro']);
    }
}

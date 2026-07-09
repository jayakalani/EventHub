<?php

namespace App\Services\Exports;

use App\Services\CroReportService;
use App\Services\ReportExportService;

class CroReportExportBuilder
{
    public function __construct(
        protected CroReportService $reportService,
        protected ReportExportService $exportService,
    ) {}

    /**
     * @return array{title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}
     */
    public function build(string $section): array
    {
        $reports = $this->reportService->getAllReports();
        $labels = $reports['chartLabels'];

        return match ($section) {
            'inquiries' => $this->buildInquiries($reports['inquiries'], $labels),
            'complaints' => $this->buildComplaints($reports['complaints'], $labels),
            default => abort(404),
        };
    }

    private function buildInquiries(array $data, array $labels): array
    {
        return [
            'title' => 'CRO Reports — Inquiry Resolution',
            'summary' => [
                ['label' => 'Total Inquiries', 'value' => $data['total']],
                ['label' => 'Resolution Rate', 'value' => $data['resolutionRate'].'%'],
                ['label' => 'Active', 'value' => $data['active']],
                ['label' => 'Resolved / Closed', 'value' => $data['resolvedOrClosed']],
            ],
            'tables' => [
                [
                    'heading' => 'Status Breakdown',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['statusBreakdown'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Resolution Rate Trend',
                    'headers' => ['Month', 'Submitted', 'Resolved', 'Rate %'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        $data['resolutionTrend']['submitted'][$i] ?? 0,
                        $data['resolutionTrend']['resolved'][$i] ?? 0,
                        $data['resolutionTrend']['resolutionRate'][$i] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Inquiries by Event',
                    'headers' => ['Event', 'Inquiries'],
                    'rows' => collect($data['byEvent'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Inquiries',
                    'headers' => ['Subject', 'User', 'Event', 'Status', 'Assignee', 'Submitted'],
                    'rows' => collect($data['recentInquiries'])->map(fn ($r) => [
                        $r['subject'], $r['user'], $r['event'], $r['status'], $r['assignee'], $r['submitted'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildComplaints(array $data, array $labels): array
    {
        return [
            'title' => 'CRO Reports — Complaint Statistics',
            'summary' => [
                ['label' => 'Total Complaints', 'value' => $data['total']],
                ['label' => 'Open', 'value' => $data['open']],
                ['label' => 'In Progress', 'value' => $data['inProgress']],
                ['label' => 'Resolved / Closed', 'value' => $data['resolved'] + $data['closed']],
            ],
            'tables' => [
                [
                    'heading' => 'Complaints by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['statusBreakdown'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Complaints by Type',
                    'headers' => ['Type', 'Count'],
                    'rows' => collect($data['typeBreakdown'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Submission Trend',
                    'headers' => ['Month', 'Complaints'],
                    'rows' => $this->exportService->trendRows($labels, $data['submissionsTrend']),
                ],
                [
                    'heading' => 'Status by Type',
                    'headers' => ['Type', 'Open', 'In Progress', 'Resolved', 'Closed'],
                    'rows' => collect($data['statusByType'])->map(fn ($r) => [
                        $r['label'], $r['open'], $r['in_progress'], $r['resolved'], $r['closed'],
                    ])->all(),
                ],
                [
                    'heading' => 'Recent Complaints',
                    'headers' => ['Subject', 'User', 'Type', 'Status', 'Assignee', 'Submitted'],
                    'rows' => collect($data['recentComplaints'])->map(fn ($r) => [
                        $r['subject'], $r['user'], $r['type'], $r['status'], $r['assignee'], $r['submitted'],
                    ])->all(),
                ],
            ],
        ];
    }
}

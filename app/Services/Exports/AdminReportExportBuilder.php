<?php

namespace App\Services\Exports;

use App\Services\AdminReportService;
use App\Services\ReportExportService;

class AdminReportExportBuilder
{
    public function __construct(
        protected AdminReportService $reportService,
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
            'admin' => $this->buildAdmin($reports['admin'], $labels),
            'users' => $this->buildUsers($reports['users'], $labels),
            'payments' => $this->buildPayments($reports['payments'], $labels),
            'system' => $this->buildSystem($reports['system'], $labels),
            default => abort(404),
        };
    }

    private function buildAdmin(array $data, array $labels): array
    {
        return [
            'title' => 'Admin Reports — Platform Overview',
            'summary' => [
                ['label' => 'Total Users', 'value' => $data['totalUsers']],
                ['label' => 'Total Events', 'value' => $data['totalEvents']],
                ['label' => 'Tickets Sold', 'value' => $data['totalTicketsSold']],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($data['netRevenue'], 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Events by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['eventsByStatus'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Top Event Categories',
                    'headers' => ['Category', 'Events'],
                    'rows' => collect($data['topCategories'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
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
            'title' => 'User Reports',
            'summary' => [
                ['label' => 'Total Users', 'value' => $data['totalUsers']],
                ['label' => 'Active Users', 'value' => $data['activeUsers']],
                ['label' => 'Verified Users', 'value' => $data['verifiedUsers']],
                ['label' => 'New This Month', 'value' => $data['newUsersThisMonth']],
            ],
            'tables' => [
                [
                    'heading' => 'User Registrations Trend',
                    'headers' => ['Month', 'Registrations'],
                    'rows' => $this->exportService->trendRows($labels, $data['registrationTrend']),
                ],
                [
                    'heading' => 'Users by Role',
                    'headers' => ['Role', 'Count'],
                    'rows' => collect($data['usersByRole'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Users',
                    'headers' => ['Name', 'Email', 'Role', 'Status', 'Joined'],
                    'rows' => collect($data['recentUsers'])->map(fn ($r) => [
                        $r['name'], $r['email'], $r['role'], $r['status'], $r['joined'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildPayments(array $data, array $labels): array
    {
        return [
            'title' => 'Payment Reports',
            'summary' => [
                ['label' => 'Total Revenue (LKR)', 'value' => number_format($data['totalRevenue'], 2)],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($data['netRevenue'], 2)],
                ['label' => 'Tickets Sold', 'value' => $data['ticketsSold']],
                ['label' => 'Total Refunded (LKR)', 'value' => number_format($data['totalRefunded'], 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Revenue Trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['revenueTrend']),
                ],
                [
                    'heading' => 'Payments by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($data['paymentsByStatus'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Payments by Method',
                    'headers' => ['Method', 'Count'],
                    'rows' => collect($data['paymentsByMethod'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Transactions',
                    'headers' => ['Reference', 'User', 'Amount (LKR)', 'Status', 'Method', 'Date'],
                    'rows' => collect($data['recentPayments'])->map(fn ($r) => [
                        $r['reference'], $r['user'], number_format($r['amount'], 2), ucfirst($r['status']), ucfirst($r['method']), $r['date'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildSystem(array $data, array $labels): array
    {
        return [
            'title' => 'System Reports',
            'summary' => [
                ['label' => 'Audit Log Entries', 'value' => $data['totalAuditLogs']],
                ['label' => 'Activity Today', 'value' => $data['auditLogsToday']],
                ['label' => 'Total Inquiries', 'value' => $data['totalInquiries']],
                ['label' => 'Total Complaints', 'value' => $data['totalComplaints']],
            ],
            'tables' => [
                [
                    'heading' => 'System Activity Trend',
                    'headers' => ['Month', 'Audit Entries'],
                    'rows' => $this->exportService->trendRows($labels, $data['activityTrend']),
                ],
                [
                    'heading' => 'Top Audit Actions',
                    'headers' => ['Action', 'Count'],
                    'rows' => collect($data['auditByAction'])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Recent Activity Logs',
                    'headers' => ['User', 'Action', 'Model', 'IP', 'When'],
                    'rows' => collect($data['recentAuditLogs'])->map(fn ($r) => [
                        $r['user'], $r['action'], $r['model'], $r['ip'], $r['time'],
                    ])->all(),
                ],
            ],
        ];
    }
}

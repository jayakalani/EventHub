<?php

namespace App\Services\CroReports\Generators;

use App\Models\User;
use App\Services\CroReports\Contracts\ReportGenerator;
use App\Services\Exports\CroReportExportBuilder;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class DashboardAnalyticsReport implements ReportGenerator
{
    public function __construct(
        protected CroReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function generate(User $user, array $fields, array $filters, string $format): Response
    {
        $period = (string) ($filters['period'] ?? 'month');
        if (! in_array($period, ['week', 'month', 'custom'], true)) {
            $period = 'month';
        }

        $serviceFilters = [
            'event' => ! empty($filters['event_id']) ? (int) $filters['event_id'] : null,
            'cro' => null,
            'range' => $period === 'custom' ? 'custom' : $period,
            'from' => $period === 'custom' ? ($filters['date_from'] ?? null) : null,
            'to' => $period === 'custom' ? ($filters['date_to'] ?? null) : null,
        ];

        $inquiriesPayload = $this->exportBuilder->build('inquiries', $serviceFilters);
        $complaintsPayload = $this->exportBuilder->build('complaints', $serviceFilters);

        $payload = [
            'title' => 'CRO Dashboard Analytics',
            'summary' => array_values(array_filter(
                array_merge($inquiriesPayload['summary'] ?? [], $complaintsPayload['summary'] ?? []),
                fn (array $row) => ($row['label'] ?? '') !== '—'
            )),
            'tables' => array_merge(
                $inquiriesPayload['tables'] ?? [],
                $complaintsPayload['tables'] ?? [],
            ),
        ];

        return $this->exportService->downloadPdf(
            $payload,
            'cro-analytics_'.now()->format('Ymd_His').'.pdf',
        );
    }
}

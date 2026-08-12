<?php

namespace App\Services\OrganizerReports\Generators;

use App\Models\User;
use App\Services\Exports\OrganizerReportExportBuilder;
use App\Services\OrganizerReports\Contracts\ReportGenerator;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class InsightsAnalyticsReport implements ReportGenerator
{
    public function __construct(
        protected OrganizerReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function generate(User $user, array $fields, array $filters, string $format): Response
    {
        $period = (string) ($filters['period'] ?? 'month');
        if (! in_array($period, ['week', 'month', 'custom'], true)) {
            $period = 'month';
        }

        if ($period === 'custom') {
            $from = $filters['date_from'] ?? null;
            $to = $filters['date_to'] ?? null;
        } elseif ($period === 'week') {
            $from = now()->subDays(6)->toDateString();
            $to = now()->toDateString();
        } else {
            $from = now()->subDays(29)->toDateString();
            $to = now()->toDateString();
        }

        $serviceFilters = [
            'from' => $from,
            'to' => $to,
            'event_id' => ! empty($filters['event_id']) ? (int) $filters['event_id'] : null,
            'status' => $filters['status'] ?? null,
        ];

        $payload = $this->exportBuilder->build((int) $user->id, 'full', $serviceFilters);

        return $this->exportService->downloadPdf(
            $payload,
            'organizer-insights_'.now()->format('Ymd_His').'.pdf',
            'organizer.exports.report-pdf',
        );
    }
}

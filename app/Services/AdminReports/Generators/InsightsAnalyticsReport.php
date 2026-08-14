<?php

namespace App\Services\AdminReports\Generators;

use App\Models\User;
use App\Services\AdminReports\Contracts\ReportGenerator;
use App\Services\Exports\AdminReportExportBuilder;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class InsightsAnalyticsReport implements ReportGenerator
{
    private const SECTIONS = ['admin', 'users', 'payments'];

    public function __construct(
        protected AdminReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function generate(User $user, array $fields, array $filters, string $format): Response
    {
        $section = (string) ($filters['section'] ?? 'admin');
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'admin';
        }

        $organizerId = ! empty($filters['organizer_id']) ? (int) $filters['organizer_id'] : null;
        $eventId = ! empty($filters['event_id']) ? (int) $filters['event_id'] : null;

        $payload = $this->exportBuilder->build($section, $organizerId, $eventId);

        return $this->exportService->downloadPdf(
            $payload,
            'admin-insights_'.now()->format('Ymd_His').'.pdf',
        );
    }
}

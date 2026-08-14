<?php

namespace App\Services\OrganizerReports\Generators;

use App\Models\User;
use App\Services\Exports\OrganizerReportExportBuilder;
use App\Services\OrganizerReports\Contracts\ReportGenerator;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class InsightsAnalyticsReport implements ReportGenerator
{
    private const SECTIONS = [
        'full',
        'overview',
        'revenue',
        'tickets',
        'events',
        'attendance',
        'engagement',
        'audience',
        'activity',
    ];

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

        $section = (string) ($filters['section'] ?? 'full');
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'full';
        }

        $charts = $filters['_charts'] ?? [];
        unset($filters['_charts']);

        $serviceFilters = [
            'from' => $from,
            'to' => $to,
            'event_id' => ! empty($filters['event_id']) ? (int) $filters['event_id'] : null,
            'status' => $filters['status'] ?? null,
        ];

        $payload = $this->exportBuilder->build((int) $user->id, $section, $serviceFilters);
        $payload['charts'] = $this->chartsForSection(is_array($charts) ? $charts : [], $section);

        return $this->exportService->downloadPdf(
            $payload,
            'organizer-insights_'.now()->format('Ymd_His').'.pdf',
            'organizer.exports.dashboard-pdf',
        );
    }

    /**
     * @param  list<array{title?: string, image?: string, section?: string}>  $charts
     * @return list<array{title: string, image: string, section: string}>
     */
    private function chartsForSection(array $charts, string $section): array
    {
        return collect($charts)
            ->filter(fn (array $chart) => ! empty($chart['image']))
            ->when(
                $section !== 'full',
                fn ($rows) => $rows->filter(fn (array $chart) => ($chart['section'] ?? '') === $section)
            )
            ->map(fn (array $chart) => [
                'title' => (string) ($chart['title'] ?? 'Chart'),
                'image' => (string) $chart['image'],
                'section' => (string) ($chart['section'] ?? ''),
            ])
            ->values()
            ->all();
    }
}

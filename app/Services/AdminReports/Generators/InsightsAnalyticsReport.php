<?php

namespace App\Services\AdminReports\Generators;

use App\Models\Event;
use App\Models\User;
use App\Services\AdminReports\Contracts\ReportGenerator;
use App\Services\Exports\AdminReportExportBuilder;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class InsightsAnalyticsReport implements ReportGenerator
{
    private const SECTIONS = [
        'all',
        'full',
        'performance',
        'support',
        'overview',
        'activity',
        'events',
        'users',
        'payments',
        'admin',
    ];

    public function __construct(
        protected AdminReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function generate(User $user, array $fields, array $filters, string $format): Response
    {
        $section = (string) ($filters['section'] ?? 'all');
        if ($section === '' || $section === 'full') {
            $section = 'all';
        }
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'all';
        }

        $charts = $filters['_charts'] ?? [];
        unset($filters['_charts']);

        $organizerId = ! empty($filters['organizer_id']) ? (int) $filters['organizer_id'] : null;
        $eventId = ! empty($filters['event_id']) ? (int) $filters['event_id'] : null;
        $croId = ! empty($filters['cro_id']) ? (int) $filters['cro_id'] : null;

        if ($section === 'support') {
            $organizerId = null;

            if ($croId && $eventId) {
                $belongs = Event::query()
                    ->assignedToCro($croId)
                    ->whereKey($eventId)
                    ->exists();

                if (! $belongs) {
                    $eventId = null;
                }
            }
        } elseif ($organizerId && $eventId) {
            $belongs = Event::query()
                ->forFilter()
                ->createdByOrganizer($organizerId)
                ->whereKey($eventId)
                ->exists();

            if (! $belongs) {
                $eventId = null;
            }
        }

        $payload = $this->exportBuilder->build(
            $section,
            $organizerId,
            $eventId,
            [
                'from' => $filters['date_from'] ?? null,
                'to' => $filters['date_to'] ?? null,
            ],
            $croId,
        );
        $payload = $this->withDashboardPdfLayout($payload, $section);
        $payload['charts'] = $this->chartsForSection(is_array($charts) ? $charts : [], $section);

        return $this->exportService->downloadPdf(
            $payload,
            'admin-insights_'.now()->format('Ymd_His').'.pdf',
            'organizer.exports.dashboard-pdf',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withDashboardPdfLayout(array $payload, string $section): array
    {
        $sectionTitles = [
            'performance' => 'Performance',
            'support' => 'Support',
            'overview' => 'Overview',
            'activity' => 'Activity',
            'events' => 'Events',
            'users' => 'Users',
            'payments' => 'Payments',
        ];
        $filterLabels = ['Date range', 'KPI / analytics scope', 'Payment scope', 'Support scope'];

        if (empty($payload['sections'])) {
            $sectionKey = $section === 'admin' ? 'events' : $section;
            $sectionTitle = $sectionTitles[$sectionKey] ?? 'Section';
            $payload['sections'] = [[
                'key' => $sectionKey,
                'title' => $sectionTitle,
                'summary' => $payload['summary'] ?? [],
                'tables' => $payload['tables'] ?? [],
            ]];
            $payload['title'] = 'Administrator Dashboard — '.$sectionTitle;
            $payload['subtitle'] = $sectionTitle.' tab';
        } else {
            $payload['title'] = $payload['title'] ?? 'Administrator Dashboard';
            $payload['subtitle'] = $payload['subtitle']
                ?? 'Performance, Support, Overview, Activity, Events, Users, and Payments';
        }

        $summarySource = $payload['summary'] ?? [];
        if (in_array($section, ['all', 'full'], true)) {
            $performance = collect($payload['sections'] ?? [])->firstWhere('key', 'performance');
            if (is_array($performance)) {
                $summarySource = $performance['summary'] ?? $summarySource;
            }
        }

        $filters = collect($summarySource)
            ->filter(fn (array $row) => in_array($row['label'] ?? '', $filterLabels, true))
            ->values()
            ->all();
        $kpis = collect($summarySource)
            ->reject(fn (array $row) => in_array($row['label'] ?? '', $filterLabels, true))
            ->values()
            ->all();

        if (! in_array($section, ['all', 'full'], true)) {
            $filters[] = [
                'label' => 'Dashboard section',
                'value' => $payload['sections'][0]['title'] ?? ($sectionTitles[$section] ?? ucfirst($section)),
            ];
        }

        $payload['filters'] = $filters;
        $payload['kpis'] = array_slice($kpis, 0, 8);

        return $payload;
    }

    /**
     * @param  list<array{title?: string, image?: string, section?: string}>  $charts
     * @return list<array{title: string, image: string, section: string}>
     */
    private function chartsForSection(array $charts, string $section): array
    {
        $sectionKey = $section === 'admin' ? 'events' : $section;

        return collect($charts)
            ->filter(fn (array $chart) => ! empty($chart['image']))
            ->when(
                ! in_array($sectionKey, ['all', 'full'], true),
                fn ($rows) => $rows->filter(fn (array $chart) => ($chart['section'] ?? '') === $sectionKey)
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

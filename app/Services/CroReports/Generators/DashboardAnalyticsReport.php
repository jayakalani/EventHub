<?php

namespace App\Services\CroReports\Generators;

use App\Models\User;
use App\Services\CroReports\Contracts\ReportGenerator;
use App\Services\Exports\CroDashboardExportBuilder;
use App\Services\Exports\CroReportExportBuilder;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

class DashboardAnalyticsReport implements ReportGenerator
{
    private const SECTIONS = [
        'full',
        'today',
        'attendance',
        'performance',
        'support',
        'inquiry',
        'complaints',
    ];

    public function __construct(
        protected CroDashboardExportBuilder $dashboardExportBuilder,
        protected CroReportExportBuilder $reportExportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function generate(User $user, array $fields, array $filters, string $format): Response
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $section = (string) ($filters['section'] ?? 'full');
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'full';
        }

        $charts = $filters['_charts'] ?? [];
        unset($filters['_charts']);

        $serviceFilters = [
            'event' => ! empty($filters['event_id']) ? (int) $filters['event_id'] : null,
            'range' => ($from || $to) ? 'custom' : 'month',
            'from' => $from,
            'to' => $to,
        ];

        $payload = $this->dashboardExportBuilder->build($serviceFilters, (int) $user->id);
        $payload = $this->enrichInquiryAndComplaintSections($payload, $serviceFilters);
        $payload = $this->limitToSection($payload, $section);
        $payload['charts'] = $this->chartsForSection(is_array($charts) ? $charts : [], $section);

        return $this->exportService->downloadPdf(
            $payload,
            'cro-analytics_'.now()->format('Ymd_His').'.pdf',
            'organizer.exports.dashboard-pdf',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{event?: int|null, from?: string|null, to?: string|null, range?: string|null}  $filters
     * @return array<string, mixed>
     */
    private function enrichInquiryAndComplaintSections(array $payload, array $filters): array
    {
        $inquiryReport = $this->reportExportBuilder->build('inquiries', $filters);
        $complaintReport = $this->reportExportBuilder->build('complaints', $filters);
        $inquiryKpis = $this->kpiCardsFromCountTable($inquiryReport['tables'] ?? [], 'Status breakdown');
        $complaintKpis = $this->kpiCardsFromCountTable($complaintReport['tables'] ?? [], 'Complaints by status');

        $payload['sections'] = collect($payload['sections'] ?? [])
            ->map(function (array $section) use ($inquiryReport, $complaintReport, $inquiryKpis, $complaintKpis) {
                if (($section['key'] ?? '') === 'inquiry') {
                    $section['tables'] = $inquiryReport['tables'] ?? ($section['tables'] ?? []);
                    $section['summary'] = $inquiryKpis;
                }
                if (($section['key'] ?? '') === 'complaints') {
                    $section['tables'] = $complaintReport['tables'] ?? ($section['tables'] ?? []);
                    $section['summary'] = $complaintKpis;
                }

                return $section;
            })
            ->values()
            ->all();

        $payload['tables'] = $this->flattenSectionTables($payload['sections']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function limitToSection(array $payload, string $section): array
    {
        if ($section === 'full') {
            $payload['title'] = 'CRO Dashboard Analytics';

            return $payload;
        }

        $sections = collect($payload['sections'] ?? [])
            ->filter(fn (array $row) => ($row['key'] ?? '') === $section)
            ->values()
            ->all();

        $selected = $sections[0] ?? null;
        $title = $selected['title'] ?? ucfirst($section);
        $kpis = $selected['summary'] ?? [];

        $payload['title'] = 'CRO Dashboard Analytics — '.$title;
        $payload['subtitle'] = $title.' tab';
        $payload['sections'] = $sections;
        $payload['kpis'] = $kpis;
        $payload['tables'] = $this->flattenSectionTables($sections);
        $payload['summary'] = array_merge($payload['filters'] ?? [], $kpis);
        $payload['filters'] = array_merge($payload['filters'] ?? [], [
            ['label' => 'Dashboard section', 'value' => $title],
        ]);

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>
     */
    private function flattenSectionTables(array $sections): array
    {
        return collect($sections)
            ->flatMap(function (array $sectionPayload) {
                $sectionTitle = $sectionPayload['title'] ?? 'Section';

                return collect($sectionPayload['tables'] ?? [])->map(fn (array $table) => [
                    'heading' => $sectionTitle.' — '.($table['heading'] ?? 'Data'),
                    'headers' => $table['headers'] ?? [],
                    'rows' => $table['rows'] ?? [],
                ]);
            })
            ->values()
            ->all();
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

    /**
     * @param  list<array{heading?: string, rows?: list<list<mixed>>}>  $tables
     * @return list<array{label: string, value: string|int|float}>
     */
    private function kpiCardsFromCountTable(array $tables, string $heading): array
    {
        $table = collect($tables)->first(
            fn (array $item) => ($item['heading'] ?? '') === $heading
        );

        return collect($table['rows'] ?? [])
            ->map(fn (array $row) => [
                'label' => (string) ($row[0] ?? 'Metric'),
                'value' => $row[1] ?? '—',
            ])
            ->filter(fn (array $item) => $item['label'] !== '')
            ->values()
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateAdminReportRequest;
use App\Models\Event;
use App\Services\AdminReportService;
use App\Services\AdminReports\AdminReportRegistry;
use App\Services\Exports\AdminReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsReportSections;

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
        protected AdminReportService $reportService,
        protected AdminReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
        protected AdminReportRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $catalog = $this->registry->catalogFor($request->user());
        abort_if($catalog === [], 403);

        $defaultKey = array_key_first($catalog);

        return view('admin.reports.builder', [
            'catalog' => $catalog,
            'defaultReport' => $defaultKey,
            'oldReport' => old('report', $defaultKey),
            'oldFields' => old('fields', []),
            'oldFilters' => old('filters', []),
            'oldFormat' => old('format', 'pdf'),
        ]);
    }

    public function generate(GenerateAdminReportRequest $request): Response
    {
        $reportKey = (string) $request->input('report');
        $format = (string) $request->input('format');
        $generator = $this->registry->generator($reportKey);
        $filters = $request->selectedFilters();

        if ($reportKey === 'insights_analytics') {
            $filters['_charts'] = $request->selectedCharts();
        }

        return $generator->generate(
            $request->user(),
            $request->selectedFields(),
            $filters,
            $format,
        );
    }

    /**
     * Dashboard + Insights chart payloads for the reports builder PDF capture.
     */
    public function chartData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'cro_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'section' => ['nullable', 'string', 'in:all,full,performance,support,overview,activity,events,users,payments,admin'],
        ]);

        $section = (string) ($validated['section'] ?? 'all');
        if ($section === '' || $section === 'full') {
            $section = 'all';
        }

        $organizerId = isset($validated['organizer_id']) ? (int) $validated['organizer_id'] : null;
        $eventId = isset($validated['event_id']) ? (int) $validated['event_id'] : null;
        $croId = isset($validated['cro_id']) ? (int) $validated['cro_id'] : null;
        $from = $validated['date_from'] ?? null;
        $to = $validated['date_to'] ?? null;

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
        } else {
            $croId = $section === 'all' ? $croId : null;

            if ($organizerId && $eventId) {
                $belongs = Event::query()
                    ->forFilter()
                    ->createdByOrganizer($organizerId)
                    ->whereKey($eventId)
                    ->exists();

                if (! $belongs) {
                    $eventId = null;
                }
            }
        }

        $support = app(SupportReportController::class)->buildReportData(
            $croId,
            $organizerId,
            $eventId,
            $from,
            $to,
        );

        return response()->json([
            'dashboard' => $this->reportService->getDashboardData(
                $organizerId,
                $eventId,
                $organizerId,
                $eventId,
                $croId,
                $eventId,
                $from,
                $to,
            ),
            'reports' => $this->reportService->getAllReports($organizerId, $eventId, $from, $to),
            'supportCharts' => [
                'volume' => $support['volumeTrend'] ?? ['labels' => [], 'inquiries' => [], 'complaints' => []],
                'sla' => $support['slaAging'] ?? [],
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedScopeFilters($request);
        $payload = $this->exportBuilder->build($section, $filters['organizer'], $filters['event']);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('admin-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedScopeFilters($request);
        $payload = $this->exportBuilder->build($section, $filters['organizer'], $filters['event']);
        $payload['charts'] = $this->validatedChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('admin-report', $section, 'pdf'),
        );
    }

    /**
     * @return array{organizer: int|null, event: int|null}
     */
    private function validatedScopeFilters(Request $request): array
    {
        $validated = $request->validate([
            'organizer' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        return [
            'organizer' => isset($validated['organizer']) ? (int) $validated['organizer'] : null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
        ];
    }
}

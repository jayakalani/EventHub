<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\GenerateOrganizerReportRequest;
use App\Models\Event;
use App\Services\Exports\OrganizerReportExportBuilder;
use App\Services\OrganizerDashboardService;
use App\Services\OrganizerReportService;
use App\Services\OrganizerReports\OrganizerReportRegistry;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsReportSections;

    /** Matches sticky nav sections on the reports page. */
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
        // Legacy aliases
        'sales',
        'attendees',
    ];

    public function __construct(
        protected OrganizerReportService $reportService,
        protected OrganizerDashboardService $dashboardService,
        protected OrganizerReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
        protected OrganizerReportRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);

        $catalog = $this->registry->catalogFor($request->user());
        abort_if($catalog === [], 403);

        $defaultKey = array_key_first($catalog);

        return view('organizer.reports.builder', [
            'catalog' => $catalog,
            'groupedCatalog' => $this->registry->groupedCatalogFor($request->user()),
            'defaultReport' => $defaultKey,
            'oldReport' => old('report', $defaultKey),
            'oldFields' => old('fields', []),
            'oldFilters' => old('filters', []),
            'oldFormat' => old('format', 'pdf'),
        ]);
    }

    public function generate(GenerateOrganizerReportRequest $request): Response
    {
        $this->authorize('viewAny', Event::class);

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
        $this->authorize('viewAny', Event::class);

        $organizerId = (int) Auth::id();

        $validated = $request->validate([
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', $organizerId)),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Event::STATUS_UPCOMING,
                    Event::STATUS_ONGOING,
                    Event::STATUS_POSTPONED,
                    Event::STATUS_COMPLETED,
                    Event::STATUS_CANCELLED,
                ]),
            ],
            'period' => ['nullable', 'string', Rule::in(['week', 'month', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $eventId = isset($validated['event_id']) ? (int) $validated['event_id'] : null;
        $period = (string) ($validated['period'] ?? 'month');
        if (! in_array($period, ['week', 'month', 'custom'], true)) {
            $period = 'month';
        }

        if (! empty($validated['date_from']) || ! empty($validated['date_to'])) {
            $from = $validated['date_from'] ?? null;
            $to = $validated['date_to'] ?? null;
        } elseif ($period === 'custom') {
            $from = null;
            $to = null;
        } elseif ($period === 'week') {
            $from = now()->subDays(6)->toDateString();
            $to = now()->toDateString();
        } else {
            $from = now()->subDays(29)->toDateString();
            $to = now()->toDateString();
        }

        $reportFilters = [
            'from' => $from,
            'to' => $to,
            'event_id' => $eventId,
            'status' => $validated['status'] ?? null,
        ];

        return response()->json([
            'dashboard' => $this->dashboardService->getDashboardData(
                $organizerId,
                $eventId,
                $eventId,
                $eventId,
                $eventId,
                $eventId,
            ),
            'reports' => $this->reportService->getAllReports($organizerId, $reportFilters),
        ]);
    }

    /**
     * Lazy-load a single tab payload as JSON (charts + Alpine tables).
     */
    public function tabData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Event::class);

        $filters = $this->validatedFilters($request);
        $tab = $this->reportService->normalizeReportTab(
            (string) $request->validate([
                'tab' => ['required', 'string', Rule::in(array_merge(
                    $this->reportService->reportTabs(),
                    ['sales', 'attendees', 'overview'],
                ))],
            ])['tab']
        );

        $data = $this->reportService->getTabReports((int) Auth::id(), $filters, $tab);

        return response()->json([
            'tab' => $tab,
            'data' => $data,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedFilters($request);
        $payload = $this->exportBuilder->build((int) Auth::id(), $section, $filters);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('organizer-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $filters = $this->validatedFilters($request);
        $payload = $this->exportBuilder->build((int) Auth::id(), 'full', $filters);
        $payload['charts'] = $this->validatedChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('organizer-report', 'full', 'pdf'),
            'organizer.exports.report-pdf',
        );
    }

    /**
     * @return array{from?: string|null, to?: string|null, event_id?: int|null, status?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
            'event_id' => $request->filled('event_id') ? $request->input('event_id') : null,
            'status' => $request->filled('status') ? $request->input('status') : null,
        ]);

        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', Auth::id())),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Event::STATUS_UPCOMING,
                    Event::STATUS_ONGOING,
                    Event::STATUS_POSTPONED,
                    Event::STATUS_COMPLETED,
                    Event::STATUS_CANCELLED,
                ]),
            ],
        ]);
    }
}

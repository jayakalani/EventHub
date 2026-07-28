<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Exports\OrganizerReportExportBuilder;
use App\Services\OrganizerReportService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ExportsReportSections;

    /** Matches sticky nav sections on the reports page. */
    private const SECTIONS = [
        'overview',
        'revenue',
        'tickets',
        'events',
        'engagement',
        'audience',
        'activity',
        // Legacy aliases
        'sales',
        'attendees',
    ];

    public function __construct(
        protected OrganizerReportService $reportService,
        protected OrganizerReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $reports = $this->reportService->getAllReports((int) Auth::id(), $filters);

        return view('organizer.reports.index', compact('reports'));
    }

    public function exportExcel(Request $request)
    {
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
        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedFilters($request);
        $payload = $this->exportBuilder->build((int) Auth::id(), $section, $filters);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('organizer-report', $section, 'pdf'),
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
                    Event::STATUS_COMPLETED,
                    Event::STATUS_CANCELLED,
                ]),
            ],
        ]);
    }
}

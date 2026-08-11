<?php

namespace App\Http\Controllers\Cro;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Services\CroReportService;
use App\Services\Exports\CroReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ExportsReportSections;

    private const SECTIONS = ['inquiries', 'complaints'];

    public function __construct(
        protected CroReportService $reportService,
        protected CroReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $reports = $this->reportService->getAllReports($filters, (int) Auth::id());

        return view('cro.reports.index', compact('reports'));
    }

    public function exportExcel(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedFilters($request);
        $payload = $this->exportBuilder->build($section, $filters);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('cro-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $filters = $this->validatedFilters($request);
        $payload = $this->exportBuilder->build($section, $filters);
        $payload['charts'] = $this->validatedChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('cro-report', $section, 'pdf'),
        );
    }

    /**
     * @return array{event: int|null, cro: int|null, range: string|null, from: string|null, to: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'event' => $request->filled('event') ? $request->input('event') : null,
            'cro' => $request->filled('cro') ? $request->input('cro') : null,
            'range' => $request->filled('range') ? $request->input('range') : null,
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $validated = $request->validate([
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'cro' => ['nullable', 'integer', 'exists:users,id'],
            'range' => ['nullable', Rule::in(['week', 'month', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'cro' => isset($validated['cro']) ? (int) $validated['cro'] : null,
            'range' => $validated['range'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }
}

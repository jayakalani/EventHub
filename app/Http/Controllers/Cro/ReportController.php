<?php

namespace App\Http\Controllers\Cro;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cro\GenerateCroReportRequest;
use App\Services\CroReportService;
use App\Services\CroReports\CroReportRegistry;
use App\Services\Exports\CroReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsReportSections;

    private const SECTIONS = ['inquiries', 'complaints'];

    public function __construct(
        protected CroReportService $reportService,
        protected CroReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
        protected CroReportRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $catalog = $this->registry->catalogFor($request->user());
        abort_if($catalog === [], 403);

        $defaultKey = array_key_first($catalog);

        return view('cro.reports.builder', [
            'catalog' => $catalog,
            'defaultReport' => $defaultKey,
            'oldReport' => old('report', $defaultKey),
            'oldFields' => old('fields', []),
            'oldFilters' => old('filters', []),
            'oldFormat' => old('format', 'pdf'),
        ]);
    }

    public function generate(GenerateCroReportRequest $request): Response
    {
        $reportKey = (string) $request->input('report');
        $format = (string) $request->input('format');
        $generator = $this->registry->generator($reportKey);

        return $generator->generate(
            $request->user(),
            $request->selectedFields(),
            $request->selectedFilters(),
            $format,
        );
    }

    /**
     * Legacy dashboard chart-section exports (Excel / PDF with charts).
     */
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
            'range' => $request->filled('range') ? $request->input('range') : null,
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $validated = $request->validate([
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'range' => ['nullable', Rule::in(['week', 'month', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'cro' => null,
            'range' => $validated['range'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }
}

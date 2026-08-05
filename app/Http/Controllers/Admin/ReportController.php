<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Services\AdminReportService;
use App\Services\Exports\AdminReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ExportsReportSections;

    private const SECTIONS = ['admin', 'users', 'payments'];

    public function __construct(
        protected AdminReportService $reportService,
        protected AdminReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedScopeFilters($request);
        $reports = $this->reportService->getAllReports(
            $filters['organizer'],
            $filters['event'],
        );

        return view('admin.reports.index', compact('reports'));
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

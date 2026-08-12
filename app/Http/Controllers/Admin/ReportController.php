<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateAdminReportRequest;
use App\Services\AdminReportService;
use App\Services\AdminReports\AdminReportRegistry;
use App\Services\Exports\AdminReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ExportsReportSections;

    private const SECTIONS = ['admin', 'users', 'payments'];

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

        return $generator->generate(
            $request->user(),
            $request->selectedFields(),
            $request->selectedFilters(),
            $format,
        );
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

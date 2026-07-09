<?php

namespace App\Http\Controllers\Cro;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Services\CroReportService;
use App\Services\Exports\CroReportExportBuilder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
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

    public function index(): View
    {
        $reports = $this->reportService->getAllReports();

        return view('cro.reports.index', compact('reports'));
    }

    public function exportExcel(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build($section);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('cro-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build($section);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('cro-report', $section, 'pdf'),
        );
    }
}

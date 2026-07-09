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

    private const SECTIONS = ['admin', 'users', 'payments', 'system'];

    public function __construct(
        protected AdminReportService $reportService,
        protected AdminReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function index(): View
    {
        $reports = $this->reportService->getAllReports();

        return view('admin.reports.index', compact('reports'));
    }

    public function exportExcel(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build($section);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('admin-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build($section);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('admin-report', $section, 'pdf'),
        );
    }
}

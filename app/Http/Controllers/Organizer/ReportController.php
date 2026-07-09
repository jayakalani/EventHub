<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Concerns\ExportsReportSections;
use App\Http\Controllers\Controller;
use App\Services\Exports\OrganizerReportExportBuilder;
use App\Services\OrganizerReportService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ExportsReportSections;

    private const SECTIONS = ['sales', 'revenue', 'attendees', 'engagement'];

    public function __construct(
        protected OrganizerReportService $reportService,
        protected OrganizerReportExportBuilder $exportBuilder,
        protected ReportExportService $exportService,
    ) {}

    public function index(): View
    {
        $reports = $this->reportService->getAllReports(Auth::id());

        return view('organizer.reports.index', compact('reports'));
    }

    public function exportExcel(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build(Auth::id(), $section);

        return $this->exportService->downloadExcel(
            $payload,
            $this->exportFilename('organizer-report', $section, 'xlsx'),
        );
    }

    public function exportPdf(Request $request)
    {
        $section = $this->validatedSection($request, self::SECTIONS);
        $payload = $this->exportBuilder->build(Auth::id(), $section);

        return $this->exportService->downloadPdf(
            $payload,
            $this->exportFilename('organizer-report', $section, 'pdf'),
        );
    }
}

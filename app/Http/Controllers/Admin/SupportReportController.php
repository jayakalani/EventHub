<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Inquiry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;

class SupportReportController extends Controller
{
    public function index(): View
    {
        $totalInquiries = Inquiry::count();
        $totalComplaints = Complaint::count();

        $resolvedInquiries = Inquiry::where('status', SupportTicketStatusEnum::Resolved)->count();
        $resolvedComplaints = Complaint::where('status', SupportTicketStatusEnum::Resolved)->count();
        $resolvedCount = $resolvedInquiries + $resolvedComplaints;

        $pendingInquiries = Inquiry::whereIn('status', [
            SupportTicketStatusEnum::Open,
            SupportTicketStatusEnum::InProgress,
        ])->count();
        $pendingComplaints = Complaint::whereIn('status', [
            SupportTicketStatusEnum::Open,
            SupportTicketStatusEnum::InProgress,
        ])->count();
        $pendingCount = $pendingInquiries + $pendingComplaints;

        $recentInquiries = Inquiry::with(['user', 'event'])->latest()->limit(10)->get();
        $recentComplaints = Complaint::with('user')->latest()->limit(10)->get();

        return view('admin.support-reports', compact(
            'totalInquiries',
            'totalComplaints',
            'resolvedCount',
            'pendingCount',
            'pendingInquiries',
            'pendingComplaints',
            'resolvedInquiries',
            'resolvedComplaints',
            'recentInquiries',
            'recentComplaints',
        ));
    }

    public function exportCsv()
    {
        $inquiries = Inquiry::with(['user', 'event'])->latest()->get();
        $complaints = Complaint::with('user')->latest()->get();

        $filename = 'support-report-'.now()->format('Y-m-d-H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($inquiries, $complaints) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Type', 'ID', 'Subject', 'User', 'Event/Context', 'Status', 'Submitted At']);

            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    'Inquiry',
                    $inquiry->id,
                    $inquiry->subject,
                    $inquiry->user->full_name,
                    $inquiry->event->name,
                    $inquiry->status->label(),
                    $inquiry->created_at,
                ]);
            }

            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    'Complaint',
                    $complaint->id,
                    $complaint->subject,
                    $complaint->user->full_name,
                    'General',
                    $complaint->status->label(),
                    $complaint->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function exportPdf()
    {
        $totalInquiries = Inquiry::count();
        $totalComplaints = Complaint::count();
        $resolvedCount = Inquiry::where('status', SupportTicketStatusEnum::Resolved)->count()
            + Complaint::where('status', SupportTicketStatusEnum::Resolved)->count();
        $pendingCount = Inquiry::whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count()
            + Complaint::whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count();

        $inquiries = Inquiry::with(['user', 'event'])->latest()->get();
        $complaints = Complaint::with('user')->latest()->get();

        $pdf = Pdf::loadView('admin.exports.support-report-pdf', compact(
            'totalInquiries',
            'totalComplaints',
            'resolvedCount',
            'pendingCount',
            'inquiries',
            'complaints',
        ))->setPaper('a4', 'landscape');

        return $pdf->download('support-report.pdf');
    }
}

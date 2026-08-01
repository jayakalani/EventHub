<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportReportController extends Controller
{
    public function index(Request $request): View
    {
        [$cros, $selectedCroId, $selectedCroName] = $this->resolveCroFilter($request);

        $inquiryQuery = $this->scopedInquiryQuery($selectedCroId);
        $complaintQuery = $this->scopedComplaintQuery($selectedCroId);

        $totalInquiries = (clone $inquiryQuery)->count();
        $totalComplaints = (clone $complaintQuery)->count();

        $resolvedInquiries = (clone $inquiryQuery)->where('status', SupportTicketStatusEnum::Resolved)->count();
        $resolvedComplaints = (clone $complaintQuery)->where('status', SupportTicketStatusEnum::Resolved)->count();
        $resolvedCount = $resolvedInquiries + $resolvedComplaints;

        $pendingStatuses = [
            SupportTicketStatusEnum::Open,
            SupportTicketStatusEnum::InProgress,
        ];
        $pendingInquiries = (clone $inquiryQuery)->whereIn('status', $pendingStatuses)->count();
        $pendingComplaints = (clone $complaintQuery)->whereIn('status', $pendingStatuses)->count();
        $pendingCount = $pendingInquiries + $pendingComplaints;

        $recentInquiries = (clone $inquiryQuery)->with(['user', 'event'])->latest()->limit(10)->get();
        $recentComplaints = (clone $complaintQuery)->with('user')->latest()->limit(10)->get();

        return view('admin.support-reports', compact(
            'cros',
            'selectedCroId',
            'selectedCroName',
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

    public function exportCsv(Request $request)
    {
        [, $selectedCroId] = $this->resolveCroFilter($request);

        $inquiries = $this->scopedInquiryQuery($selectedCroId)->with(['user', 'event'])->latest()->get();
        $complaints = $this->scopedComplaintQuery($selectedCroId)->with('user')->latest()->get();

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

    public function exportPdf(Request $request)
    {
        [, $selectedCroId] = $this->resolveCroFilter($request);

        $inquiryQuery = $this->scopedInquiryQuery($selectedCroId);
        $complaintQuery = $this->scopedComplaintQuery($selectedCroId);

        $totalInquiries = (clone $inquiryQuery)->count();
        $totalComplaints = (clone $complaintQuery)->count();
        $resolvedCount = (clone $inquiryQuery)->where('status', SupportTicketStatusEnum::Resolved)->count()
            + (clone $complaintQuery)->where('status', SupportTicketStatusEnum::Resolved)->count();
        $pendingCount = (clone $inquiryQuery)->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count()
            + (clone $complaintQuery)->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count();

        $inquiries = (clone $inquiryQuery)->with(['user', 'event'])->latest()->get();
        $complaints = (clone $complaintQuery)->with('user')->latest()->get();

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

    /**
     * @return array{0: list<array{id: int, name: string}>, 1: int|null, 2: string|null}
     */
    private function resolveCroFilter(Request $request): array
    {
        $croRoleId = UserRole::query()->where('name_en', UserRole::CRO)->value('id');

        $cros = $croRoleId
            ? User::query()
                ->where('role_id', $croRoleId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ])
                ->values()
                ->all()
            : [];

        $selectedCroId = null;
        $selectedCroName = null;

        if ($request->filled('cro')) {
            $croId = (int) $request->input('cro');
            $selectedCro = collect($cros)->firstWhere('id', $croId);
            if ($selectedCro) {
                $selectedCroId = (int) $selectedCro['id'];
                $selectedCroName = $selectedCro['name'];
            }
        }

        return [$cros, $selectedCroId, $selectedCroName];
    }

    private function scopedInquiryQuery(?int $croId): Builder
    {
        $query = Inquiry::query();

        if ($croId) {
            $query->where('assigned_to', $croId);
        }

        return $query;
    }

    private function scopedComplaintQuery(?int $croId): Builder
    {
        $query = Complaint::query();

        if ($croId) {
            $query->where('assigned_to', $croId);
        }

        return $query;
    }
}

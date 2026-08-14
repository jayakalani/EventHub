<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupportReportController extends Controller
{
    /**
     * Support lives on the admin dashboard — keep old URLs working.
     */
    public function index(Request $request): RedirectResponse
    {
        $query = array_filter([
            'cro' => $request->input('cro'),
            'organizer' => $request->input('organizer'),
            'event' => $request->input('event'),
            'support' => 1,
            'section' => 'support',
        ], fn ($value) => filled($value));

        return redirect()->to(route('dashboard', $query).'#support');
    }

    /**
     * @return array{
     *     cros: list<array{id: int, name: string}>,
     *     selectedCroId: int|null,
     *     selectedCroName: string|null,
     *     selectedOrganizerId: int|null,
     *     selectedOrganizerName: string|null,
     *     selectedEventId: int|null,
     *     selectedEventName: string|null,
     *     scopeCaption: string,
     *     totalInquiries: int,
     *     totalComplaints: int,
     *     resolvedCount: int,
     *     pendingCount: int,
     *     pendingInquiries: int,
     *     pendingComplaints: int,
     *     resolvedInquiries: int,
     *     resolvedComplaints: int,
     *     recentInquiries: Collection,
     *     recentComplaints: Collection
     * }
     */
    public function buildReportData(
        ?int $selectedCroId = null,
        ?int $selectedOrganizerId = null,
        ?int $selectedEventId = null,
    ): array {
        [$cros, $resolvedCroId, $selectedCroName] = $this->resolveCroFilter($selectedCroId);
        [$resolvedOrganizerId, $selectedOrganizerName, $resolvedEventId, $selectedEventName] = $this->resolveEventScope(
            $selectedOrganizerId,
            $selectedEventId,
        );

        $inquiryQuery = $this->scopedInquiryQuery($resolvedCroId, $resolvedOrganizerId, $resolvedEventId);
        $complaintQuery = $this->scopedComplaintQuery($resolvedCroId, $resolvedOrganizerId, $resolvedEventId);

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
        $recentComplaints = (clone $complaintQuery)->with(['user', 'event'])->latest()->limit(10)->get();

        return [
            'cros' => $cros,
            'selectedCroId' => $resolvedCroId,
            'selectedCroName' => $selectedCroName,
            'selectedOrganizerId' => $resolvedOrganizerId,
            'selectedOrganizerName' => $selectedOrganizerName,
            'selectedEventId' => $resolvedEventId,
            'selectedEventName' => $selectedEventName,
            'scopeCaption' => $this->buildScopeCaption(
                $selectedCroName,
                $selectedOrganizerName,
                $selectedEventName,
            ),
            'totalInquiries' => $totalInquiries,
            'totalComplaints' => $totalComplaints,
            'resolvedCount' => $resolvedCount,
            'pendingCount' => $pendingCount,
            'pendingInquiries' => $pendingInquiries,
            'pendingComplaints' => $pendingComplaints,
            'resolvedInquiries' => $resolvedInquiries,
            'resolvedComplaints' => $resolvedComplaints,
            'recentInquiries' => $recentInquiries,
            'recentComplaints' => $recentComplaints,
        ];
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->validatedExportFilters($request);

        $inquiries = $this->scopedInquiryQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
        )->with(['user', 'event'])->latest()->get();

        $complaints = $this->scopedComplaintQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
        )->with(['user', 'event'])->latest()->get();

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
                    $inquiry->user?->full_name ?? '—',
                    $inquiry->event?->name ?? 'General',
                    $inquiry->status->label(),
                    $inquiry->created_at,
                ]);
            }

            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    'Complaint',
                    $complaint->id,
                    $complaint->subject,
                    $complaint->user?->full_name ?? '—',
                    $complaint->event?->name ?? 'General',
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
        $filters = $this->validatedExportFilters($request);

        $inquiryQuery = $this->scopedInquiryQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
        );
        $complaintQuery = $this->scopedComplaintQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
        );

        $totalInquiries = (clone $inquiryQuery)->count();
        $totalComplaints = (clone $complaintQuery)->count();
        $resolvedCount = (clone $inquiryQuery)->where('status', SupportTicketStatusEnum::Resolved)->count()
            + (clone $complaintQuery)->where('status', SupportTicketStatusEnum::Resolved)->count();
        $pendingCount = (clone $inquiryQuery)->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count()
            + (clone $complaintQuery)->whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count();

        $inquiries = (clone $inquiryQuery)->with(['user', 'event'])->latest()->get();
        $complaints = (clone $complaintQuery)->with(['user', 'event'])->latest()->get();

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
     * @return array{cro: int|null, organizer: int|null, event: int|null}
     */
    private function validatedExportFilters(Request $request): array
    {
        [, $croId] = $this->resolveCroFilter(
            $request->filled('cro') ? (int) $request->input('cro') : null
        );
        [$organizerId, , $eventId] = $this->resolveEventScope(
            $request->filled('organizer') ? (int) $request->input('organizer') : null,
            $request->filled('event') ? (int) $request->input('event') : null,
        );

        return [
            'cro' => $croId,
            'organizer' => $organizerId,
            'event' => $eventId,
        ];
    }

    /**
     * @return array{0: list<array{id: int, name: string}>, 1: int|null, 2: string|null}
     */
    private function resolveCroFilter(?int $requestedCroId): array
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

        if ($requestedCroId) {
            $selectedCro = collect($cros)->firstWhere('id', $requestedCroId);
            if ($selectedCro) {
                $selectedCroId = (int) $selectedCro['id'];
                $selectedCroName = $selectedCro['name'];
            }
        }

        return [$cros, $selectedCroId, $selectedCroName];
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: int|null, 3: string|null}
     */
    private function resolveEventScope(?int $organizerId, ?int $eventId): array
    {
        $resolvedOrganizerId = null;
        $selectedOrganizerName = null;
        $resolvedEventId = null;
        $selectedEventName = null;

        if ($organizerId) {
            $organizer = User::query()
                ->whereHas('userRole', fn ($q) => $q->where('name_en', UserRole::ORGANIZER))
                ->find($organizerId);

            if ($organizer) {
                $resolvedOrganizerId = (int) $organizer->id;
                $selectedOrganizerName = $organizer->full_name;
            }
        }

        if ($eventId) {
            $eventQuery = Event::query()->where('id', $eventId);
            if ($resolvedOrganizerId) {
                $eventQuery->where('created_by', $resolvedOrganizerId);
            }
            $event = $eventQuery->first(['id', 'name', 'created_by']);

            if ($event) {
                $resolvedEventId = (int) $event->id;
                $selectedEventName = $event->name;

                if (! $resolvedOrganizerId && $event->created_by) {
                    $owner = User::query()->find($event->created_by);
                    if ($owner) {
                        $resolvedOrganizerId = (int) $owner->id;
                        $selectedOrganizerName = $owner->full_name;
                    }
                }
            }
        }

        return [$resolvedOrganizerId, $selectedOrganizerName, $resolvedEventId, $selectedEventName];
    }

    private function buildScopeCaption(
        ?string $croName,
        ?string $organizerName,
        ?string $eventName,
    ): string {
        $parts = [];

        if ($croName) {
            $parts[] = 'Assigned to '.$croName;
        }
        if ($eventName) {
            $parts[] = 'Event: '.$eventName;
        } elseif ($organizerName) {
            $parts[] = 'Organizer: '.$organizerName;
        }

        return $parts !== []
            ? implode(' · ', $parts)
            : 'All customer relations officers';
    }

    private function scopedInquiryQuery(?int $croId, ?int $organizerId = null, ?int $eventId = null): Builder
    {
        return $this->applySupportScope(Inquiry::query(), $croId, $organizerId, $eventId);
    }

    private function scopedComplaintQuery(?int $croId, ?int $organizerId = null, ?int $eventId = null): Builder
    {
        return $this->applySupportScope(Complaint::query(), $croId, $organizerId, $eventId);
    }

    private function applySupportScope(
        Builder $query,
        ?int $croId,
        ?int $organizerId,
        ?int $eventId,
    ): Builder {
        if ($croId) {
            $query->where('assigned_to', $croId);
        }

        if ($eventId) {
            $query->where('event_id', $eventId);
        } elseif ($organizerId) {
            $query->whereIn(
                'event_id',
                Event::query()->where('created_by', $organizerId)->select('id')
            );
        }

        return $query;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;
use App\Support\CroSupportSla;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            'from' => $request->input('from'),
            'to' => $request->input('to'),
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
     *     recentComplaints: Collection,
     *     volumeTrend: array{labels: list<string>, inquiries: list<int>, complaints: list<int>},
     *     slaAging: list<array{key: string, label: string, count: int, color: string}>
     * }
     */
    public function buildReportData(
        ?int $selectedCroId = null,
        ?int $selectedOrganizerId = null,
        ?int $selectedEventId = null,
        ?string $from = null,
        ?string $to = null,
    ): array {
        [$cros, $resolvedCroId, $selectedCroName] = $this->resolveCroFilter($selectedCroId);
        [$resolvedOrganizerId, $selectedOrganizerName, $resolvedEventId, $selectedEventName] = $this->resolveEventScope(
            $selectedOrganizerId,
            $selectedEventId,
        );

        $inquiryQuery = $this->scopedInquiryQuery($resolvedCroId, $resolvedOrganizerId, $resolvedEventId, $from, $to);
        $complaintQuery = $this->scopedComplaintQuery($resolvedCroId, $resolvedOrganizerId, $resolvedEventId, $from, $to);

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
            'volumeTrend' => $this->volumeTrend($inquiryQuery, $complaintQuery, $from, $to),
            'slaAging' => $this->slaAging($inquiryQuery, $complaintQuery, $pendingStatuses),
        ];
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->validatedExportFilters($request);

        $inquiries = $this->scopedInquiryQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
            $filters['from'],
            $filters['to'],
        )->with(['user', 'event'])->latest()->get();

        $complaints = $this->scopedComplaintQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
            $filters['from'],
            $filters['to'],
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
            $filters['from'],
            $filters['to'],
        );
        $complaintQuery = $this->scopedComplaintQuery(
            $filters['cro'],
            $filters['organizer'],
            $filters['event'],
            $filters['from'],
            $filters['to'],
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
     * @return array{cro: int|null, organizer: int|null, event: int|null, from: string|null, to: string|null}
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

        $request->merge([
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'cro' => $croId,
            'organizer' => $organizerId,
            'event' => $eventId,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
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
            $eventQuery = Event::query()->forFilter()->where('id', $eventId);
            if ($resolvedOrganizerId) {
                $eventQuery->where('created_by', $resolvedOrganizerId);
            }
            $event = $eventQuery->first(['id', 'name', 'created_by', 'deleted_at']);

            if ($event) {
                $resolvedEventId = (int) $event->id;
                $selectedEventName = $event->filterLabel();

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

    /**
     * Weekly inquiry vs complaint volume. Uses the last 8 ISO weeks, clipped to the date chips.
     *
     * @return array{labels: list<string>, inquiries: list<int>, complaints: list<int>}
     */
    private function volumeTrend(Builder $inquiryQuery, Builder $complaintQuery, ?string $from, ?string $to): array
    {
        $end = filled($to) ? Carbon::parse($to)->endOfDay() : now();
        $start = $end->copy()->subWeeks(7)->startOfWeek(Carbon::MONDAY);

        if (filled($from)) {
            $fromStart = Carbon::parse($from)->startOfWeek(Carbon::MONDAY);
            if ($fromStart->gt($start)) {
                $start = $fromStart;
            }
        }

        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end) && count($weeks) < 8) {
            $weeks[$cursor->format('o-W')] = $cursor->format('M j');
            $cursor->addWeek();
        }

        if ($weeks === []) {
            return ['labels' => [], 'inquiries' => [], 'complaints' => []];
        }

        $keys = array_keys($weeks);
        $inquiryCounts = $this->weeklyCounts($inquiryQuery);
        $complaintCounts = $this->weeklyCounts($complaintQuery);

        return [
            'labels' => array_values($weeks),
            'inquiries' => array_map(fn (string $key) => (int) ($inquiryCounts[$key] ?? 0), $keys),
            'complaints' => array_map(fn (string $key) => (int) ($complaintCounts[$key] ?? 0), $keys),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function weeklyCounts(Builder $query): array
    {
        return (clone $query)
            ->selectRaw("DATE_FORMAT(created_at, '%x-%v') as week, COUNT(*) as count")
            ->groupByRaw("DATE_FORMAT(created_at, '%x-%v')")
            ->pluck('count', 'week')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Open tickets scored against 24h aging / 48h overdue (plus urgent subjects).
     *
     * @param  list<SupportTicketStatusEnum>  $pendingStatuses
     * @return list<array{key: string, label: string, count: int, color: string}>
     */
    private function slaAging(Builder $inquiryQuery, Builder $complaintQuery, array $pendingStatuses): array
    {
        $counts = [
            'ok' => 0,
            'aging' => 0,
            'overdue' => 0,
            'urgent' => 0,
        ];

        $tickets = (clone $inquiryQuery)
            ->whereIn('status', $pendingStatuses)
            ->get(['subject', 'created_at'])
            ->concat(
                (clone $complaintQuery)
                    ->whereIn('status', $pendingStatuses)
                    ->get(['subject', 'created_at'])
            );

        foreach ($tickets as $ticket) {
            $level = CroSupportSla::level($ticket->created_at, $ticket->subject, true);
            $counts[$level] = ($counts[$level] ?? 0) + 1;
        }

        $colors = [
            'ok' => 'rgba(16, 185, 129, 0.85)',
            'aging' => 'rgba(245, 158, 11, 0.85)',
            'overdue' => 'rgba(249, 115, 22, 0.85)',
            'urgent' => 'rgba(244, 63, 94, 0.85)',
        ];

        return collect(['ok', 'aging', 'overdue', 'urgent'])
            ->map(fn (string $key) => [
                'key' => $key,
                'label' => CroSupportSla::levelLabel($key),
                'count' => $counts[$key],
                'color' => $colors[$key],
            ])
            ->all();
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

    private function scopedInquiryQuery(
        ?int $croId,
        ?int $organizerId = null,
        ?int $eventId = null,
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return $this->applySupportScope(Inquiry::query(), $croId, $organizerId, $eventId, $from, $to);
    }

    private function scopedComplaintQuery(
        ?int $croId,
        ?int $organizerId = null,
        ?int $eventId = null,
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return $this->applySupportScope(Complaint::query(), $croId, $organizerId, $eventId, $from, $to);
    }

    private function applySupportScope(
        Builder $query,
        ?int $croId,
        ?int $organizerId,
        ?int $eventId,
        ?string $from = null,
        ?string $to = null,
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

        if (filled($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        if (filled($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}

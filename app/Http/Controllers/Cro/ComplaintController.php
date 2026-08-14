<?php

namespace App\Http\Controllers\Cro;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Event;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ComplaintService;
use App\Services\CroCaseContextService;
use App\Support\CroReplyTemplates;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
        protected CroCaseContextService $caseContextService,
    ) {}

    public function index(Request $request): View
    {
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);

        $baseQuery = Complaint::query()->forCroQueue($croId, 'mine');

        $counts = [
            'open' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Open)->count(),
            'in_progress' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::InProgress)->count(),
            'resolved' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Resolved)->count(),
            'closed' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Closed)->count(),
        ];

        $complaints = $this->applyFilters(clone $baseQuery, $filters, $croId)
            ->with(['user', 'event', 'attachments', 'assignee'])
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString();

        $events = Event::query()
            ->assignedToCro($croId)
            ->orderBy('name')
            ->get(['id', 'name', 'deleted_at']);

        $statuses = SupportTicketStatusEnum::cases();

        return view('cro.complaints.index', compact(
            'complaints',
            'counts',
            'filters',
            'events',
            'statuses',
        ));
    }

    public function exportCsv(Request $request): Response
    {
        $complaints = $this->exportRows($request);

        $filename = 'complaints_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($complaints) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Subject',
                'Attendee',
                'Email',
                'Event',
                'Assignee',
                'Status',
                'Attachments',
                'Submitted At',
                'Message',
            ]);

            foreach ($complaints as $complaint) {
                fputcsv($file, [
                    $complaint->id,
                    $complaint->subject,
                    $complaint->user?->full_name ?? '',
                    $complaint->user?->email ?? '',
                    $complaint->event?->name ?? 'General',
                    $complaint->assignee?->full_name ?? 'Unassigned',
                    $complaint->status->label(),
                    $complaint->attachments->count(),
                    $complaint->created_at?->format('Y-m-d H:i'),
                    $complaint->message,
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);
        $complaints = $this->exportRows($request, $filters, $croId);

        $eventName = $filters['event']
            ? Event::query()->assignedToCro($croId)->whereKey($filters['event'])->value('name')
            : null;

        $pdf = Pdf::loadView('cro.exports.complaints_pdf', [
            'complaints' => $complaints,
            'filters' => $filters,
            'eventName' => $eventName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('complaints_'.now()->format('Ymd_His').'.pdf');
    }

    public function show(Complaint $complaint): View
    {
        $this->authorize('view', $complaint);

        $complaint->load(['user', 'event.contactPerson', 'attachments', 'responses.user', 'assignee']);
        $caseContext = $this->caseContextService->forComplaint($complaint);
        $replyTemplates = CroReplyTemplates::forComplaints();
        $croUsers = $this->croUsers();

        return view('cro.complaints.show', compact('complaint', 'caseContext', 'replyTemplates', 'croUsers'));
    }

    public function reply(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $this->complaintService->reply($complaint, Auth::user(), $validated['message']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.complaints.show', $complaint)
            ->with('success', 'Reply sent to the attendee.');
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        try {
            $this->complaintService->updateStatus(
                $complaint,
                Auth::user(),
                SupportTicketStatusEnum::from($validated['status']),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.complaints.show', $complaint)
            ->with('success', 'Complaint status updated.');
    }

    public function claim(Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        try {
            $this->complaintService->claim($complaint, Auth::user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', 'Complaint claimed.');
    }

    public function reassign(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', $this->activeCroUserIdRule()],
        ], [
            'assigned_to.exists' => 'Please select an active CRO user.',
        ]);

        $assignee = isset($validated['assigned_to'])
            ? User::query()->find($validated['assigned_to'])
            : null;

        try {
            $this->complaintService->reassign($complaint, Auth::user(), $assignee);
        } catch (RuntimeException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', $assignee ? 'Complaint reassigned.' : 'Complaint marked unassigned.');
    }

    public function updateNotes(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->complaintService->updateInternalNotes(
                $complaint,
                Auth::user(),
                $validated['internal_notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['internal_notes' => $e->getMessage()]);
        }

        return back()->with('success', 'Internal notes saved.');
    }

    public function downloadAttachment(Complaint $complaint, ComplaintAttachment $attachment): BinaryFileResponse
    {
        $this->authorize('view', $complaint);

        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $path = public_path($attachment->file_path);
        abort_unless(is_file($path), 404);

        return response()->download($path, $attachment->original_filename);
    }

    /**
     * @param  array{status: ?string, q: ?string, event: ?int, from: ?string, to: ?string}|null  $filters
     * @return \Illuminate\Support\Collection<int, Complaint>
     */
    private function exportRows(Request $request, ?array $filters = null, ?int $croId = null)
    {
        $croId ??= (int) Auth::id();
        $filters ??= $this->validatedFilters($request);

        return $this->applyFilters(
            Complaint::query()->forCroQueue($croId, 'mine'),
            $filters,
            $croId,
        )
            ->with(['user', 'event', 'attachments', 'assignee'])
            ->oldest('created_at')
            ->get();
    }

    /**
     * @return array{status: ?string, q: ?string, event: ?int, from: ?string, to: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $croId = (int) Auth::id();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(array_column(SupportTicketStatusEnum::cases(), 'value'))],
            'q' => ['nullable', 'string', 'max:120'],
            'event' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where('contact_person', $croId),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'status' => $validated['status'] ?? null,
            'q' => filled($validated['q'] ?? null) ? trim($validated['q']) : null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    /**
     * @param  array{status: ?string, q: ?string, event: ?int, from: ?string, to: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters, int $croId): Builder
    {
        return $query
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['event'], function (Builder $q, int $eventId) use ($croId) {
                $q->where('event_id', $eventId)
                    ->whereHas('event', fn (Builder $event) => $event->assignedToCro($croId));
            })
            ->when($filters['from'], fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'], function (Builder $q, string $search) use ($croId) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $inner) use ($like, $croId) {
                    $inner->where('subject', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhereHas('user', function (Builder $user) use ($like) {
                            $user->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$like]);
                        })
                        ->orWhereHas('event', function (Builder $event) use ($like, $croId) {
                            $event->assignedToCro($croId)
                                ->where('name', 'like', $like);
                        });
                });
            });
    }

    private function croUsers()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('userRole', fn ($q) => $q->where('name_en', UserRole::CRO))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    private function activeCroUserIdRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('users', 'id')
            ->where('is_active', true)
            ->whereIn(
                'role_id',
                UserRole::query()->where('name_en', UserRole::CRO)->select('id')
            );
    }
}

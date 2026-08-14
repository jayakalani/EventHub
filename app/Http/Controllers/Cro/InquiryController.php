<?php

namespace App\Http\Controllers\Cro;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\UserRole;
use App\Services\CroCaseContextService;
use App\Services\InquiryService;
use App\Support\CroReplyTemplates;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class InquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService,
        protected CroCaseContextService $caseContextService,
    ) {}

    public function index(Request $request): View
    {
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);

        // Always limited to events where this CRO is the assigned contact person.
        $baseQuery = Inquiry::query()->forCroQueue($croId, 'mine');

        $counts = [
            'open' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Open)->count(),
            'in_progress' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::InProgress)->count(),
            'resolved' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Resolved)->count(),
            'closed' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Closed)->count(),
        ];

        $inquiries = $this->applyFilters(clone $baseQuery, $filters, $croId)
            ->with(['user', 'event', 'assignee'])
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString();

        $events = Event::query()
            ->assignedToCro($croId)
            ->orderBy('name')
            ->get(['id', 'name', 'deleted_at']);

        $statuses = SupportTicketStatusEnum::cases();

        return view('cro.inquiries.index', compact(
            'inquiries',
            'counts',
            'filters',
            'events',
            'statuses',
        ));
    }

    public function exportCsv(Request $request): Response
    {
        $inquiries = $this->exportRows($request);

        $filename = 'inquiries_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($inquiries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Subject',
                'Attendee',
                'Email',
                'Event',
                'Assignee',
                'Status',
                'Submitted At',
                'Message',
            ]);

            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    $inquiry->id,
                    $inquiry->subject,
                    $inquiry->user?->full_name ?? '',
                    $inquiry->user?->email ?? '',
                    $inquiry->event?->name ?? '—',
                    $inquiry->assignee?->full_name ?? 'Unassigned',
                    $inquiry->status->label(),
                    $inquiry->created_at?->format('Y-m-d H:i'),
                    $inquiry->message,
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
        $inquiries = $this->exportRows($request, $filters, $croId);

        $eventName = $filters['event']
            ? Event::query()->assignedToCro($croId)->whereKey($filters['event'])->value('name')
            : null;

        $pdf = Pdf::loadView('cro.exports.inquiries_pdf', [
            'inquiries' => $inquiries,
            'filters' => $filters,
            'eventName' => $eventName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inquiries_'.now()->format('Ymd_His').'.pdf');
    }

    public function show(Inquiry $inquiry): View
    {
        $this->authorize('view', $inquiry);

        $inquiry->load(['user', 'event', 'responses.user', 'assignee']);
        $caseContext = $this->caseContextService->forInquiry($inquiry);
        $replyTemplates = CroReplyTemplates::forInquiries();
        $croUsers = $this->croUsers();

        return view('cro.inquiries.show', compact('inquiry', 'caseContext', 'replyTemplates', 'croUsers'));
    }

    public function reply(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $this->inquiryService->reply($inquiry, Auth::user(), $validated['message']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.inquiries.show', $inquiry)
            ->with('success', 'Reply sent to the attendee.');
    }

    public function updateStatus(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        try {
            $this->inquiryService->updateStatus(
                $inquiry,
                Auth::user(),
                SupportTicketStatusEnum::from($validated['status']),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.inquiries.show', $inquiry)
            ->with('success', 'Inquiry status updated.');
    }

    public function claim(Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        try {
            $this->inquiryService->claim($inquiry, Auth::user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', 'Inquiry claimed.');
    }

    public function reassign(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', $this->activeCroUserIdRule()],
        ], [
            'assigned_to.exists' => 'Please select an active CRO user.',
        ]);

        $assignee = isset($validated['assigned_to'])
            ? User::query()->find($validated['assigned_to'])
            : null;

        try {
            $this->inquiryService->reassign($inquiry, Auth::user(), $assignee);
        } catch (RuntimeException $e) {
            return back()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('success', $assignee ? 'Inquiry reassigned.' : 'Inquiry marked unassigned.');
    }

    public function updateNotes(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('update', $inquiry);

        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->inquiryService->updateInternalNotes(
                $inquiry,
                Auth::user(),
                $validated['internal_notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['internal_notes' => $e->getMessage()]);
        }

        return back()->with('success', 'Internal notes saved.');
    }

    /**
     * @param  array{status: ?string, q: ?string, event: ?int, from: ?string, to: ?string}|null  $filters
     * @return \Illuminate\Support\Collection<int, Inquiry>
     */
    private function exportRows(Request $request, ?array $filters = null, ?int $croId = null)
    {
        $croId ??= (int) Auth::id();
        $filters ??= $this->validatedFilters($request);

        return $this->applyFilters(
            Inquiry::query()->forCroQueue($croId, 'mine'),
            $filters,
            $croId,
        )
            ->with(['user', 'event', 'assignee'])
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

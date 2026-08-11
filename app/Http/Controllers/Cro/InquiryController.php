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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class InquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService,
        protected CroCaseContextService $caseContextService,
    ) {}

    public function index(Request $request): View
    {
        $queueScope = $this->resolveQueueScope($request);
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);

        $baseQuery = Inquiry::query()->forCroQueue($croId, $queueScope);

        $counts = [
            'open' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Open)->count(),
            'in_progress' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::InProgress)->count(),
            'resolved' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Resolved)->count(),
            'closed' => (clone $baseQuery)->where('status', SupportTicketStatusEnum::Closed)->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_to')->count(),
        ];

        $inquiries = $this->applyFilters(clone $baseQuery, $filters, $croId)
            ->with(['user', 'event', 'assignee'])
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString();

        $events = Event::query()
            ->whereIn('id', (clone $baseQuery)->whereNotNull('event_id')->select('event_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = SupportTicketStatusEnum::cases();

        return view('cro.inquiries.index', compact(
            'inquiries',
            'counts',
            'filters',
            'queueScope',
            'events',
            'statuses',
        ));
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

        $this->inquiryService->updateStatus(
            $inquiry,
            Auth::user(),
            SupportTicketStatusEnum::from($validated['status']),
        );

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
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
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

        $this->inquiryService->updateInternalNotes(
            $inquiry,
            Auth::user(),
            $validated['internal_notes'] ?? null,
        );

        return back()->with('success', 'Internal notes saved.');
    }

    /**
     * @return array{status: ?string, assignment: string, q: ?string, event: ?int, from: ?string, to: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(array_column(SupportTicketStatusEnum::cases(), 'value'))],
            'assignment' => ['nullable', 'string', Rule::in(['all', 'unassigned', 'me'])],
            'q' => ['nullable', 'string', 'max:120'],
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'status' => $validated['status'] ?? null,
            'assignment' => $validated['assignment'] ?? 'all',
            'q' => filled($validated['q'] ?? null) ? trim($validated['q']) : null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @param  array{status: ?string, assignment: string, q: ?string, event: ?int, from: ?string, to: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters, int $croId): Builder
    {
        return $query
            ->assignmentFilter($filters['assignment'], $croId)
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['event'], fn (Builder $q, int $eventId) => $q->where('event_id', $eventId))
            ->when($filters['from'], fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'], function (Builder $q, string $search) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('subject', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhereHas('user', function (Builder $user) use ($like) {
                            $user->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$like]);
                        })
                        ->orWhereHas('event', fn (Builder $event) => $event->where('name', 'like', $like));
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

    private function resolveQueueScope(Request $request): string
    {
        return $request->query('scope') === 'all' ? 'all' : 'mine';
    }
}

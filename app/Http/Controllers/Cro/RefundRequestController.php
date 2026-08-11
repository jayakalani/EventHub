<?php

namespace App\Http\Controllers\Cro;

use App\Enums\RefundRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RefundRequest;
use App\Services\CroCaseContextService;
use App\Services\RefundRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class RefundRequestController extends Controller
{
    public function __construct(
        protected RefundRequestService $refundRequestService,
        protected CroCaseContextService $caseContextService,
    ) {}

    public function index(Request $request): View
    {
        $queueScope = $this->resolveQueueScope($request);
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);

        $baseQuery = RefundRequest::query()->forCroQueue($croId, $queueScope);

        $counts = [
            'pending' => (clone $baseQuery)->where('status', RefundRequestStatusEnum::Pending)->count(),
            'approved' => (clone $baseQuery)->where('status', RefundRequestStatusEnum::Approved)->count(),
            'declined' => (clone $baseQuery)->where('status', RefundRequestStatusEnum::Declined)->count(),
            'processed' => (clone $baseQuery)->whereIn('status', [
                RefundRequestStatusEnum::Approved,
                RefundRequestStatusEnum::Declined,
                RefundRequestStatusEnum::AutoDeclined,
            ])->count(),
        ];

        $refundRequests = $this->applyFilters(clone $baseQuery, $filters)
            ->with([
                'user',
                'reviewer',
                'ticketBooking.event',
                'ticketBooking.ticketCategory',
            ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $eventIds = (clone $baseQuery)
            ->join('ticket_bookings', 'ticket_bookings.id', '=', 'refund_requests.ticket_booking_id')
            ->distinct()
            ->pluck('ticket_bookings.event_id');

        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = RefundRequestStatusEnum::cases();

        return view('cro.refund-requests.index', compact(
            'refundRequests',
            'counts',
            'queueScope',
            'filters',
            'events',
            'statuses',
        ));
    }

    public function show(RefundRequest $refundRequest): View
    {
        $this->authorize('view', $refundRequest);

        $refundRequest->load([
            'user',
            'reviewer',
            'ticketBooking.event',
            'ticketBooking.ticketCategory',
            'ticketBooking.payment',
        ]);
        $caseContext = $this->caseContextService->forRefund($refundRequest);

        return view('cro.refund-requests.show', compact('refundRequest', 'caseContext'));
    }

    public function approve(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $this->authorize('update', $refundRequest);

        $validated = $request->validate([
            'cro_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->refundRequestService->approve($refundRequest, Auth::user(), $validated['cro_notes'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.refund-requests.show', $refundRequest)
            ->with('success', 'Refund approved and credited to the attendee wallet.');
    }

    public function decline(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $this->authorize('update', $refundRequest);

        $validated = $request->validate([
            'cro_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'cro_notes.required' => 'A reason is required when declining a refund request.',
            'cro_notes.min' => 'Please provide at least 10 characters explaining why the refund was declined.',
        ]);

        try {
            $this->refundRequestService->decline($refundRequest, Auth::user(), $validated['cro_notes']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return redirect()
            ->route('cro.refund-requests.show', $refundRequest)
            ->with('success', 'Refund request declined.');
    }

    /**
     * @return array{status: ?string, event: ?int, from: ?string, to: ?string, q: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                ...array_column(RefundRequestStatusEnum::cases(), 'value'),
                'processed',
            ])],
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'status' => $validated['status'] ?? null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'from' => $from,
            'to' => $to,
            'q' => filled($validated['q'] ?? null) ? trim($validated['q']) : null,
        ];
    }

    /**
     * @param  array{status: ?string, event: ?int, from: ?string, to: ?string, q: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] === 'processed', function (Builder $q) {
                $q->whereIn('status', [
                    RefundRequestStatusEnum::Approved,
                    RefundRequestStatusEnum::Declined,
                    RefundRequestStatusEnum::AutoDeclined,
                ]);
            })
            ->when(
                $filters['status'] && $filters['status'] !== 'processed',
                fn (Builder $q) => $q->where('status', $filters['status'])
            )
            ->when($filters['event'], function (Builder $q, int $eventId) {
                $q->whereHas('ticketBooking', fn (Builder $booking) => $booking->where('event_id', $eventId));
            })
            ->when($filters['from'], fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'], function (Builder $q, string $search) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $inner) use ($like) {
                    $inner->where('reason', 'like', $like)
                        ->orWhere('cro_notes', 'like', $like)
                        ->orWhereHas('user', function (Builder $user) use ($like) {
                            $user->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$like]);
                        })
                        ->orWhereHas('ticketBooking', function (Builder $booking) use ($like) {
                            $booking->where('ticket_number', 'like', $like)
                                ->orWhereHas('event', fn (Builder $event) => $event->where('name', 'like', $like));
                        });
                });
            });
    }

    private function resolveQueueScope(Request $request): string
    {
        return $request->query('scope') === 'all' ? 'all' : 'mine';
    }
}

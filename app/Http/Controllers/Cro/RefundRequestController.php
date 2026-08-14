<?php

namespace App\Http\Controllers\Cro;

use App\Enums\RefundRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RefundRequest;
use App\Services\CroCaseContextService;
use App\Services\RefundRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RefundRequestController extends Controller
{
    public function __construct(
        protected RefundRequestService $refundRequestService,
        protected CroCaseContextService $caseContextService,
    ) {}

    public function index(Request $request): View
    {
        $croId = (int) Auth::id();
        $filters = $this->validatedFilters($request);

        // Always limited to events where this CRO is the assigned contact person.
        $baseQuery = RefundRequest::query()->forCroQueue($croId, 'mine');

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

        $refundRequests = $this->applyFilters(clone $baseQuery, $filters, $croId)
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

        $events = Event::query()
            ->assignedToCro($croId)
            ->orderBy('name')
            ->get(['id', 'name', 'deleted_at']);

        $statuses = RefundRequestStatusEnum::cases();

        return view('cro.refund-requests.index', compact(
            'refundRequests',
            'counts',
            'filters',
            'events',
            'statuses',
        ));
    }

    public function exportCsv(Request $request): Response
    {
        $refundRequests = $this->exportRows($request);

        $filename = 'refund-requests_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($refundRequests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Event',
                'Ticket Number',
                'Attendee',
                'Email',
                'Amount (LKR)',
                'Percentage',
                'Status',
                'Reviewed By',
                'Reviewed At',
                'Requested At',
                'Reason',
                'CRO Notes',
            ]);

            foreach ($refundRequests as $refundRequest) {
                $reviewedBy = '—';
                if ($refundRequest->status === RefundRequestStatusEnum::Pending) {
                    $reviewedBy = 'Awaiting review';
                } elseif ($refundRequest->reviewer) {
                    $reviewedBy = $refundRequest->reviewer->full_name;
                } elseif ($refundRequest->status->isProcessed()) {
                    $reviewedBy = 'System';
                }

                fputcsv($file, [
                    $refundRequest->id,
                    $refundRequest->ticketBooking?->event?->name ?? '',
                    $refundRequest->ticketBooking?->ticket_number ?? '',
                    $refundRequest->user?->full_name ?? '',
                    $refundRequest->user?->email ?? '',
                    number_format((float) $refundRequest->refund_amount, 2, '.', ''),
                    $refundRequest->refund_percentage,
                    $refundRequest->status->label(),
                    $reviewedBy,
                    $refundRequest->reviewed_at?->format('Y-m-d H:i') ?? '',
                    $refundRequest->created_at?->format('Y-m-d H:i'),
                    $refundRequest->reason,
                    $refundRequest->cro_notes,
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
        $refundRequests = $this->exportRows($request, $filters, $croId);

        $eventName = $filters['event']
            ? Event::query()->assignedToCro($croId)->whereKey($filters['event'])->value('name')
            : null;

        $pdf = Pdf::loadView('cro.exports.refund-requests_pdf', [
            'refundRequests' => $refundRequests,
            'filters' => $filters,
            'eventName' => $eventName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('refund-requests_'.now()->format('Ymd_His').'.pdf');
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
     * @param  array{status: ?string, event: ?int, from: ?string, to: ?string, q: ?string}|null  $filters
     * @return \Illuminate\Support\Collection<int, RefundRequest>
     */
    private function exportRows(Request $request, ?array $filters = null, ?int $croId = null)
    {
        $croId ??= (int) Auth::id();
        $filters ??= $this->validatedFilters($request);

        return $this->applyFilters(
            RefundRequest::query()->forCroQueue($croId, 'mine'),
            $filters,
            $croId,
        )
            ->with([
                'user',
                'reviewer',
                'ticketBooking.event',
                'ticketBooking.ticketCategory',
            ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('created_at')
            ->get();
    }

    /**
     * @return array{status: ?string, event: ?int, from: ?string, to: ?string, q: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $croId = (int) Auth::id();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                ...array_column(RefundRequestStatusEnum::cases(), 'value'),
                'processed',
            ])],
            'event' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where('contact_person', $croId),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        return [
            'status' => $validated['status'] ?? null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'q' => filled($validated['q'] ?? null) ? trim($validated['q']) : null,
        ];
    }

    /**
     * @param  array{status: ?string, event: ?int, from: ?string, to: ?string, q: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters, int $croId): Builder
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
            ->when($filters['event'], function (Builder $q, int $eventId) use ($croId) {
                $q->whereHas('ticketBooking', function (Builder $booking) use ($eventId, $croId) {
                    $booking->where('event_id', $eventId)
                        ->whereHas('event', fn (Builder $event) => $event->assignedToCro($croId));
                });
            })
            ->when($filters['from'], fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'], function (Builder $q, string $search) use ($croId) {
                $like = '%'.$search.'%';
                $q->where(function (Builder $inner) use ($like, $croId) {
                    $inner->where('reason', 'like', $like)
                        ->orWhere('cro_notes', 'like', $like)
                        ->orWhereHas('user', function (Builder $user) use ($like) {
                            $user->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$like]);
                        })
                        ->orWhereHas('ticketBooking', function (Builder $booking) use ($like, $croId) {
                            $booking->where('ticket_number', 'like', $like)
                                ->orWhereHas('event', function (Builder $event) use ($like, $croId) {
                                    $event->assignedToCro($croId)
                                        ->where('name', 'like', $like);
                                });
                        });
                });
            });
    }
}

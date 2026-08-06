<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\BookingStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ticketBooking;
use App\Services\TicketQrService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function __construct(
        protected TicketQrService $ticketQrService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ticketBooking::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $bookings = $this->bookingsQuery($organizerId, $filters)
            ->with(['user', 'event', 'ticketCategory', 'payment', 'refundRequest', 'checkedInBy'])
            ->latest('ticket_bookings.created_at')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = $this->bookingsQuery($organizerId, $filters);
        $checkedIn = (clone $statsQuery)->whereNotNull('checked_in_at')->count();
        $total = (clone $statsQuery)->count();
        $validEntry = (clone $statsQuery)->whereIn('status', BookingStatusEnum::retainedSaleStatuses());
        $stats = [
            'total' => $total,
            'confirmed' => (clone $validEntry)->count(),
            'checked_in' => $checkedIn,
            'awaiting_check_in' => (clone $validEntry)->whereNull('checked_in_at')->count(),
        ];

        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = BookingStatusEnum::cases();

        return view('organizer.bookings.index', compact('bookings', 'stats', 'events', 'statuses', 'filters'));
    }

    public function show(ticketBooking $ticketBooking): View
    {
        $this->authorize('view', $ticketBooking);

        $ticketBooking->load([
            'user',
            'event.host',
            'ticketCategory',
            'payment',
            'refundRequest.reviewer',
            'checkedInBy',
        ]);

        $relatedTickets = collect();
        if ($ticketBooking->payment_id) {
            $relatedTickets = ticketBooking::query()
                ->where('payment_id', $ticketBooking->payment_id)
                ->where('id', '!=', $ticketBooking->id)
                ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer((int) Auth::id()))
                ->with(['event', 'ticketCategory'])
                ->orderBy('id')
                ->get();
        }

        $qrSvg = $this->ticketQrService->getQrCodeSvg($ticketBooking->ticket_number);

        return view('organizer.bookings.show', compact('ticketBooking', 'relatedTickets', 'qrSvg'));
    }

    public function scanForm(Request $request): View
    {
        $this->authorize('viewAny', ticketBooking::class);

        $organizerId = (int) Auth::id();
        $events = Event::query()
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $eventId = $request->filled('event_id') ? (int) $request->input('event_id') : null;
        if ($eventId && ! $events->contains('id', $eventId)) {
            $eventId = null;
        }

        return view('organizer.bookings.scan', [
            'events' => $events,
            'eventId' => $eventId,
        ]);
    }

    public function scan(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', ticketBooking::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:500'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', Auth::id())),
            ],
        ]);

        $ticketNumber = $this->ticketQrService->extractTicketNumber($validated['code']);

        if (! $ticketNumber) {
            return back()
                ->withInput()
                ->with('error', 'Could not read a valid ticket number from that code.');
        }

        $organizerId = (int) Auth::id();
        $bookingQuery = ticketBooking::query()
            ->where('ticket_number', $ticketNumber)
            ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer($organizerId));

        if (! empty($validated['event_id'])) {
            $bookingQuery->where('event_id', $validated['event_id']);
        }

        $booking = $bookingQuery->first();

        if (! $booking) {
            return back()
                ->withInput()
                ->with('error', 'No matching ticket found for your events.');
        }

        $this->authorize('view', $booking);

        $error = $this->markCheckedIn($booking);
        if ($error !== null) {
            return redirect()
                ->route('organizer.bookings.show', $booking)
                ->with('error', $error);
        }

        return redirect()
            ->route('organizer.bookings.show', $booking)
            ->with('success', 'Guest checked in successfully.');
    }

    public function checkIn(ticketBooking $ticketBooking): RedirectResponse
    {
        $this->authorize('view', $ticketBooking);
        $this->authorize('checkIn', $ticketBooking);

        $error = $this->markCheckedIn($ticketBooking);
        if ($error !== null) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Guest checked in successfully.');
    }

    public function undoCheckIn(ticketBooking $ticketBooking): RedirectResponse
    {
        $this->authorize('undoCheckIn', $ticketBooking);

        $ticketBooking->forceFill([
            'checked_in_at' => null,
            'checked_in_by' => null,
        ])->save();

        return back()->with('success', 'Check-in has been undone.');
    }

    public function exportCsv(Request $request): Response
    {
        $this->authorize('viewAny', ticketBooking::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $bookings = $this->bookingsQuery($organizerId, $filters)
            ->with(['user', 'event', 'ticketCategory', 'payment', 'refundRequest', 'checkedInBy'])
            ->latest('ticket_bookings.created_at')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Ticket Number',
            'Guest Name',
            'Email',
            'Event',
            'Ticket Type',
            'Amount',
            'Booking Status',
            'Payment Reference',
            'Payment Method',
            'Payment Status',
            'Purchased At',
            'Refund Status',
            'Checked In',
            'Checked In At',
            'Checked In By',
        ];

        foreach ($bookings as $booking) {
            $csvData[] = [
                $booking->ticket_number,
                $booking->user?->full_name ?? 'Unknown',
                $booking->user?->email ?? '',
                $booking->event?->name ?? '',
                $booking->ticketCategory?->name ?? 'General',
                number_format((float) $booking->ticket_price, 2, '.', ''),
                $booking->displayStatusLabel(),
                $booking->payment?->reference ?? '',
                $booking->payment?->payment_method?->value ?? '',
                $booking->payment?->status?->value ?? '',
                $booking->created_at?->format('Y-m-d H:i') ?? '',
                $booking->refundRequest?->status?->value ?? '',
                $booking->isCheckedIn() ? 'Yes' : 'No',
                $booking->checked_in_at?->format('Y-m-d H:i') ?? '',
                $booking->checkedInBy?->full_name ?? '',
            ];
        }

        $filename = 'guest-list_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Atomically mark a ticket as checked in.
     *
     * @return string|null Error message when check-in did not apply; null on success
     */
    private function markCheckedIn(ticketBooking $booking): ?string
    {
        return DB::transaction(function () use ($booking) {
            $locked = ticketBooking::query()
                ->lockForUpdate()
                ->find($booking->id);

            if (! $locked) {
                return 'Ticket not found.';
            }

            if ($locked->isCheckedIn()) {
                $booking->refresh();

                return 'This ticket was already checked in'
                    .($locked->checked_in_at ? ' at '.$locked->checked_in_at->format('M d, Y H:i') : '')
                    .'.';
            }

            if (! $locked->canCheckIn()) {
                $booking->refresh();

                return $locked->checkInIneligibilityReason() ?? 'This ticket cannot be checked in.';
            }

            $locked->forceFill([
                'checked_in_at' => now(),
                'checked_in_by' => Auth::id(),
            ])->save();

            $booking->refresh();

            return null;
        });
    }

    /**
     * @return array{search?: string|null, event_id?: int|null, status?: string|null, check_in?: string|null, from_date?: string|null, to_date?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'search' => $request->filled('search') ? $request->input('search') : null,
            'event_id' => $request->filled('event_id') ? $request->input('event_id') : null,
            'status' => $request->filled('status') ? $request->input('status') : null,
            'check_in' => $request->filled('check_in') ? $request->input('check_in') : null,
            'from_date' => $request->filled('from_date') ? $request->input('from_date') : null,
            'to_date' => $request->filled('to_date') ? $request->input('to_date') : null,
        ]);

        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', Auth::id())),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_column(BookingStatusEnum::cases(), 'value')),
            ],
            'check_in' => ['nullable', 'string', Rule::in(['checked_in', 'not_checked_in'])],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }

    /**
     * @param  array{search?: string|null, event_id?: int|null, status?: string|null, check_in?: string|null, from_date?: string|null, to_date?: string|null}  $filters
     */
    private function bookingsQuery(int $organizerId, array $filters): Builder
    {
        $query = ticketBooking::query()
            ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer($organizerId));

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['check_in'] ?? null) === 'checked_in') {
            $query->whereNotNull('checked_in_at');
        } elseif (($filters['check_in'] ?? null) === 'not_checked_in') {
            $query->whereNull('checked_in_at');
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('ticket_bookings.created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('ticket_bookings.created_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event', function (Builder $eventQuery) use ($search) {
                        $eventQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}

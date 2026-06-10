<?php

namespace App\Http\Controllers;

use App\Models\ticketBooking;
use App\Services\RefundPolicyService;
use App\Services\RefundRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class RefundRequestController extends Controller
{
    public function __construct(
        protected RefundPolicyService $refundPolicyService,
        protected RefundRequestService $refundRequestService,
    ) {}

    public function create(ticketBooking $ticketBooking): View|RedirectResponse
    {
        $this->authorizeBooking($ticketBooking);

        if ($ticketBooking->isExpired()) {
            return redirect()
                ->route('attendee.bookings.index')
                ->withErrors(['refund' => 'This ticket has expired. Cancellation is not available after the event date.']);
        }

        if (! $ticketBooking->isCancellable()) {
            return redirect()
                ->route('attendee.bookings.index')
                ->withErrors(['refund' => 'This ticket cannot be cancelled or already has a refund request.']);
        }

        $ticketBooking->load(['event.host', 'ticketCategory', 'payment']);
        $policy = $this->refundPolicyService->evaluate($ticketBooking);

        return view('attendee.bookings.refund-request', compact('ticketBooking', 'policy'));
    }

    public function store(Request $request, ticketBooking $ticketBooking): RedirectResponse
    {
        $this->authorizeBooking($ticketBooking);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $refundRequest = $this->refundRequestService->submit($ticketBooking, $validated['reason']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['reason' => $e->getMessage()])->withInput();
        }

        $message = $refundRequest->status->value === 'auto_declined'
            ? 'Your refund request was automatically declined because the event has already passed.'
            : 'Your refund request has been submitted. A confirmation email has been sent.';

        return redirect()
            ->route('attendee.bookings.index')
            ->with('success', $message);
    }

    private function authorizeBooking(ticketBooking $ticketBooking): void
    {
        if ((int) $ticketBooking->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}

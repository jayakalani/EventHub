<?php

namespace App\Http\Controllers;

use App\Services\PostponementAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ticketBooking;

class PostponementAlertController extends Controller
{
    public function __construct(
        protected PostponementAlertService $postponementAlertService,
    ) {}

    public function dismiss(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_ids' => ['required', 'array', 'min:1'],
            'event_ids.*' => ['integer', 'exists:events,id'],
        ]);

        $this->postponementAlertService->dismiss(Auth::user(), $validated['event_ids']);

        return back()->with('success', 'Postponement notice dismissed.');
    }

    public function keepTicket(Request $request, ticketBooking $ticketBooking): RedirectResponse
    {
        if ((int) $ticketBooking->user_id !== (int) Auth::id()) {
            abort(403);
        }

        try {
            $this->postponementAlertService->keepTicket(Auth::user(), $ticketBooking);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return back()->with('success', 'Ticket kept. It remains valid for the postponed event.');
    }
}

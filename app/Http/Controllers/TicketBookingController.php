<?php

namespace App\Http\Controllers;

use App\Models\ticketBooking;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketBookingController extends Controller
{
    /**
     * List confirmed ticket bookings for the attendee.
     */
    public function index(): View
    {
        $bookings = ticketBooking::query()
            ->where('user_id', Auth::id())
            ->with(['event.host', 'event.eventCategory', 'ticketCategory'])
            ->latest()
            ->get()
            ->groupBy('event_id');

        $bookingCount = ticketBooking::query()
            ->where('user_id', Auth::id())
            ->sum('quantity');

        return view('attendee.bookings.index', compact('bookings', 'bookingCount'));
    }
}

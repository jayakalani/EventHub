<?php

namespace App\Http\Controllers;

use App\Models\ticketBooking;
use App\Services\PostponementAlertService;
use App\Services\TicketPdfService;
use App\Services\TicketQrService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TicketBookingController extends Controller
{
    public function __construct(
        protected TicketQrService $ticketQrService,
        protected TicketPdfService $ticketPdfService,
        protected PostponementAlertService $postponementAlertService,
    ) {}

    /**
     * List confirmed tickets grouped by event.
     */
    public function index(): View
    {
        $bookings = ticketBooking::query()
            ->where('user_id', Auth::id())
            ->with(['event.host', 'event.eventCategory', 'ticketCategory', 'payment', 'refundRequest'])
            ->latest()
            ->get()
            ->each(fn (ticketBooking $booking) => $booking->setAttribute(
                'qr_code_svg',
                $this->ticketQrService->getQrCodeSvg($booking->ticket_number)
            ))
            ->groupBy('event_id');

        $bookingCount = ticketBooking::query()
            ->where('user_id', Auth::id())
            ->count();

        $hasUnresolvedPostponement = $this->postponementAlertService
            ->undismissedAlertsFor(Auth::user())
            ->isNotEmpty();

        return view('attendee.bookings.index', compact('bookings', 'bookingCount', 'hasUnresolvedPostponement'));
    }

    /**
     * Download a single ticket as PDF.
     */
    public function download(ticketBooking $ticketBooking): Response
    {
        $this->authorizeBooking($ticketBooking);

        return $this->ticketPdfService->downloadResponse($ticketBooking);
    }

    private function authorizeBooking(ticketBooking $ticketBooking): void
    {
        if ((int) $ticketBooking->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}

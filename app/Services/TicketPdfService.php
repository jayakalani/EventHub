<?php

namespace App\Services;

use App\Models\ticketBooking;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class TicketPdfService
{
    public function __construct(
        protected TicketQrService $ticketQrService
    ) {}

    public function generate(ticketBooking $booking): string
    {
        $booking->loadMissing(['event.host', 'event.contactPerson', 'ticketCategory', 'payment', 'user']);

        $qrCode = $this->ticketQrService->getQrCodeForPdf($booking->ticket_number);

        return Pdf::loadView('attendee.bookings.ticket_pdf', [
            'booking' => $booking,
            'qrCode' => $qrCode,
        ])->setPaper('a4', 'landscape')->output();
    }

    public function downloadResponse(ticketBooking $booking): Response
    {
        $filename = $booking->ticket_number.'.pdf';

        return response($this->generate($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

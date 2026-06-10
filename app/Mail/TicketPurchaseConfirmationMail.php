<?php

namespace App\Mail;

use App\Models\Payment;
use App\Services\TicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketPurchaseConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' tickets — '.$this->payment->reference,
        );
    }

    public function content(): Content
    {
        $this->payment->load([
            'user',
            'ticketBookings.event',
            'ticketBookings.ticketCategory',
        ]);

        return new Content(
            view: 'mail.ticket-purchase-confirmation',
            with: [
                'payment' => $this->payment,
                'groupedBookings' => $this->payment->ticketBookings->groupBy('event_id'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $this->payment->load([
            'ticketBookings.event.host',
            'ticketBookings.ticketCategory',
            'ticketBookings.payment',
            'ticketBookings.user',
        ]);

        $pdfService = app(TicketPdfService::class);

        return $this->payment->ticketBookings
            ->map(fn ($booking) => Attachment::fromData(
                fn () => $pdfService->generate($booking),
                $booking->ticket_number.'.pdf'
            )->withMime('application/pdf'))
            ->all();
    }
}

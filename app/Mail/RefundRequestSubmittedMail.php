<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund request received — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->refundRequest->load(['user', 'ticketBooking.event', 'ticketBooking.ticketCategory']);

        return new Content(
            view: 'mail.refund-request-submitted',
            with: ['refundRequest' => $this->refundRequest],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestDeclinedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund request update — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->refundRequest->load(['user', 'ticketBooking.event', 'ticketBooking.ticketCategory', 'reviewer']);

        return new Content(
            view: 'mail.refund-request-declined',
            with: ['refundRequest' => $this->refundRequest],
        );
    }
}

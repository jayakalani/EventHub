<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundRequestApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $refundRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund approved — credited to your wallet',
        );
    }

    public function content(): Content
    {
        $this->refundRequest->load(['user.wallet', 'ticketBooking.event', 'ticketBooking.ticketCategory']);

        return new Content(
            view: 'mail.refund-request-approved',
            with: ['refundRequest' => $this->refundRequest],
        );
    }
}

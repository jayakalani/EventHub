<?php

namespace App\Mail;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketExpiryReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CartItem $cartItem,
        public User $user,
        public int $minutesRemaining,
    ) {}

    public function envelope(): Envelope
    {
        $this->cartItem->loadMissing('event');

        return new Envelope(
            subject: 'Complete payment: your "'.$this->cartItem->event->name.'" reservation expires soon',
        );
    }

    public function content(): Content
    {
        $this->cartItem->loadMissing(['event.host', 'ticketCategory']);

        return new Content(view: 'mail.ticket-expiry-reminder');
    }
}

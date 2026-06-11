<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\ticketBooking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, ticketBooking>|iterable<int, ticketBooking>  $bookings
     */
    public function __construct(
        public Event $event,
        public User $user,
        public string $cancellationReason,
        public iterable $bookings,
        public float $refundTotal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event cancelled — '.$this->event->name,
        );
    }

    public function content(): Content
    {
        $this->user->loadMissing('wallet');
        $this->event->loadMissing('host');

        return new Content(
            view: 'mail.event-cancelled',
        );
    }
}

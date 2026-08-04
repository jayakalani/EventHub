<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventPostponedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
        public string $postponementReason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Postponed',
        );
    }

    public function content(): Content
    {
        $this->event->loadMissing('host');

        return new Content(
            view: 'mail.event-postponed',
        );
    }
}

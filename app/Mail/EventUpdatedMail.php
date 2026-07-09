<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        public Event $event,
        public User $user,
        public array $changes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event updated — '.$this->event->name,
        );
    }

    public function content(): Content
    {
        $this->event->loadMissing('host');

        return new Content(view: 'mail.event-updated');
    }
}

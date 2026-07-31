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

class NewEventFromHostMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        $this->event->loadMissing('host');
        $hostName = $this->event->host?->name ?? 'a host you follow';

        return new Envelope(
            subject: 'New event from '.$hostName.' — '.$this->event->name,
        );
    }

    public function content(): Content
    {
        $this->event->loadMissing('host');

        return new Content(view: 'mail.new-event-from-host');
    }
}

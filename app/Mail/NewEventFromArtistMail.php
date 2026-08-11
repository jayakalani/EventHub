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

class NewEventFromArtistMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        $this->event->loadMissing('artists');
        $artistName = $this->event->artists->pluck('name')->filter()->implode(', ');
        $artistName = $artistName !== '' ? $artistName : 'an artist you follow';

        return new Envelope(
            subject: 'New event from '.$artistName.' — '.$this->event->name,
        );
    }

    public function content(): Content
    {
        $this->event->loadMissing('artists');

        return new Content(view: 'mail.new-event-from-artist');
    }
}

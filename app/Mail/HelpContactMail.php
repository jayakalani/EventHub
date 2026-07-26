<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HelpContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $comment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('eventhubhelp@gmail.com', config('app.name').' Help')],
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: 'Help / Contact form — '.$this->senderName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.help-contact',
        );
    }
}

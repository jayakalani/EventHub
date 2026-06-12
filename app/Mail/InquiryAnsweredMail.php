<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryAnsweredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your inquiry has been answered — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->inquiry->load(['user', 'event']);

        return new Content(
            view: 'mail.inquiry-answered',
            with: ['inquiry' => $this->inquiry],
        );
    }
}

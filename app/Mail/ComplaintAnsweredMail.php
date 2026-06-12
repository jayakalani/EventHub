<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplaintAnsweredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your complaint has been answered — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->complaint->load(['user']);

        return new Content(
            view: 'mail.complaint-answered',
            with: ['complaint' => $this->complaint],
        );
    }
}

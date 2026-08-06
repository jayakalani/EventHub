<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizerWeeklyDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     weekLabel: string,
     *     from: string,
     *     to: string,
     *     netRevenue: float,
     *     ticketsSold: int,
     *     attendanceRate: float|null,
     *     checkedIn: int,
     *     topEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     bottomEvent: array{name: string, revenue: float, tickets_sold: int}|null,
     *     reportsUrl: string
     * }  $digest
     */
    public function __construct(
        public User $user,
        public array $digest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your EventHub weekly digest · '.$this->digest['weekLabel'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.organizer-weekly-digest');
    }
}

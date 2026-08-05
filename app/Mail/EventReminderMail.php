<?php

namespace App\Mail;

use App\Enums\EventReminderTypeEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
        public EventReminderTypeEnum $reminderType,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->reminderType) {
            EventReminderTypeEnum::SevenDays => 'Reminder: '.$this->event->name.' starts in 7 days',
            EventReminderTypeEnum::OneDay => 'Reminder: '.$this->event->name.' is tomorrow',
            EventReminderTypeEnum::ThreeHours, EventReminderTypeEnum::TwoHours => 'Reminder: '.$this->event->name.' starts in 3 hours — gates opening soon',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $this->event->loadMissing('host');

        return new Content(view: 'mail.event-reminder');
    }
}

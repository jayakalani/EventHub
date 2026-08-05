<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\ticketBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ticketBooking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->loadMissing('event');

        return [
            'category' => AttendeeNotificationCategory::Ticket->value,
            'type' => 'ticket_cancelled',
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->event_id,
            'event_name' => $this->booking->event?->name,
            'message' => 'Your ticket for "'.($this->booking->event?->name ?? 'an event').'" was cancelled.',
            'url' => route('attendee.bookings.index'),
        ];
    }
}

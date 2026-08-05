<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->payment->loadMissing(['ticketBookings.event']);
        $eventName = $this->payment->ticketBookings->first()?->event?->name;

        return [
            'category' => AttendeeNotificationCategory::Ticket->value,
            'type' => 'ticket_purchased',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'message' => $eventName
                ? 'Ticket purchased successfully for "'.$eventName.'".'
                : 'Ticket purchased successfully.',
            'url' => route('attendee.bookings.index'),
        ];
    }
}

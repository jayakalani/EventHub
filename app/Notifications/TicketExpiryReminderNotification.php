<?php

namespace App\Notifications;

use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CartItem $cartItem,
        public int $minutesRemaining,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->cartItem->loadMissing(['event', 'ticketCategory']);

        $event = $this->cartItem->event;

        return [
            'category' => \App\Enums\AttendeeNotificationCategory::Payment->value,
            'type' => 'ticket_expiry',
            'event_id' => $event->id,
            'event_name' => $event->name,
            'ticket_category' => $this->cartItem->ticketCategory?->name,
            'quantity' => $this->cartItem->quantity,
            'minutes_remaining' => $this->minutesRemaining,
            'expires_at' => $this->cartItem->expiresAt()->toIso8601String(),
            'message' => 'Your reservation for "'.$event->name.'" expires in '.$this->minutesRemaining.' minute(s). Complete payment to secure your tickets.',
            'url' => route('attendee.cart.index'),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CartItem $cartItem) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->cartItem->loadMissing('event');
        $eventName = $this->cartItem->event?->name ?? 'an event';

        return [
            'category' => AttendeeNotificationCategory::Payment->value,
            'type' => 'payment_pending',
            'cart_item_id' => $this->cartItem->id,
            'event_id' => $this->cartItem->event_id,
            'event_name' => $this->cartItem->event?->name,
            'message' => 'Payment pending: tickets for "'.$eventName.'" have been in your cart for over 5 days.',
            'url' => route('attendee.cart.index'),
        ];
    }
}

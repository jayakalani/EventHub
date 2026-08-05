<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
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
        return [
            'category' => AttendeeNotificationCategory::Payment->value,
            'type' => 'payment_failed',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'message' => 'Payment failed for '.$this->payment->reference.'. Please try again.',
            'url' => route('attendee.cart.index'),
        ];
    }
}

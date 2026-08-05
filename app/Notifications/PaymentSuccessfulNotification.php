<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentSuccessfulNotification extends Notification implements ShouldQueue
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
            'type' => 'payment_successful',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'amount' => (float) $this->payment->amount,
            'message' => 'Payment successful for '.$this->payment->reference.'.',
            'url' => route('attendee.bookings.index'),
        ];
    }
}

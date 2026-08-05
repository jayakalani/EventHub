<?php

namespace App\Notifications;

use App\Enums\AttendeeNotificationCategory;
use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RefundCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RefundRequest $refundRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->refundRequest->loadMissing('ticketBooking.event');
        $eventName = $this->refundRequest->ticketBooking?->event?->name ?? 'an event';

        return [
            'category' => AttendeeNotificationCategory::Payment->value,
            'type' => 'refund_completed',
            'refund_request_id' => $this->refundRequest->id,
            'amount' => (float) $this->refundRequest->refund_amount,
            'message' => 'Refund completed for "'.$eventName.'". LKR '.number_format((float) $this->refundRequest->refund_amount, 2).' was credited to your wallet.',
            'url' => route('attendee.wallet.index'),
        ];
    }
}

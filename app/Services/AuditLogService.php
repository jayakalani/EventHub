<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): void {
        AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }

    public function logEventPostponed(Event $event, array $oldValues, array $newValues): void
    {
        $this->log('Event postponed', $event, $oldValues, $newValues);
    }

    public function logEventRescheduled(Event $event, array $oldValues, array $newValues): void
    {
        $this->log('Event rescheduled', $event, $oldValues, $newValues);
    }

    public function logPostponementRefund(ticketBooking $booking, RefundRequest $refundRequest): void
    {
        $booking->loadMissing('event');

        $this->log(
            'Refund because of postponement',
            $booking,
            null,
            [
                'event_id' => $booking->event_id,
                'event_name' => $booking->event?->name,
                'ticket_number' => $booking->ticket_number,
                'refund_request_id' => $refundRequest->id,
                'refund_amount' => $refundRequest->refund_amount,
                'user_id' => $booking->user_id,
            ],
        );
    }
}

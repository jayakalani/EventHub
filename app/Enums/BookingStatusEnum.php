<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    case Confirmed = 'confirmed';
    case BookingCancelled = 'booking_cancelled';
    case EventCancelled = 'event_cancelled';
    case Refunded = 'refunded';
    case RefundDeclined = 'refund_declined';
}

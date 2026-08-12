<?php

namespace App\Enums;

enum BookingStatusEnum: string
{
    case Confirmed = 'confirmed';
    case BookingCancelled = 'booking_cancelled';
    case EventCancelled = 'event_cancelled';
    case Refunded = 'refunded';
    case RefundDeclined = 'refund_declined';

    /**
     * Statuses that keep payment and remain valid for entry / sales totals.
     *
     * @return list<self>
     */
    public static function retainedSaleStatuses(): array
    {
        return [
            self::Confirmed,
            self::RefundDeclined,
        ];
    }

    /**
     * Booking statuses that can appear on the Sales list.
     * Partial refunds (status = refunded) are included separately by amount rules.
     *
     * @return list<self>
     */
    public static function salesListStatuses(): array
    {
        return [
            self::Confirmed,
            self::RefundDeclined,
            self::Refunded,
        ];
    }
}

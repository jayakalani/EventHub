<?php

namespace App\Enums;

enum CartReminderTypeEnum: string
{
    case ExpiryWarning = 'expiry_warning';
    case PendingFiveDays = 'pending_five_days';

    public function label(): string
    {
        return match ($this) {
            self::ExpiryWarning => 'Reservation expiring soon',
            self::PendingFiveDays => 'Payment pending (cart over 5 days)',
        };
    }
}

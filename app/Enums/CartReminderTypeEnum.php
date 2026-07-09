<?php

namespace App\Enums;

enum CartReminderTypeEnum: string
{
    case ExpiryWarning = 'expiry_warning';

    public function label(): string
    {
        return match ($this) {
            self::ExpiryWarning => 'Reservation expiring soon',
        };
    }
}

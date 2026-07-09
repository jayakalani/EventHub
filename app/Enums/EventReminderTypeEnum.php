<?php

namespace App\Enums;

enum EventReminderTypeEnum: string
{
    case OneDay = 'one_day';
    case TwoHours = 'two_hours';

    public function label(): string
    {
        return match ($this) {
            self::OneDay => '1 day before',
            self::TwoHours => '2 hours before',
        };
    }
}

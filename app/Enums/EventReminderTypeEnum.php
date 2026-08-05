<?php

namespace App\Enums;

enum EventReminderTypeEnum: string
{
    case SevenDays = 'seven_days';
    case OneDay = 'one_day';
    case ThreeHours = 'three_hours';
    case RatingNudge = 'rating_nudge';
    /** @deprecated Kept for existing reminder log rows */
    case TwoHours = 'two_hours';

    public function label(): string
    {
        return match ($this) {
            self::SevenDays => '7 days before',
            self::OneDay => '1 day before',
            self::ThreeHours, self::TwoHours => '3 hours before',
            self::RatingNudge => '24 hours after',
        };
    }

    public function message(string $eventName): string
    {
        return match ($this) {
            self::SevenDays => '"'.$eventName.'" starts in 7 days. Get ready!',
            self::OneDay => '"'.$eventName.'" starts tomorrow. Don\'t forget your tickets!',
            self::ThreeHours, self::TwoHours => '"'.$eventName.'" starts in 3 hours. Gates opening soon!',
            self::RatingNudge => 'How was "'.$eventName.'"? Rate it and leave a comment to help others.',
        };
    }
}

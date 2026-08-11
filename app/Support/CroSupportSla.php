<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class CroSupportSla
{
    /** @var list<string> */
    public const URGENT_TERMS = [
        'payment',
        'refund',
        'duplicate',
        'cancel',
        'urgent',
        'failed',
        'fraud',
    ];

    public const AGING_HOURS = 24;

    public const OVERDUE_HOURS = 48;

    public static function isUrgentSubject(?string $subject): bool
    {
        $subject = strtolower((string) $subject);

        foreach (self::URGENT_TERMS as $term) {
            if (str_contains($subject, $term)) {
                return true;
            }
        }

        return false;
    }

    public static function ageHours(?Carbon $createdAt): int
    {
        if (! $createdAt) {
            return 0;
        }

        return (int) max(0, $createdAt->diffInHours(now()));
    }

    public static function ageLabel(?Carbon $createdAt): string
    {
        if (! $createdAt) {
            return '—';
        }

        $minutes = (int) max(0, $createdAt->diffInMinutes(now()));

        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 48) {
            return $hours.'h';
        }

        return intdiv($hours, 24).'d';
    }

    /**
     * @return 'ok'|'aging'|'overdue'|'urgent'
     */
    public static function level(?Carbon $createdAt, ?string $subject, bool $isOpen = true): string
    {
        if (! $isOpen) {
            return 'ok';
        }

        if (self::isUrgentSubject($subject)) {
            return 'urgent';
        }

        $hours = self::ageHours($createdAt);

        if ($hours >= self::OVERDUE_HOURS) {
            return 'overdue';
        }

        if ($hours >= self::AGING_HOURS) {
            return 'aging';
        }

        return 'ok';
    }

    public static function levelLabel(string $level): string
    {
        return match ($level) {
            'urgent' => 'Urgent',
            'overdue' => 'Overdue',
            'aging' => 'Aging',
            default => 'On track',
        };
    }

    public static function levelClass(string $level): string
    {
        return match ($level) {
            'urgent' => 'bg-rose-100 text-rose-700',
            'overdue' => 'bg-orange-100 text-orange-800',
            'aging' => 'bg-amber-100 text-amber-800',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public static function ageClass(string $level): string
    {
        return match ($level) {
            'urgent', 'overdue' => 'text-rose-700 font-semibold',
            'aging' => 'text-amber-700 font-semibold',
            default => 'text-slate-500',
        };
    }
}

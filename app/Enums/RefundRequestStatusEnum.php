<?php

namespace App\Enums;

enum RefundRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case AutoDeclined = 'auto_declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::AutoDeclined => 'Auto declined',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-700',
            self::Approved => 'bg-emerald-100 text-emerald-700',
            self::Declined => 'bg-rose-100 text-rose-700',
            self::AutoDeclined => 'bg-slate-100 text-slate-600',
        };
    }

    public function isProcessed(): bool
    {
        return $this !== self::Pending;
    }
}

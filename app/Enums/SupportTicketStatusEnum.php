<?php

namespace App\Enums;

enum SupportTicketStatusEnum: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Open => 'Waiting for CRO',
            self::InProgress => 'CRO is working on it',
            self::Resolved => 'CRO has answered',
            self::Closed => 'Issue finalized',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-100 text-amber-700',
            self::InProgress => 'bg-blue-100 text-blue-700',
            self::Resolved => 'bg-emerald-100 text-emerald-700',
            self::Closed => 'bg-slate-100 text-slate-600',
        };
    }
}

<?php

namespace App\Enums;

enum OrganizerNotificationCategory: string
{
    case Ticket = 'ticket';
    case Reminder = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Ticket Notifications',
            self::Reminder => 'Reminder Notifications',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ticket => 'bi-ticket-perforated',
            self::Reminder => 'bi-bell',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::Ticket => 'indigo',
            self::Reminder => 'amber',
        };
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->typeLabels());
    }

    /**
     * @return array<string, string>
     */
    public function typeLabels(): array
    {
        return match ($this) {
            self::Ticket => [
                'ticket_category_sold_out' => 'Ticket category sold out',
                'low_ticket_inventory' => 'Low ticket inventory',
            ],
            self::Reminder => [
                'event_starts_tomorrow' => 'Event starts tomorrow',
                'event_starts_in_one_hour' => 'Event starts in 1 hour',
                'ticket_sales_closing_soon' => 'Ticket sales closing soon',
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function allTypeLabels(): array
    {
        $labels = [];

        foreach (self::cases() as $category) {
            $labels += $category->typeLabels();
        }

        return $labels;
    }

    public static function labelForType(?string $type): string
    {
        return self::allTypeLabels()[$type]
            ?? ucfirst(str_replace('_', ' ', (string) $type ?: 'Update'));
    }

    public static function fromType(?string $type): self
    {
        foreach (self::cases() as $category) {
            if (in_array($type, $category->types(), true)) {
                return $category;
            }
        }

        return self::Ticket;
    }
}

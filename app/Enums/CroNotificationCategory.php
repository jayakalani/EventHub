<?php

namespace App\Enums;

enum CroNotificationCategory: string
{
    case Assignment = 'assignment';
    case Interaction = 'interaction';
    case Refund = 'refund';
    case Event = 'event';
    case Reminder = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::Assignment => 'Assignment Notifications',
            self::Interaction => 'Interaction Notifications',
            self::Refund => 'Refund Notifications',
            self::Event => 'Event Notifications',
            self::Reminder => 'Reminder Notifications',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Assignment => 'bi-person-check',
            self::Interaction => 'bi-chat-dots',
            self::Refund => 'bi-cash-coin',
            self::Event => 'bi-calendar-event',
            self::Reminder => 'bi-bell',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::Assignment => 'sky',
            self::Interaction => 'rose',
            self::Refund => 'teal',
            self::Event => 'violet',
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
            self::Assignment => [
                'event_assigned' => 'Event assigned',
            ],
            self::Interaction => [
                'inquiry_submitted' => 'New inquiry',
                'complaint_submitted' => 'New complaint',
            ],
            self::Refund => [
                'refund_request_submitted' => 'Refund request submitted',
            ],
            self::Event => [
                'event_postponed' => 'Event postponed',
                'event_rescheduled' => 'Event rescheduled',
                'event_cancelled' => 'Event cancelled',
            ],
            self::Reminder => [
                'event_starts_tomorrow' => 'Event starts tomorrow',
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

        return self::Event;
    }
}

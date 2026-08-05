<?php

namespace App\Enums;

enum AdminNotificationCategory: string
{
    case Audit = 'audit';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Audit => 'Audit Notifications',
            self::Security => 'Security Notifications',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Audit => 'bi-journal-text',
            self::Security => 'bi-shield-lock',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::Audit => 'slate',
            self::Security => 'rose',
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
            self::Audit => [
                'category_deleted' => 'Category deleted',
                'payment_settings_changed' => 'Payment settings changed',
            ],
            self::Security => [
                'account_locked' => 'Account locked',
                'organizer_category_deleted' => 'Category deleted by organizer',
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

        return self::Audit;
    }
}

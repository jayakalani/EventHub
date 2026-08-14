<?php

namespace App\Enums;

enum AttendeeNotificationCategory: string
{
    case Ticket = 'ticket';
    case Payment = 'payment';
    case Event = 'event';
    case Reminder = 'reminder';
    case Refund = 'refund';
    case Interaction = 'interaction';
    case Wishlist = 'wishlist';
    case Account = 'account';

    public function label(): string
    {
        return match ($this) {
            self::Ticket => 'Ticket Notifications',
            self::Payment => 'Payment Notifications',
            self::Event => 'Event Notifications',
            self::Reminder => 'Reminder Notifications',
            self::Refund => 'Refund Notifications',
            self::Interaction => 'Interaction Notifications',
            self::Wishlist => 'Wishlist / Saved Events',
            self::Account => 'Account Notifications',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ticket => 'bi-ticket-perforated',
            self::Payment => 'bi-credit-card',
            self::Event => 'bi-calendar-event',
            self::Reminder => 'bi-bell',
            self::Refund => 'bi-cash-coin',
            self::Interaction => 'bi-heart',
            self::Wishlist => 'bi-bookmark-heart',
            self::Account => 'bi-person-circle',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::Ticket => 'indigo',
            self::Payment => 'emerald',
            self::Event => 'violet',
            self::Reminder => 'amber',
            self::Refund => 'teal',
            self::Interaction => 'rose',
            self::Wishlist => 'pink',
            self::Account => 'blue',
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
     * Notification types belonging to this category, mapped to readable labels.
     *
     * @return array<string, string>
     */
    public function typeLabels(): array
    {
        return match ($this) {
            self::Ticket => [
                'ticket_purchased' => 'Ticket purchased',
                'ticket_cancelled' => 'Ticket cancelled',
                'ticket_refunded' => 'Ticket refunded',
            ],
            self::Payment => [
                'payment_successful' => 'Payment successful',
                'payment_failed' => 'Payment failed',
                'payment_pending' => 'Payment pending',
                'ticket_expiry' => 'Reservation expiring',
            ],
            self::Event => [
                'new_event' => 'New event published',
                'event_published' => 'Event published',
                'event_updated' => 'Event updated',
                'event_postponed' => 'Event postponed',
                'event_rescheduled' => 'Event rescheduled',
                'event_schedule_announced' => 'Date announced',
                'event_cancelled' => 'Event cancelled',
                'event_completed' => 'Event completed',
            ],
            self::Reminder => [
                'event_reminder' => 'Event reminder',
                'event_rating_nudge' => 'Rate past event',
            ],
            self::Refund => [
                'refund_request_received' => 'Refund received',
                'refund_approved' => 'Refund approved',
                'refund_rejected' => 'Refund rejected',
                'refund_completed' => 'Refund completed',
            ],
            self::Interaction => [
                'inquiry_replied' => 'Inquiry reply',
                'inquiry_resolved' => 'Inquiry resolved',
                'complaint_replied' => 'Complaint reply',
                'complaint_resolved' => 'Complaint resolved',
            ],
            self::Wishlist => [
                'saved_event_published' => 'Saved event published',
                'ticket_sales_opened' => 'Ticket sales opened',
            ],
            self::Account => [
                'email_verified' => 'Email verified',
                'password_changed' => 'Password changed',
                'profile_updated' => 'Profile updated',
            ],
        };
    }

    /**
     * Every notification type across all categories, mapped to readable labels.
     *
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

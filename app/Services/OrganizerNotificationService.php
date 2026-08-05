<?php

namespace App\Services;

use App\Enums\OrganizerNotificationCategory;
use App\Models\Event;
use App\Models\ticketCategory;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\OrganizerActivityNotification;

class OrganizerNotificationService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(
        ?User $user,
        OrganizerNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        if (! $user || ! $this->isOrganizer($user)) {
            return;
        }

        $user->notify(new OrganizerActivityNotification(
            $category,
            $type,
            $message,
            $url,
            $metadata,
        ));
    }

    /**
     * Notify the organizer who owns the event (created_by).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function notifyEventOrganizer(
        Event $event,
        OrganizerNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        $event->loadMissing('organizer.userRole');

        $this->send(
            $event->organizer,
            $category,
            $type,
            $message,
            $url,
            array_merge(['event_id' => $event->id], $metadata),
        );
    }

    public function notifyTicketCategorySoldOut(ticketCategory $category): void
    {
        $category->loadMissing('event.organizer.userRole');

        $event = $category->event;

        if (! $event || (int) $category->no_of_available_tickets > 0) {
            return;
        }

        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->unreadNotifications()
            ->where('data->type', 'ticket_category_sold_out')
            ->where('data->ticket_category_id', $category->id)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Ticket,
            'ticket_category_sold_out',
            'Ticket category "'.$category->name.'" for "'.$event->name.'" is sold out.',
            route('organizer.events.show', $event),
            [
                'event_id' => $event->id,
                'ticket_category_id' => $category->id,
                'ticket_category_name' => $category->name,
            ],
        );
    }

    public function notifyEventStartsTomorrow(Event $event): void
    {
        $event->loadMissing('organizer.userRole');
        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'event_starts_tomorrow')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'event_starts_tomorrow',
            'Reminder: your event "'.$event->name.'" starts tomorrow.',
            route('organizer.events.show', $event),
            ['event_id' => $event->id],
        );
    }

    public function notifyEventStartsInOneHour(Event $event): void
    {
        $event->loadMissing('organizer.userRole');
        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'event_starts_in_one_hour')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'event_starts_in_one_hour',
            'Reminder: your event "'.$event->name.'" starts in 1 hour.',
            route('organizer.events.show', $event),
            ['event_id' => $event->id],
        );
    }

    public function notifyTicketSalesClosingSoon(ticketCategory $category): void
    {
        $category->loadMissing('event.organizer.userRole');

        $event = $category->event;

        if (! $event || ! $category->booking_end) {
            return;
        }

        if (! in_array($event->status, [Event::STATUS_UPCOMING, Event::STATUS_ONGOING], true)) {
            return;
        }

        $organizer = $event->organizer;

        if (! $organizer || ! $this->isOrganizer($organizer)) {
            return;
        }

        $alreadySent = $organizer->notifications()
            ->where('data->type', 'ticket_sales_closing_soon')
            ->where('data->ticket_category_id', $category->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $closesAt = $category->booking_end->timezone(config('app.timezone'))->format('M j, g:i A');

        $this->send(
            $organizer,
            OrganizerNotificationCategory::Reminder,
            'ticket_sales_closing_soon',
            'Ticket sales for "'.$category->name.'" on "'.$event->name.'" close soon ('.$closesAt.').',
            route('organizer.events.show', $event),
            [
                'event_id' => $event->id,
                'ticket_category_id' => $category->id,
                'ticket_category_name' => $category->name,
                'booking_end' => $category->booking_end->toIso8601String(),
            ],
        );
    }

    public function isOrganizer(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::ORGANIZER;
    }
}

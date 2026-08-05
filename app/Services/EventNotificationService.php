<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\AttendeeNotificationCategory;
use App\Enums\EventReminderTypeEnum;
use App\Mail\EventReminderMail;
use App\Mail\EventUpdatedMail;
use App\Mail\NewEventFromHostMail;
use App\Models\Event;
use App\Models\EventReminderLog;
use App\Models\FollowHost;
use App\Models\SavedEvent;
use App\Models\ticketBooking;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\NewEventFromHostNotification;
use App\Notifications\EventUpdatedNotification;
use App\Notifications\EventReminderNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EventNotificationService
{
    /**
     * @var list<string>
     */
    public const UPDATABLE_FIELDS = [
        'name',
        'date',
        'time',
        'place',
        'description',
    ];

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function notifyEventUpdated(Event $event, array $changes): void
    {
        if ($event->isCancelled() || $event->isCompleted() || $event->isPostponed() || ! $event->isVisibleToAttendees()) {
            return;
        }

        $recipients = $this->mergeRecipients(
            $this->getUsersWhoSavedEvent($event),
            $this->getHostFollowers((int) $event->hosted_by),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        DB::afterCommit(function () use ($event, $changes, $recipients) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new EventUpdatedMail($event, $user, $changes));
                $user->notify(new EventUpdatedNotification($event, $changes));
            }
        });
    }

    public function notifyNewEventPublished(Event $event): void
    {
        if ($event->isCancelled() || ! $event->isVisibleToAttendees()) {
            return;
        }

        $event->loadMissing('host');
        $recipients = $this->mergeRecipients(
            $this->getHostFollowers((int) $event->hosted_by),
            $this->getUsersWhoSavedEvent($event),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        DB::afterCommit(function () use ($event, $recipients) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new NewEventFromHostMail($event, $user));
                $user->notify(new NewEventFromHostNotification($event));
            }

            $savedUsers = $this->getUsersWhoSavedEvent($event);

            foreach ($savedUsers as $user) {
                app(AttendeeNotificationService::class)->send(
                    $user,
                    AttendeeNotificationCategory::Wishlist,
                    'saved_event_published',
                    'Your saved event "'.$event->name.'" was published.',
                    route('attendee.events.show', $event),
                    ['event_id' => $event->id],
                );
            }

            $this->notifyTicketSalesOpened($event);
        });
    }

    /**
     * Notify savers when ticket sales become available for a visible event.
     */
    public function notifyTicketSalesOpened(Event $event): void
    {
        if ($event->isCancelled() || $event->isCompleted() || ! $event->isVisibleToAttendees()) {
            return;
        }

        $event->loadMissing('ticketCategories');

        $hasOpenTicketSales = $event->ticketCategories
            ->contains(fn ($category) => $category->isSalesOpenNow());

        if (! $hasOpenTicketSales) {
            return;
        }

        foreach ($this->getUsersWhoSavedEvent($event) as $user) {
            $alreadyNotified = $user->notifications()
                ->where('data->type', 'ticket_sales_opened')
                ->where('data->event_id', $event->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            app(AttendeeNotificationService::class)->send(
                $user,
                AttendeeNotificationCategory::Wishlist,
                'ticket_sales_opened',
                'Ticket sales are now open for your saved event "'.$event->name.'".',
                route('attendee.events.show', $event),
                ['event_id' => $event->id],
            );
        }
    }

    public function notifyEventCompleted(Event $event): void
    {
        $recipients = $this->interestedAttendees($event);

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $user) {
            $user->notify(new \App\Notifications\EventCompletedNotification($event));
        }
    }

    public function notifyEventCancelled(Event $event, string $reason = ''): void
    {
        $recipients = $this->interestedAttendees($event);

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $user) {
            $user->notify(new \App\Notifications\EventCancelledNotification($event, $reason));
        }
    }

    /**
     * Ticket holders + savers + host followers (unique).
     *
     * @return Collection<int, User>
     */
    public function interestedAttendees(Event $event): Collection
    {
        return $this->mergeRecipients(
            $this->getConfirmedTicketHolders($event),
            $this->getUsersWhoSavedEvent($event),
            $this->getHostFollowers((int) $event->hosted_by),
        );
    }

    /**
     * @param  Collection<int, User>  ...$groups
     * @return Collection<int, User>
     */
    public function mergeRecipients(Collection ...$groups): Collection
    {
        $merged = new Collection;

        foreach ($groups as $group) {
            foreach ($group as $user) {
                $merged[$user->id] = $user;
            }
        }

        return $merged->values();
    }

    public function sendReminder(Event $event, User $user, EventReminderTypeEnum $type): void
    {
        $alreadySent = EventReminderLog::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('reminder_type', $type)
            ->exists();

        if ($alreadySent) {
            return;
        }

        EventReminderLog::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'reminder_type' => $type,
            'sent_at' => now(),
        ]);

        Mail::to($user)->queue(new EventReminderMail($event, $user, $type));
        $user->notify(new EventReminderNotification($event, $type));
    }

    /**
     * @return Collection<int, User>
     */
    public function getConfirmedTicketHolders(Event $event): Collection
    {
        $userIds = ticketBooking::query()
            ->where('event_id', $event->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct()
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->get();
    }

    /**
     * Attendees who saved/bookmarked the event.
     *
     * @return Collection<int, User>
     */
    public function getUsersWhoSavedEvent(Event $event): Collection
    {
        $userIds = SavedEvent::query()
            ->where('event_id', $event->id)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return new Collection;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->whereHas('userRole', fn ($query) => $query->where('name_en', UserRole::ATTENDEE))
            ->get();
    }

    /**
     * Attendees who follow the host.
     *
     * @return Collection<int, User>
     */
    public function getHostFollowers(int $hostId): Collection
    {
        $userIds = FollowHost::query()
            ->where('host_id', $hostId)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return new Collection;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->whereHas('userRole', fn ($query) => $query->where('name_en', UserRole::ATTENDEE))
            ->get();
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public static function buildChangesFromEvent(Event $event, array $original): array
    {
        $changes = [];

        foreach (self::UPDATABLE_FIELDS as $field) {
            $newValue = $event->{$field};
            $oldValue = $original[$field] ?? null;

            if ((string) $newValue !== (string) $oldValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}

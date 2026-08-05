<?php

namespace App\Console\Commands;

use App\Enums\EventReminderTypeEnum;
use App\Models\Event;
use App\Services\EventNotificationService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminders to ticket holders (7 days, 1 day, and 3 hours before start)';

    public function handle(EventNotificationService $eventNotificationService): int
    {
        $events = Event::query()
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->get();

        $counts = [
            EventReminderTypeEnum::SevenDays->value => 0,
            EventReminderTypeEnum::OneDay->value => 0,
            EventReminderTypeEnum::ThreeHours->value => 0,
        ];

        foreach ($events as $event) {
            $startsAt = $event->startsAt();

            if ($startsAt->isPast()) {
                continue;
            }

            $hoursUntil = now()->diffInHours($startsAt, false);
            $reminderType = null;

            if ($hoursUntil >= (7 * 24 - 1) && $hoursUntil < (7 * 24 + 1)) {
                $reminderType = EventReminderTypeEnum::SevenDays;
            } elseif ($hoursUntil >= 23 && $hoursUntil < 25) {
                $reminderType = EventReminderTypeEnum::OneDay;
            } elseif ($hoursUntil >= 2 && $hoursUntil < 4) {
                $reminderType = EventReminderTypeEnum::ThreeHours;
            }

            if ($reminderType === null) {
                continue;
            }

            $recipients = $eventNotificationService->getConfirmedTicketHolders($event);

            foreach ($recipients as $user) {
                $eventNotificationService->sendReminder($event, $user, $reminderType);
                $counts[$reminderType->value]++;
            }
        }

        $this->info(sprintf(
            'Sent %d seven-day, %d one-day, and %d three-hour reminder(s).',
            $counts[EventReminderTypeEnum::SevenDays->value],
            $counts[EventReminderTypeEnum::OneDay->value],
            $counts[EventReminderTypeEnum::ThreeHours->value],
        ));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\EventReminderTypeEnum;
use App\Models\Event;
use App\Services\CroNotificationService;
use App\Services\EventNotificationService;
use App\Services\OrganizerNotificationService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminders to ticket holders, CROs, and organizers';

    public function handle(
        EventNotificationService $eventNotificationService,
        CroNotificationService $croNotificationService,
        OrganizerNotificationService $organizerNotificationService,
    ): int {
        $events = Event::query()
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->get();

        $counts = [
            EventReminderTypeEnum::SevenDays->value => 0,
            EventReminderTypeEnum::OneDay->value => 0,
            EventReminderTypeEnum::ThreeHours->value => 0,
        ];
        $croTomorrowCount = 0;
        $organizerTomorrowCount = 0;
        $organizerOneHourCount = 0;

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

            if ($reminderType !== null) {
                $recipients = $eventNotificationService->getConfirmedTicketHolders($event);

                foreach ($recipients as $user) {
                    $eventNotificationService->sendReminder($event, $user, $reminderType);
                    $counts[$reminderType->value]++;
                }

                if ($reminderType === EventReminderTypeEnum::OneDay) {
                    $croNotificationService->notifyEventStartsTomorrow($event);
                    $croTomorrowCount++;
                }
            }

            // Organizer reminders for their own events (independent of attendee windows).
            if ($hoursUntil >= 23 && $hoursUntil < 25) {
                $organizerNotificationService->notifyEventStartsTomorrow($event);
                $organizerTomorrowCount++;
            }

            if ($hoursUntil >= 0 && $hoursUntil < 2) {
                $organizerNotificationService->notifyEventStartsInOneHour($event);
                $organizerOneHourCount++;
            }
        }

        $this->info(sprintf(
            'Sent %d seven-day, %d one-day, and %d three-hour reminder(s). CRO tomorrow: %d. Organizer tomorrow: %d. Organizer 1-hour: %d.',
            $counts[EventReminderTypeEnum::SevenDays->value],
            $counts[EventReminderTypeEnum::OneDay->value],
            $counts[EventReminderTypeEnum::ThreeHours->value],
            $croTomorrowCount,
            $organizerTomorrowCount,
            $organizerOneHourCount,
        ));

        return self::SUCCESS;
    }
}

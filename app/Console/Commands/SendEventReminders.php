<?php

namespace App\Console\Commands;

use App\Enums\EventReminderTypeEnum;
use App\Models\Event;
use App\Services\EventNotificationService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminder notifications to ticket holders (1 day and 2 hours before start)';

    public function handle(EventNotificationService $eventNotificationService): int
    {
        $events = Event::query()
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->get();

        $oneDayCount = 0;
        $twoHourCount = 0;

        foreach ($events as $event) {
            $startsAt = $event->startsAt();

            if ($startsAt->isPast()) {
                continue;
            }

            $hoursUntil = now()->diffInHours($startsAt, false);

            $reminderType = null;

            if ($hoursUntil >= 23 && $hoursUntil < 25) {
                $reminderType = EventReminderTypeEnum::OneDay;
            } elseif ($hoursUntil >= 1 && $hoursUntil < 3) {
                $reminderType = EventReminderTypeEnum::TwoHours;
            }

            if ($reminderType === null) {
                continue;
            }

            $recipients = $eventNotificationService->getConfirmedTicketHolders($event);

            foreach ($recipients as $user) {
                $eventNotificationService->sendReminder($event, $user, $reminderType);

                if ($reminderType === EventReminderTypeEnum::OneDay) {
                    $oneDayCount++;
                } else {
                    $twoHourCount++;
                }
            }
        }

        $this->info("Sent {$oneDayCount} one-day reminder(s) and {$twoHourCount} two-hour reminder(s).");

        return self::SUCCESS;
    }
}

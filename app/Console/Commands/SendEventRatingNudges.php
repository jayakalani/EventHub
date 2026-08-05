<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EventNotificationService;
use Illuminate\Console\Command;

class SendEventRatingNudges extends Command
{
    protected $signature = 'events:send-rating-nudges';

    protected $description = 'Prompt ticket holders to rate and comment ~24 hours after an event ends';

    public function handle(EventNotificationService $eventNotificationService): int
    {
        $events = Event::query()
            ->where('status', Event::STATUS_COMPLETED)
            ->whereNotNull('date')
            ->where(function ($query) {
                $query->where('date_tba', false)->orWhereNull('date_tba');
            })
            // Keep the scan window bounded so old completed events are not re-checked forever.
            ->whereDate('date', '>=', now()->subDays(14)->toDateString())
            ->whereDate('date', '<=', now()->toDateString())
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            $hoursSinceStart = $event->startsAt()->diffInHours(now());

            // Same ±1 hour window style as pre-event reminders.
            if ($hoursSinceStart < 23 || $hoursSinceStart >= 25) {
                continue;
            }

            $recipients = $eventNotificationService->getConfirmedTicketHolders($event);

            foreach ($recipients as $user) {
                if ($eventNotificationService->sendRatingNudge($event, $user)) {
                    $sent++;
                }
            }
        }

        $this->info(sprintf('Sent %d post-event rating nudge(s).', $sent));

        return self::SUCCESS;
    }
}

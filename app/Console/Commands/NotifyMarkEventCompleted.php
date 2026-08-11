<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\OrganizerNotificationService;
use Illuminate\Console\Command;

class NotifyMarkEventCompleted extends Command
{
    protected $signature = 'events:notify-mark-completed';

    protected $description = 'Remind organizers to mark ongoing events as completed after the event date has passed';

    public function handle(OrganizerNotificationService $organizerNotificationService): int
    {
        $events = Event::query()
            ->where('status', Event::STATUS_ONGOING)
            ->whereNotNull('date')
            ->where(function ($query) {
                $query->where('date_tba', false)->orWhereNull('date_tba');
            })
            ->whereDate('date', '<', now()->toDateString())
            ->whereDate('date', '>=', now()->subDays(30)->toDateString())
            ->with('organizer.userRole')
            ->get();

        $sent = 0;

        foreach ($events as $event) {
            if ($organizerNotificationService->notifyMarkEventCompleted($event)) {
                $sent++;
            }
        }

        $this->info(sprintf('Sent %d mark-as-completed reminder(s).', $sent));

        return self::SUCCESS;
    }
}

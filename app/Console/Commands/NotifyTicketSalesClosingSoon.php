<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ticketCategory;
use App\Services\OrganizerNotificationService;
use Illuminate\Console\Command;

class NotifyTicketSalesClosingSoon extends Command
{
    protected $signature = 'events:notify-ticket-sales-closing-soon';

    protected $description = 'Notify organizers when ticket sales for their categories close within about 24 hours';

    public function handle(OrganizerNotificationService $organizerNotificationService): int
    {
        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(25);

        $categories = ticketCategory::query()
            ->where('is_active', true)
            ->where('no_of_available_tickets', '>', 0)
            ->whereNotNull('booking_end')
            ->whereBetween('booking_end', [$windowStart, $windowEnd])
            ->with(['event.organizer.userRole'])
            ->get();

        $notified = 0;

        foreach ($categories as $category) {
            $event = $category->event;

            if (! $event || ! in_array($event->status, [Event::STATUS_UPCOMING, Event::STATUS_ONGOING], true)) {
                continue;
            }

            $organizerNotificationService->notifyTicketSalesClosingSoon($category);
            $notified++;
        }

        $this->info("Checked {$categories->count()} ticket categor(ies); sales-closing reminders considered for {$notified}.");

        return self::SUCCESS;
    }
}

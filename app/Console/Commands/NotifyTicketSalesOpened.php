<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ticketCategory;
use App\Services\EventNotificationService;
use Illuminate\Console\Command;

class NotifyTicketSalesOpened extends Command
{
    protected $signature = 'events:notify-ticket-sales-opened';

    protected $description = 'Notify savers when scheduled ticket booking windows become open';

    public function handle(EventNotificationService $eventNotificationService): int
    {
        $openedSince = now()->subHours(2);

        $eventIds = ticketCategory::query()
            ->where('is_active', true)
            ->where('no_of_available_tickets', '>', 0)
            ->whereNotNull('booking_start')
            ->where('booking_start', '<=', now())
            ->where('booking_start', '>=', $openedSince)
            ->where(function ($query) {
                $query->whereNull('booking_end')
                    ->orWhere('booking_end', '>=', now());
            })
            ->distinct()
            ->pluck('event_id');

        if ($eventIds->isEmpty()) {
            $this->info('No ticket categories newly opened.');

            return self::SUCCESS;
        }

        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->get();

        $notifiedEvents = 0;

        foreach ($events as $event) {
            $eventNotificationService->notifyTicketSalesOpened($event);
            $notifiedEvents++;
        }

        $this->info("Checked {$notifiedEvents} event(s) for ticket sales opened notifications.");

        return self::SUCCESS;
    }
}

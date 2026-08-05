<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Models\Event;
use Illuminate\Console\Command;

class PurgeCompletedEventCartItems extends Command
{
    protected $signature = 'cart:purge-completed-events';

    protected $description = 'Remove cart tickets for events that completed more than the retention period ago';

    public function handle(): int
    {
        $retentionDays = (int) config('cart.completed_event_retention_days', 5);
        $cutoffDate = now()->subDays($retentionDays)->toDateString();

        $deleted = CartItem::query()
            ->whereHas('event', function ($query) use ($cutoffDate) {
                $query
                    ->where('status', Event::STATUS_COMPLETED)
                    ->whereNotNull('date')
                    ->whereDate('date', '<=', $cutoffDate);
            })
            ->delete();

        $this->info("Removed {$deleted} cart item(s) for completed events older than {$retentionDays} day(s).");

        return self::SUCCESS;
    }
}

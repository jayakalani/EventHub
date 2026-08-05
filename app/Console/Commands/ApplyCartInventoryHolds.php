<?php

namespace App\Console\Commands;

use App\Services\CartInventoryService;
use Illuminate\Console\Command;

class ApplyCartInventoryHolds extends Command
{
    protected $signature = 'cart:apply-inventory-holds {--force : Run without confirmation}';

    protected $description = 'One-time: decrement available stock for existing cart items that were created before hard holds';

    public function handle(CartInventoryService $cartInventoryService): int
    {
        $force = (bool) $this->option('force');

        if (! $force && ! $this->confirm('This will decrement available tickets for cart items without a hard hold. Continue?', true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $applied = $cartInventoryService->applyMissingHolds();

        $this->info("Applied inventory holds for {$applied} cart item(s).");

        return self::SUCCESS;
    }
}

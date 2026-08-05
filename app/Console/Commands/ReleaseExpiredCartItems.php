<?php

namespace App\Console\Commands;

use App\Services\CartInventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredCartItems extends Command
{
    protected $signature = 'cart:release-expired';

    protected $description = 'Release hard inventory holds for expired cart reservations and remove those items';

    public function handle(CartInventoryService $cartInventoryService): int
    {
        $released = $cartInventoryService->releaseExpired();

        $this->info("Released inventory and removed {$released} expired cart item(s).");

        return self::SUCCESS;
    }
}

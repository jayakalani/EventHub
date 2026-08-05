<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Services\CartNotificationService;
use Illuminate\Console\Command;

class SendCartPendingPaymentReminders extends Command
{
    protected $signature = 'cart:send-pending-payment-reminders';

    protected $description = 'Notify attendees when cart tickets have been pending payment for more than 5 days';

    public function handle(CartNotificationService $cartNotificationService): int
    {
        $sentCount = 0;
        $threshold = now()->subDays(5);

        $cartItems = CartItem::query()
            ->with(['user', 'event'])
            ->where('created_at', '<=', $threshold)
            ->get();

        foreach ($cartItems as $cartItem) {
            $user = $cartItem->user;

            if (! $user) {
                continue;
            }

            $cartNotificationService->sendPendingFiveDayReminder($cartItem, $user);
            $sentCount++;
        }

        $this->info("Sent {$sentCount} pending-payment reminder(s).");

        return self::SUCCESS;
    }
}

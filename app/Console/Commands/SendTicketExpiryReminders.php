<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Services\CartNotificationService;
use Illuminate\Console\Command;

class SendTicketExpiryReminders extends Command
{
    protected $signature = 'cart:send-expiry-reminders';

    protected $description = 'Send reminders before cart reservations expire';

    public function handle(CartNotificationService $cartNotificationService): int
    {
        $reminderMinutes = (int) config('cart.expiry_reminder_minutes_before', 10);
        $sentCount = 0;

        $cartItems = CartItem::query()
            ->with(['user', 'event', 'ticketCategory'])
            ->get()
            ->filter(fn (CartItem $cartItem) => ! $cartItem->isExpired());

        foreach ($cartItems as $cartItem) {
            $minutesRemaining = $cartItem->minutesUntilExpiry();

            if ($minutesRemaining > $reminderMinutes || $minutesRemaining <= 0) {
                continue;
            }

            $user = $cartItem->user;

            if (! $user) {
                continue;
            }

            $cartNotificationService->sendExpiryReminder($cartItem, $user, $minutesRemaining);
            $sentCount++;
        }

        $this->info("Sent {$sentCount} ticket expiry reminder(s).");

        return self::SUCCESS;
    }
}

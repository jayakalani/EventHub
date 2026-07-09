<?php

namespace App\Services;

use App\Enums\CartReminderTypeEnum;
use App\Mail\TicketExpiryReminderMail;
use App\Models\CartItem;
use App\Models\CartReminderLog;
use App\Models\User;
use App\Notifications\TicketExpiryReminderNotification;
use Illuminate\Support\Facades\Mail;

class CartNotificationService
{
    public function sendExpiryReminder(CartItem $cartItem, User $user, int $minutesRemaining): void
    {
        $alreadySent = CartReminderLog::query()
            ->where('cart_item_id', $cartItem->id)
            ->where('user_id', $user->id)
            ->where('reminder_type', CartReminderTypeEnum::ExpiryWarning)
            ->exists();

        if ($alreadySent) {
            return;
        }

        CartReminderLog::create([
            'cart_item_id' => $cartItem->id,
            'user_id' => $user->id,
            'reminder_type' => CartReminderTypeEnum::ExpiryWarning,
            'sent_at' => now(),
        ]);

        $user->notify(new TicketExpiryReminderNotification($cartItem, $minutesRemaining));
        Mail::to($user)->queue(new TicketExpiryReminderMail($cartItem, $user, $minutesRemaining));
    }
}

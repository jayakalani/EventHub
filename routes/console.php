<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:send-reminders')->hourly();
Schedule::command('events:notify-mark-completed')->hourly();
Schedule::command('events:send-rating-nudges')->hourly();
Schedule::command('events:send-organizer-review-digests')->dailyAt('10:00');
Schedule::command('organizer:send-weekly-digests')->weeklyOn(1, '09:00');
Schedule::command('events:notify-ticket-sales-opened')->hourly();
Schedule::command('events:notify-ticket-sales-closing-soon')->hourly();
Schedule::command('cart:release-expired')->everyMinute();
Schedule::command('cart:send-expiry-reminders')->everyFiveMinutes();
Schedule::command('cart:send-pending-payment-reminders')->daily();
Schedule::command('cart:purge-completed-events')->daily();
Schedule::command('payments:fulfill-paid')->everyFiveMinutes();

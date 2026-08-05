<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:complete-past')->hourly();
Schedule::command('events:send-reminders')->hourly();
Schedule::command('events:notify-ticket-sales-opened')->hourly();
Schedule::command('events:notify-ticket-sales-closing-soon')->hourly();
Schedule::command('cart:send-expiry-reminders')->everyFiveMinutes();
Schedule::command('cart:send-pending-payment-reminders')->daily();
Schedule::command('cart:purge-completed-events')->daily();

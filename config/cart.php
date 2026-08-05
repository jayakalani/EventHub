<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cart Reservation Duration
    |--------------------------------------------------------------------------
    |
    | Minutes a cart reservation remains valid before tickets are released.
    | Default: 3 days (4320 minutes).
    |
    */

    'reservation_minutes' => (int) env('CART_RESERVATION_MINUTES', 4320),

    /*
    |--------------------------------------------------------------------------
    | Expiry Reminder Lead Time
    |--------------------------------------------------------------------------
    |
    | Send a reminder this many minutes before a reservation expires.
    | Default: 1 day before expiry (1440 minutes).
    |
    */

    'expiry_reminder_minutes_before' => (int) env('CART_EXPIRY_REMINDER_MINUTES', 1440),

    /*
    |--------------------------------------------------------------------------
    | Completed Event Cart Retention
    |--------------------------------------------------------------------------
    |
    | Days to keep cart tickets after an event's date once it is completed.
    | After this period, cart items for that event are removed automatically.
    |
    */

    'completed_event_retention_days' => (int) env('CART_COMPLETED_EVENT_RETENTION_DAYS', 5),

];

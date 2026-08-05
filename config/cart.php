<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cart Reservation Duration
    |--------------------------------------------------------------------------
    |
    | Minutes a cart reservation remains valid before tickets are released.
    |
    */

    'reservation_minutes' => (int) env('CART_RESERVATION_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Expiry Reminder Lead Time
    |--------------------------------------------------------------------------
    |
    | Send a reminder this many minutes before a reservation expires.
    |
    */

    'expiry_reminder_minutes_before' => (int) env('CART_EXPIRY_REMINDER_MINUTES', 10),

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

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

];

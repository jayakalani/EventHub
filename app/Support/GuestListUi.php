<?php

namespace App\Support;

class GuestListUi
{
    /**
     * @return array<string, mixed>
     */
    public static function organizer(): array
    {
        return [
            'index' => 'organizer.bookings.index',
            'show' => 'organizer.bookings.show',
            'scan' => 'organizer.bookings.scan',
            'scan_submit' => 'organizer.bookings.scan.submit',
            'check_in' => 'organizer.bookings.check-in',
            'export_csv' => 'organizer.bookings.export.csv',
            'event_show' => 'organizer.events.show',
            'events_index' => 'organizer.events.index',
            'home' => 'organizer.dashboard',
            'refund_show' => null,
            'subtitle' => 'Guests, bookings, and event-day check-in across your events.',
            'filter_all_events' => 'All Events',
            'scan_subtitle' => 'Scan tickets only for events you have marked as Ongoing.',
            'scan_empty' => 'Check-in opens only after you set an event\'s status to Ongoing on your Events page. Status does not change automatically.',
            'scan_empty_cta' => 'Go to Events',
            'scan_disabled_title' => 'Set an event to Ongoing on the Events page to enable check-in.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function cro(): array
    {
        return [
            'index' => 'cro.bookings.index',
            'show' => 'cro.bookings.show',
            'scan' => 'cro.bookings.scan',
            'scan_submit' => 'cro.bookings.scan.submit',
            'check_in' => 'cro.bookings.check-in',
            'export_csv' => 'cro.bookings.export.csv',
            'event_show' => null,
            'events_index' => null,
            'home' => 'cro.dashboard',
            'refund_show' => 'cro.refund-requests.show',
            'subtitle' => 'Guests, bookings, and event-day check-in for your assigned events only.',
            'filter_all_events' => 'All assigned events',
            'scan_subtitle' => 'Scan tickets only for assigned events the organizer has marked as Ongoing.',
            'scan_empty' => 'Check-in opens only after the organizer sets an assigned event to Ongoing. You can check in guests for those events only.',
            'scan_empty_cta' => 'Back to Dashboard',
            'scan_disabled_title' => 'Check-in is available when an assigned event is set to Ongoing.',
        ];
    }
}

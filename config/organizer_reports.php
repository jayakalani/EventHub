<?php

use App\Enums\BookingStatusEnum;
use App\Models\Event;

/**
 * Organizer report catalog (English only).
 *
 * Each report: label, description, formats, fields, filters, generator class.
 */
return [

    'generators' => [
        'sales' => \App\Services\OrganizerReports\Generators\SalesReport::class,
        'bookings' => \App\Services\OrganizerReports\Generators\BookingsReport::class,
        'events' => \App\Services\OrganizerReports\Generators\EventsReport::class,
        'reviews' => \App\Services\OrganizerReports\Generators\ReviewsReport::class,
        'insights_analytics' => \App\Services\OrganizerReports\Generators\InsightsAnalyticsReport::class,
    ],

    'reports' => [

        'sales' => [
            'label' => 'Sales',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'ticket_number' => 'Ticket Number',
                'event' => 'Event',
                'category' => 'Ticket Category',
                'amount' => 'Amount (LKR)',
                'original_amount' => 'Original Amount (LKR)',
                'refund_amount' => 'Refund Amount (LKR)',
                'purchased_at' => 'Purchased At',
                'check_in_status' => 'Check-in Status',
                'status' => 'Ticket Status',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'options' => collect(BookingStatusEnum::salesListStatuses())
                        ->mapWithKeys(fn (BookingStatusEnum $status) => [
                            $status->value => ucwords(str_replace('_', ' ', $status->value)),
                        ])
                        ->all(),
                    'label' => 'Status',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Purchased From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Purchased To'],
            ],
        ],

        'bookings' => [
            'label' => 'Guest List',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'ticket_number' => 'Ticket Number',
                'guest_name' => 'Guest Name',
                'email' => 'Email',
                'event' => 'Event',
                'ticket_type' => 'Ticket Type',
                'amount' => 'Amount',
                'status' => 'Booking Status',
                'purchased_at' => 'Purchased At',
                'checked_in' => 'Checked In',
                'checked_in_at' => 'Checked In At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'options' => collect(BookingStatusEnum::retainedSaleStatuses())
                        ->mapWithKeys(fn (BookingStatusEnum $status) => [
                            $status->value => ucwords(str_replace('_', ' ', $status->value)),
                        ])
                        ->all(),
                    'label' => 'Status',
                ],
                [
                    'key' => 'check_in',
                    'type' => 'select',
                    'options' => [
                        'checked_in' => 'Checked in',
                        'not_checked_in' => 'Not checked in',
                    ],
                    'label' => 'Check-in',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Purchased From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Purchased To'],
            ],
        ],

        'events' => [
            'label' => 'Events',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'status' => 'Status',
                'date' => 'Date',
                'location' => 'Location',
                'tickets_sold' => 'Tickets Sold',
                'revenue' => 'Revenue (LKR)',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'options' => [
                        Event::STATUS_UPCOMING => 'Upcoming',
                        Event::STATUS_ONGOING => 'Ongoing',
                        Event::STATUS_POSTPONED => 'Postponed',
                        Event::STATUS_COMPLETED => 'Completed',
                        Event::STATUS_CANCELLED => 'Cancelled',
                        Event::STATUS_UNPUBLISHED => 'Unpublished',
                    ],
                    'label' => 'Status',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Event From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Event To'],
            ],
        ],

        'reviews' => [
            'label' => 'Reviews',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'event' => 'Event',
                'reviewer' => 'Reviewer',
                'email' => 'Email',
                'score' => 'Score',
                'submitted_at' => 'Submitted At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'score',
                    'type' => 'select',
                    'options' => [
                        '5' => '5 stars',
                        '4' => '4 stars',
                        '3' => '3 stars',
                        '2' => '2 stars',
                        '1' => '1 star',
                    ],
                    'label' => 'Score',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
            ],
        ],

        'insights_analytics' => [
            'label' => 'Insights Analytics',
            'description' => 'PDF only – analytics summary export',
            'kind' => 'analytics',
            'formats' => ['pdf'],
            'fields' => [],
            'filters' => [
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'options' => [
                        Event::STATUS_UPCOMING => 'Upcoming',
                        Event::STATUS_ONGOING => 'Ongoing',
                        Event::STATUS_POSTPONED => 'Postponed',
                        Event::STATUS_COMPLETED => 'Completed',
                        Event::STATUS_CANCELLED => 'Cancelled',
                    ],
                    'label' => 'Event Status',
                ],
                [
                    'key' => 'period',
                    'type' => 'select',
                    'options' => [
                        'week' => 'Last 7 Days',
                        'month' => 'Last 30 Days',
                        'custom' => 'Custom Range',
                    ],
                    'label' => 'Period',
                ],
                [
                    'key' => 'date_from',
                    'type' => 'date',
                    'label' => 'From',
                    'show_when' => ['period' => 'custom'],
                    'required' => true,
                ],
                [
                    'key' => 'date_to',
                    'type' => 'date',
                    'label' => 'To',
                    'show_when' => ['period' => 'custom'],
                    'required' => true,
                ],
            ],
        ],

    ],
];

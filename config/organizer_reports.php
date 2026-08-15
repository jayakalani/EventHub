<?php

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Models\Event;

/**
 * Organizer report catalog (English only).
 *
 * Groups mirror the organizer dashboard tabs so exports line up with Insights.
 * Each report: label, description, group, formats, fields, filters, generator.
 */
return [

    'groups' => [
        'performance' => [
            'label' => 'Performance',
            'icon' => 'bi-speedometer2',
            'description' => 'Dashboard analytics summary',
        ],
        'revenue' => [
            'label' => 'Revenue',
            'icon' => 'bi-cash-stack',
            'description' => 'Sales, payments, and refunds',
        ],
        'tickets' => [
            'label' => 'Tickets',
            'icon' => 'bi-ticket-perforated',
            'description' => 'Ticket categories and inventory',
        ],
        'events' => [
            'label' => 'Events',
            'icon' => 'bi-calendar-event',
            'description' => 'Your events and performance',
        ],
        'attendance' => [
            'label' => 'Attendance',
            'icon' => 'bi-person-check',
            'description' => 'Guest lists and check-ins',
        ],
        'audience' => [
            'label' => 'Audience',
            'icon' => 'bi-people',
            'description' => 'Attendees and customers',
        ],
        'engagement' => [
            'label' => 'Engagement',
            'icon' => 'bi-heart',
            'description' => 'Reviews and ratings',
        ],
        'resources' => [
            'label' => 'Resources',
            'icon' => 'bi-collection',
            'description' => 'Hosts and artists you manage',
        ],
    ],

    'generators' => [
        'insights_analytics' => \App\Services\OrganizerReports\Generators\InsightsAnalyticsReport::class,
        'sales' => \App\Services\OrganizerReports\Generators\SalesReport::class,
        'refunds' => \App\Services\OrganizerReports\Generators\RefundsReport::class,
        'payments' => \App\Services\OrganizerReports\Generators\PaymentsReport::class,
        'ticket_categories' => \App\Services\OrganizerReports\Generators\TicketCategoriesReport::class,
        'events' => \App\Services\OrganizerReports\Generators\EventsReport::class,
        'bookings' => \App\Services\OrganizerReports\Generators\BookingsReport::class,
        'attendees' => \App\Services\OrganizerReports\Generators\AttendeesReport::class,
        'reviews' => \App\Services\OrganizerReports\Generators\ReviewsReport::class,
        'hosts' => \App\Services\OrganizerReports\Generators\HostsReport::class,
        'artists' => \App\Services\OrganizerReports\Generators\ArtistsReport::class,
    ],

    'reports' => [

        'insights_analytics' => [
            'label' => 'Dashboard Analytics',
            'description' => 'PDF only – analytics summary export',
            'group' => 'performance',
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
                    'key' => 'section',
                    'type' => 'select',
                    'options' => [
                        'full' => 'Full Report (all tabs)',
                        'overview' => 'Overview',
                        'revenue' => 'Revenue',
                        'tickets' => 'Tickets',
                        'events' => 'Events',
                        'attendance' => 'Attendance',
                        'audience' => 'Audience',
                        'engagement' => 'Engagement',
                        'activity' => 'Activity',
                    ],
                    'label' => 'Dashboard Section',
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
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
            ],
        ],

        'sales' => [
            'label' => 'Sales',
            'description' => 'Tabular export with field selection',
            'group' => 'revenue',
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

        'refunds' => [
            'label' => 'Refund Requests',
            'description' => 'Tabular export with field selection',
            'group' => 'revenue',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'ticket_number' => 'Ticket Number',
                'user' => 'Attendee',
                'event' => 'Event',
                'amount' => 'Refund Amount',
                'percentage' => 'Percentage',
                'status' => 'Status',
                'reason' => 'Reason',
                'requested_at' => 'Requested At',
                'reviewed_at' => 'Reviewed At',
                'reviewer' => 'Reviewed By',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'status',
                    'type' => 'enum',
                    'enum' => RefundRequestStatusEnum::class,
                    'label' => 'Status',
                ],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Requested From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Requested To'],
            ],
        ],

        'payments' => [
            'label' => 'Payments',
            'description' => 'Tabular export with field selection',
            'group' => 'revenue',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'reference' => 'Reference',
                'buyer' => 'Buyer',
                'email' => 'Email',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'status' => 'Status',
                'payment_method' => 'Method',
                'purpose' => 'Purpose',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'options' => collect(PaymentStatusEnum::cases())
                        ->mapWithKeys(fn (PaymentStatusEnum $status) => [
                            $status->value => ucfirst($status->value),
                        ])
                        ->all(),
                    'label' => 'Status',
                ],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
            ],
        ],

        'ticket_categories' => [
            'label' => 'Ticket Categories',
            'description' => 'Tabular export with field selection',
            'group' => 'tickets',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'event' => 'Event',
                'name' => 'Category',
                'price' => 'Price (LKR)',
                'capacity' => 'Capacity',
                'available' => 'Available',
                'sold' => 'Sold',
                'revenue' => 'Revenue (LKR)',
                'is_active' => 'Active',
                'booking_start' => 'Booking Start',
                'booking_end' => 'Booking End',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'active',
                    'type' => 'select',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ],
                    'label' => 'Active',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Event From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Event To'],
            ],
        ],

        'events' => [
            'label' => 'Events',
            'description' => 'Tabular export with field selection',
            'group' => 'events',
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

        'bookings' => [
            'label' => 'Guest List',
            'description' => 'Tabular export with field selection',
            'group' => 'attendance',
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

        'attendees' => [
            'label' => 'Attendees',
            'description' => 'Tabular export with field selection',
            'group' => 'audience',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'name' => 'Name',
                'email' => 'Email',
                'tickets' => 'Tickets',
                'events' => 'Events Attended',
                'total_spent' => 'Total Spent (LKR)',
                'first_purchase' => 'First Purchase',
                'last_purchase' => 'Last Purchase',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Purchased From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Purchased To'],
            ],
        ],

        'reviews' => [
            'label' => 'Reviews',
            'description' => 'Tabular export with field selection',
            'group' => 'engagement',
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

        'hosts' => [
            'label' => 'Hosts',
            'description' => 'Tabular export with field selection',
            'group' => 'resources',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'contact_number' => 'Contact',
                'events_count' => 'Events',
                'is_active' => 'Active',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'active',
                    'type' => 'select',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ],
                    'label' => 'Active',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Created From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Created To'],
            ],
        ],

        'artists' => [
            'label' => 'Artists',
            'description' => 'Tabular export with field selection',
            'group' => 'resources',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'contact_number' => 'Contact',
                'events_count' => 'Events',
                'followers' => 'Followers',
                'is_active' => 'Active',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'active',
                    'type' => 'select',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ],
                    'label' => 'Active',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Created From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Created To'],
            ],
        ],

    ],
];

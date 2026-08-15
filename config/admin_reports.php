<?php

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\Event;

/**
 * Admin export catalog (tabular CSV/PDF).
 *
 * Groups mirror dashboard domains. Live charts stay on Dashboard Insights.
 */
return [

    'groups' => [
        'people' => [
            'label' => 'People',
            'icon' => 'bi-people',
            'description' => 'Accounts and roles',
        ],
        'events' => [
            'label' => 'Events',
            'icon' => 'bi-calendar-event',
            'description' => 'Platform events and inventory',
        ],
        'revenue' => [
            'label' => 'Revenue',
            'icon' => 'bi-cash-stack',
            'description' => 'Payments and refunds',
        ],
        'attendance' => [
            'label' => 'Attendance',
            'icon' => 'bi-person-check',
            'description' => 'Tickets and guest lists',
        ],
        'audience' => [
            'label' => 'Audience',
            'icon' => 'bi-person-hearts',
            'description' => 'Buyers and spend',
        ],
        'support' => [
            'label' => 'Support',
            'icon' => 'bi-headset',
            'description' => 'Inquiries and complaints',
        ],
        'resources' => [
            'label' => 'Resources',
            'icon' => 'bi-collection',
            'description' => 'Hosts and artists',
        ],
    ],

    'generators' => [
        'insights_analytics' => \App\Services\AdminReports\Generators\InsightsAnalyticsReport::class,
        'users' => \App\Services\AdminReports\Generators\UsersReport::class,
        'events' => \App\Services\AdminReports\Generators\EventsReport::class,
        'ticket_categories' => \App\Services\AdminReports\Generators\TicketCategoriesReport::class,
        'payments' => \App\Services\AdminReports\Generators\PaymentsReport::class,
        'refunds' => \App\Services\AdminReports\Generators\RefundsReport::class,
        'bookings' => \App\Services\AdminReports\Generators\BookingsReport::class,
        'attendees' => \App\Services\AdminReports\Generators\AttendeesReport::class,
        'support' => \App\Services\AdminReports\Generators\SupportReport::class,
        'hosts' => \App\Services\AdminReports\Generators\HostsReport::class,
        'artists' => \App\Services\AdminReports\Generators\ArtistsReport::class,
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
                [
                    'key' => 'section',
                    'type' => 'select',
                    'options' => [
                        'all' => 'All',
                        'performance' => 'Performance',
                        'support' => 'Support',
                        'overview' => 'Overview',
                        'activity' => 'Activity',
                        'events' => 'Events',
                        'users' => 'Users',
                        'payments' => 'Payments',
                    ],
                    'label' => 'Dashboard Section',
                    'required' => true,
                    'include_empty' => false,
                ],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer', 'hide_when' => ['section' => 'support']],
                ['key' => 'cro_id', 'type' => 'cros', 'label' => 'CRO', 'show_when' => ['section' => 'support']],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
            ],
        ],

        'users' => [
            'label' => 'Users',
            'description' => 'Tabular export with field selection',
            'group' => 'people',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email',
                'role' => 'Role',
                'contact_number' => 'Contact',
                'is_active' => 'Active',
                'is_locked' => 'Locked',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'role_id', 'type' => 'roles', 'label' => 'Role'],
                [
                    'key' => 'state',
                    'type' => 'select',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'locked' => 'Locked',
                    ],
                    'label' => 'State',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Created From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Created To'],
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
                'organizer' => 'Organizer',
                'status' => 'Status',
                'date' => 'Date',
                'place' => 'Place',
                'tickets_sold' => 'Tickets Sold',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
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

        'ticket_categories' => [
            'label' => 'Ticket Categories',
            'description' => 'Tabular export with field selection',
            'group' => 'events',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'event' => 'Event',
                'organizer' => 'Organizer',
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
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
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
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
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
                'organizer' => 'Organizer',
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
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Requested From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Requested To'],
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
                'organizer' => 'Organizer',
                'ticket_type' => 'Ticket Type',
                'amount' => 'Amount',
                'status' => 'Booking Status',
                'purchased_at' => 'Purchased At',
                'checked_in' => 'Checked In',
                'checked_in_at' => 'Checked In At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
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
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Purchased From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Purchased To'],
            ],
        ],

        'support' => [
            'label' => 'Support Tickets',
            'description' => 'Tabular export with field selection',
            'group' => 'support',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'type' => 'Type',
                'id' => 'ID',
                'subject' => 'Subject',
                'user' => 'User',
                'context' => 'Event / Context',
                'status' => 'Status',
                'assignee' => 'Assignee',
                'submitted_at' => 'Submitted At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'ticket_type',
                    'type' => 'select',
                    'options' => [
                        'inquiry' => 'Inquiries only',
                        'complaint' => 'Complaints only',
                    ],
                    'label' => 'Ticket Type',
                ],
                [
                    'key' => 'status',
                    'type' => 'enum',
                    'enum' => SupportTicketStatusEnum::class,
                    'label' => 'Status',
                ],
                ['key' => 'cro_id', 'type' => 'cros', 'label' => 'CRO Assignee'],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Submitted From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Submitted To'],
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
                'organizer' => 'Organizer',
                'events_count' => 'Events',
                'is_active' => 'Active',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
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
                'organizer' => 'Organizer',
                'events_count' => 'Events',
                'followers' => 'Followers',
                'is_active' => 'Active',
                'created_at' => 'Created At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
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

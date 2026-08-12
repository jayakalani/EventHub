<?php

use App\Enums\PaymentStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\Event;

/**
 * Admin report catalog (English only).
 *
 * Each report: label, description, formats, fields, filters, generator class.
 */
return [

    'generators' => [
        'users' => \App\Services\AdminReports\Generators\UsersReport::class,
        'events' => \App\Services\AdminReports\Generators\EventsReport::class,
        'payments' => \App\Services\AdminReports\Generators\PaymentsReport::class,
        'support' => \App\Services\AdminReports\Generators\SupportReport::class,
        'insights_analytics' => \App\Services\AdminReports\Generators\InsightsAnalyticsReport::class,
    ],

    'reports' => [

        'users' => [
            'label' => 'Users',
            'description' => 'Tabular export with field selection',
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

        'payments' => [
            'label' => 'Payments',
            'description' => 'Tabular export with field selection',
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

        'support' => [
            'label' => 'Support Tickets',
            'description' => 'Tabular export with field selection',
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
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Submitted From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Submitted To'],
            ],
        ],

        'insights_analytics' => [
            'label' => 'Insights Analytics',
            'description' => 'PDF only – platform analytics summary',
            'kind' => 'analytics',
            'formats' => ['pdf'],
            'fields' => [],
            'filters' => [
                ['key' => 'organizer_id', 'type' => 'organizers', 'label' => 'Organizer'],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'section',
                    'type' => 'select',
                    'options' => [
                        'admin' => 'Platform Overview',
                        'users' => 'Users',
                        'payments' => 'Payments',
                    ],
                    'label' => 'Section',
                ],
            ],
        ],

    ],
];

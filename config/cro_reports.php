<?php

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;

/**
 * CRO report catalog (English only).
 *
 * Each report: label, description, formats, fields, filters, generator class.
 */
return [

    'generators' => [
        'inquiries' => \App\Services\CroReports\Generators\InquiriesReport::class,
        'complaints' => \App\Services\CroReports\Generators\ComplaintsReport::class,
        'refunds' => \App\Services\CroReports\Generators\RefundsReport::class,
        'guest_list' => \App\Services\CroReports\Generators\GuestListReport::class,
        'dashboard_analytics' => \App\Services\CroReports\Generators\DashboardAnalyticsReport::class,
    ],

    'reports' => [

        'inquiries' => [
            'label' => 'Inquiries',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'subject' => 'Subject',
                'user' => 'User',
                'event' => 'Event',
                'status' => 'Status',
                'assignee' => 'Assignee',
                'submitted_at' => 'Submitted At',
                'updated_at' => 'Updated At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'status',
                    'type' => 'enum',
                    'enum' => SupportTicketStatusEnum::class,
                    'label' => 'Status',
                ],
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'assignment',
                    'type' => 'select',
                    'options' => [
                        'all' => 'All',
                        'me' => 'Assigned to me',
                        'unassigned' => 'Unassigned',
                    ],
                    'label' => 'Assignment',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Submitted From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Submitted To'],
            ],
        ],

        'complaints' => [
            'label' => 'Complaints',
            'description' => 'Tabular export with field selection',
            'formats' => ['pdf', 'csv'],
            'fields' => [
                'id' => 'ID',
                'subject' => 'Subject',
                'user' => 'User',
                'status' => 'Status',
                'assignee' => 'Assignee',
                'submitted_at' => 'Submitted At',
                'updated_at' => 'Updated At',
            ],
            'filters' => [
                ['key' => 'q', 'type' => 'text', 'label' => 'Search'],
                [
                    'key' => 'status',
                    'type' => 'enum',
                    'enum' => SupportTicketStatusEnum::class,
                    'label' => 'Status',
                ],
                [
                    'key' => 'assignment',
                    'type' => 'select',
                    'options' => [
                        'all' => 'All',
                        'me' => 'Assigned to me',
                        'unassigned' => 'Unassigned',
                    ],
                    'label' => 'Assignment',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'Submitted From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'Submitted To'],
            ],
        ],

        'refunds' => [
            'label' => 'Refund Requests',
            'description' => 'Tabular export with field selection',
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

        'guest_list' => [
            'label' => 'Guest List',
            'description' => 'Assigned events only — tickets, check-in, and attendees',
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
                'checked_in_by' => 'Checked In By',
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

        'dashboard_analytics' => [
            'label' => 'Dashboard Analytics',
            'description' => 'PDF only – analytics summary export',
            'kind' => 'analytics',
            'formats' => ['pdf'],
            'fields' => [],
            'filters' => [
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
                [
                    'key' => 'section',
                    'type' => 'select',
                    'include_empty' => false,
                    'options' => [
                        'full' => 'Full Report (all tabs)',
                        'today' => 'Today',
                        'attendance' => 'Attendance',
                        'performance' => 'Performance',
                        'support' => 'Support',
                        'inquiry' => 'Inquiry',
                        'complaints' => 'Complaints',
                    ],
                    'label' => 'Dashboard Section',
                ],
                ['key' => 'date_from', 'type' => 'date', 'label' => 'From'],
                ['key' => 'date_to', 'type' => 'date', 'label' => 'To'],
            ],
        ],

    ],
];

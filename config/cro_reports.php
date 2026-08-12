<?php

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

        'dashboard_analytics' => [
            'label' => 'Dashboard Analytics',
            'description' => 'PDF only – analytics summary export',
            'kind' => 'analytics',
            'formats' => ['pdf'],
            'fields' => [],
            'filters' => [
                ['key' => 'event_id', 'type' => 'events', 'label' => 'Event'],
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

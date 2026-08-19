<?php

namespace App\Services\Exports;

use App\Services\OrganizerReportService;
use App\Services\ReportExportService;

class OrganizerReportExportBuilder
{
    public function __construct(
        protected OrganizerReportService $reportService,
        protected ReportExportService $exportService,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     title: string,
     *     subtitle?: string,
     *     summary: list<array{label: string, value: string|int|float}>,
     *     filters: list<array{label: string, value: string}>,
     *     kpis: list<array{label: string, value: string|int|float}>,
     *     sections: list<array{key: string, title: string, summary: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}>,
     *     tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>
     * }
     */
    public function build(int $organizerId, string $section, array $filters = []): array
    {
        $reports = $this->reportService->getAllReports($organizerId, $filters);
        $labels = $reports['chartLabels'];
        $filterMeta = $this->filterSummary($reports['filters'] ?? $filters, $reports['filterOptions']['events'] ?? []);

        $payload = match ($section) {
            'full' => $this->buildFull($reports, $labels),
            'overview' => $this->buildOverview($reports, $labels),
            'revenue' => $this->buildRevenue($reports, $labels),
            'tickets' => $this->buildTickets($reports, $labels),
            'events' => $this->buildEvents($reports),
            'attendance' => $this->buildAttendance($reports),
            'engagement' => $this->buildEngagement($reports, $labels),
            'audience' => $this->buildAudience($reports),
            'activity' => $this->buildActivity($reports),
            // Legacy aliases from older export buttons
            'sales' => $this->buildTickets($reports, $labels),
            'attendees' => $this->buildAudience($reports),
            default => abort(404),
        };

        $kpis = $payload['summary'] ?? [];
        $sections = $payload['sections'] ?? [[
            'key' => $section,
            'title' => $this->shortSectionLabel($payload['title'] ?? 'Report'),
            'summary' => $kpis,
            'tables' => $payload['tables'] ?? [],
        ]];

        $payload['filters'] = $filterMeta;
        $payload['kpis'] = $kpis;
        $payload['sections'] = $sections;
        $payload['subtitle'] = $payload['subtitle'] ?? 'Organizer performance analytics';
        $payload['summary'] = [
            ...$filterMeta,
            ...$kpis,
        ];

        if (! isset($payload['tables'])) {
            $payload['tables'] = collect($sections)
                ->flatMap(function (array $sectionPayload) {
                    $sectionTitle = $sectionPayload['title'] ?? 'Section';

                    return collect($sectionPayload['tables'] ?? [])->map(fn (array $table) => [
                        'heading' => $sectionTitle.' — '.($table['heading'] ?? 'Data'),
                        'headers' => $table['headers'] ?? [],
                        'rows' => $table['rows'] ?? [],
                    ]);
                })
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * @param  array{from?: string|null, to?: string|null, event_id?: int|string|null, status?: string|null}  $filters
     * @param  list<array{id: int, name: string}>  $events
     * @return list<array{label: string, value: string}>
     */
    private function filterSummary(array $filters, array $events): array
    {
        $eventId = $filters['event_id'] ?? null;
        $eventName = 'All Events';

        if ($eventId) {
            $match = collect($events)->firstWhere('id', (int) $eventId);
            $eventName = $match['name'] ?? ('Event #'.$eventId);
        }

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $dateRange = ($from || $to)
            ? trim(($from ?: '…').' → '.($to ?: '…'))
            : 'All dates';

        $status = $filters['status'] ?? null;

        return [
            ['label' => 'Event filter', 'value' => $eventName],
            ['label' => 'Date range', 'value' => $dateRange],
            ['label' => 'Status filter', 'value' => $status ? ucfirst((string) $status) : 'All statuses'],
        ];
    }

    private function buildFull(array $reports, array $labels): array
    {
        $overview = $this->buildOverview($reports, $labels);
        $sectionBuilders = [
            'overview' => $overview,
            'revenue' => $this->buildRevenue($reports, $labels),
            'tickets' => $this->buildTickets($reports, $labels),
            'events' => $this->buildEvents($reports),
            'attendance' => $this->buildAttendance($reports),
            'audience' => $this->buildAudience($reports),
            'engagement' => $this->buildEngagement($reports, $labels),
            'activity' => $this->buildActivity($reports),
        ];

        $sections = [];
        $tables = [];

        foreach ($sectionBuilders as $key => $sectionPayload) {
            $sectionTitle = $this->shortSectionLabel($sectionPayload['title'] ?? 'Section');
            $sectionTables = $sectionPayload['tables'] ?? [];

            $sections[] = [
                'key' => $key,
                'title' => $sectionTitle,
                'summary' => $sectionPayload['summary'] ?? [],
                'tables' => $sectionTables,
            ];

            foreach ($sectionTables as $table) {
                $tables[] = [
                    'heading' => $sectionTitle.' — '.($table['heading'] ?? 'Data'),
                    'headers' => $table['headers'] ?? [],
                    'rows' => $table['rows'] ?? [],
                ];
            }
        }

        return [
            'title' => 'Organizer Reports — Full Report',
            'subtitle' => 'Complete performance analytics across revenue, tickets, events, attendance, audience, and engagement',
            'summary' => $overview['summary'] ?? [],
            'sections' => $sections,
            'tables' => $tables,
        ];
    }

    private function shortSectionLabel(string $title): string
    {
        return trim(str_ireplace('Organizer Reports — ', '', $title)) ?: $title;
    }

    private function buildOverview(array $reports, array $labels): array
    {
        $sales = $reports['ticketSales'];
        $revenue = $reports['revenue'];
        $attendees = $reports['attendees'];
        $engagement = $reports['engagement'];
        $salesByCategory = $reports['salesByCategory'] ?? [];

        return [
            'title' => 'Organizer Reports — Overview',
            'summary' => [
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($revenue['netRevenue'], 2)],
                ['label' => 'Tickets Sold', 'value' => $sales['totalTicketsSold']],
                ['label' => 'Events', 'value' => $sales['totalEvents']],
                ['label' => 'Attendees', 'value' => $attendees['totalAttendees']],
                ['label' => 'Avg Rating', 'value' => $engagement['averageRating'] ?? '—'],
            ],
            'tables' => [
                [
                    'heading' => 'Revenue Trend',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $revenue['revenueTrend']),
                ],
                [
                    'heading' => 'Ticket Sales by Category',
                    'headers' => ['Category', 'Tickets', 'Share %'],
                    'rows' => collect($salesByCategory)->map(fn ($r) => [
                        $r['label'], $r['count'], $r['percentage'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildRevenue(array $reports, array $labels): array
    {
        $data = $reports['revenue'];
        $refundAnalytics = $reports['refundAnalytics'] ?? [];

        return [
            'title' => 'Organizer Reports — Revenue',
            'summary' => [
                ['label' => 'Gross Revenue (LKR)', 'value' => number_format($data['grossRevenue'], 2)],
                ['label' => 'Net Revenue (LKR)', 'value' => number_format($data['netRevenue'], 2)],
                ['label' => 'Refunded (LKR)', 'value' => number_format($data['totalRefunded'], 2)],
                ['label' => 'Refund Rate %', 'value' => $data['refundRate'] ?? ($refundAnalytics['refundRate'] ?? '—')],
                ['label' => 'Refund Count', 'value' => $data['refundCount'] ?? ($refundAnalytics['refundCount'] ?? 0)],
            ],
            'tables' => [
                [
                    'heading' => 'Monthly Revenue',
                    'headers' => ['Month', 'Revenue (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['revenueTrend']),
                ],
                [
                    'heading' => 'Cumulative Revenue',
                    'headers' => ['Month', 'Cumulative (LKR)'],
                    'rows' => $this->exportService->trendRows($labels, $data['cumulativeRevenueTrend'] ?? []),
                ],
                [
                    'heading' => 'Refunds vs Sales',
                    'headers' => ['Month', 'Confirmed Sales (LKR)', 'Refunds (LKR)'],
                    'rows' => collect($labels)->map(fn ($label, $i) => [
                        $label,
                        number_format((float) ($data['revenueTrend'][$i] ?? 0), 2),
                        number_format((float) ($data['refundTrend'][$i] ?? 0), 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Refunds by Event',
                    'headers' => ['Event', 'Refunds', 'Amount (LKR)', 'Rate %'],
                    'rows' => collect($refundAnalytics['byEvent'] ?? [])->map(fn ($r) => [
                        $r['name'], $r['refund_count'], number_format($r['refunded'], 2), $r['rate'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Refunds by Ticket Category',
                    'headers' => ['Category', 'Refunds', 'Amount (LKR)', 'Share %', 'Rate %'],
                    'rows' => collect($refundAnalytics['byCategory'] ?? [])->map(fn ($r) => [
                        $r['label'], $r['refund_count'], number_format($r['refunded'], 2), $r['share'], $r['rate'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Revenue by Event',
                    'headers' => ['Event', 'Status', 'Revenue (LKR)'],
                    'rows' => collect($data['revenueByEvent'])->map(fn ($r) => [
                        $r['name'], $r['status'], number_format($r['revenue'], 2),
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildTickets(array $reports, array $labels): array
    {
        $sales = $reports['ticketSales'];
        $ticketTypeTrend = $reports['ticketTypeTrend'] ?? [];
        $conversionFunnel = $reports['conversionFunnel'] ?? [];
        $salesVelocity = $reports['salesVelocity'] ?? [];

        $typeSeries = collect($ticketTypeTrend);
        $typeLabels = $typeSeries->pluck('label')->all();
        $typeTrendRows = collect($labels)->map(function ($month, $i) use ($typeSeries) {
            $row = [$month];
            foreach ($typeSeries as $series) {
                $row[] = $series['data'][$i] ?? 0;
            }

            return $row;
        })->all();

        $velocityLabels = $salesVelocity['labels'] ?? [];
        $velocityRows = collect($velocityLabels)->map(fn ($label, $i) => [
            $label,
            $salesVelocity['tickets'][$i] ?? 0,
            $salesVelocity['cumulative'][$i] ?? 0,
        ])->all();

        $peak = $salesVelocity['peak'] ?? null;

        return [
            'title' => 'Organizer Reports — Tickets',
            'summary' => [
                ['label' => 'Tickets Sold', 'value' => $sales['totalTicketsSold']],
                ['label' => 'Events', 'value' => $sales['totalEvents']],
                ['label' => 'Events with Sales', 'value' => $sales['eventsWithSales']],
                ['label' => 'Pre-event Window Tickets', 'value' => $salesVelocity['totalInWindow'] ?? 0],
                [
                    'label' => 'Peak Velocity Day',
                    'value' => $peak
                        ? ($peak['label'].' ('.$peak['count'].')')
                        : '—',
                ],
                ['label' => 'Final Week Share %', 'value' => $salesVelocity['finalWeekShare'] ?? '—'],
            ],
            'tables' => [
                [
                    'heading' => 'Ticket Sales Over Time',
                    'headers' => ['Month', 'Tickets Sold'],
                    'rows' => $this->exportService->trendRows($labels, $sales['salesTrend']),
                ],
                [
                    'heading' => 'Ticket Type Trend',
                    'headers' => array_merge(['Month'], $typeLabels),
                    'rows' => count($typeLabels) ? $typeTrendRows : [['—', 'No ticket-type data']],
                ],
                [
                    'heading' => 'Conversion Funnel',
                    'headers' => ['Stage', 'Count', 'Rate %'],
                    'rows' => collect($conversionFunnel)->map(fn ($r) => [
                        $r['label'], $r['count'], $r['rate'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Tickets Per Day Before Event (T-30 → T-0)',
                    'headers' => ['Day', 'Tickets Sold'],
                    'rows' => count($velocityRows)
                        ? collect($velocityRows)->map(fn ($row) => [$row[0], $row[1]])->all()
                        : [['—', 0]],
                ],
                [
                    'heading' => 'Cumulative Tickets Before Event (T-30 → T-0)',
                    'headers' => ['Day', 'Cumulative'],
                    'rows' => count($velocityRows)
                        ? collect($velocityRows)->map(fn ($row) => [$row[0], $row[2]])->all()
                        : [['—', 0]],
                ],
                [
                    'heading' => 'Sales by Event',
                    'headers' => ['Event', 'Sold', 'Capacity', 'Fill Rate %'],
                    'rows' => collect($sales['salesByEvent'])->map(fn ($r) => [
                        $r['name'], $r['sold'], $r['capacity'], $r['fill_rate'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildEvents(array $reports): array
    {
        $performance = $reports['eventPerformance'] ?? [];
        $eventsByStatus = $reports['eventsByStatus'] ?? [];
        $postponedCount = (int) (collect($eventsByStatus)->firstWhere('key', 'postponed')['count'] ?? 0);
        $top = collect($performance)
            ->sortByDesc(fn ($event) => ((float) $event['revenue']) + ((int) $event['tickets_sold'] * 100))
            ->take(10)
            ->values();

        return [
            'title' => 'Organizer Reports — Events',
            'summary' => [
                ['label' => 'Events Listed', 'value' => count($performance)],
                ['label' => 'Postponed Events', 'value' => $postponedCount],
                ['label' => 'Total Tickets Sold', 'value' => collect($performance)->sum('tickets_sold')],
                ['label' => 'Total Revenue (LKR)', 'value' => number_format((float) collect($performance)->sum('revenue'), 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Events by Status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($eventsByStatus)->map(fn ($row) => [
                        $row['label'] ?? '',
                        $row['count'] ?? 0,
                    ])->all(),
                ],
                [
                    'heading' => 'Event Performance',
                    'headers' => ['Event', 'Tickets Sold', 'Revenue (LKR)', 'Fill Rate %', 'Rating', 'Status'],
                    'rows' => collect($performance)->map(fn ($r) => [
                        $r['name'],
                        $r['tickets_sold'],
                        number_format($r['revenue'], 2),
                        $r['fill_rate'],
                        $r['rating'] ?? '—',
                        $r['status'],
                    ])->all(),
                ],
                [
                    'heading' => 'Top Performing Events',
                    'headers' => ['Event', 'Tickets', 'Revenue (LKR)', 'Fill Rate %', 'Status'],
                    'rows' => $top->map(fn ($r) => [
                        $r['name'], $r['tickets_sold'], number_format($r['revenue'], 2), $r['fill_rate'], $r['status'],
                    ])->all(),
                ],
                [
                    'heading' => 'Fill Rate by Event',
                    'headers' => ['Event', 'Revenue (LKR)', 'Fill Rate %', 'Tickets Sold', 'Capacity', 'What this means'],
                    'rows' => collect($performance)->map(fn ($r) => [
                        $r['name'],
                        number_format((float) ($r['revenue'] ?? 0), 2),
                        $r['fill_rate'],
                        $r['tickets_sold'] ?? 0,
                        $r['capacity'] ?? '—',
                        $r['insight'] ?? '—',
                    ])->all(),
                ],
                [
                    'heading' => 'Event Comparison Metrics',
                    'headers' => ['Event', 'Revenue (LKR)', 'Fill %', 'Conversion %', 'Rating', 'Tickets'],
                    'rows' => collect($reports['eventComparison'] ?? [])->take(10)->map(fn ($r) => [
                        $r['name'],
                        number_format($r['revenue'], 2),
                        $r['fill_rate'],
                        $r['conversion_rate'] ?? '—',
                        $r['rating'] ?? '—',
                        $r['tickets_sold'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildAttendance(array $reports): array
    {
        $data = $reports['attendance'] ?? [];
        $byEvent = $data['byEvent'] ?? [];
        $timing = $data['checkInTiming'] ?? [];
        $peak = $data['peakTiming'] ?? null;

        return [
            'title' => 'Organizer Reports — Attendance',
            'summary' => [
                ['label' => 'Eligible Tickets', 'value' => $data['ticketsEligible'] ?? 0],
                ['label' => 'Checked In', 'value' => $data['checkedIn'] ?? 0],
                ['label' => 'No-shows (completed)', 'value' => $data['noShows'] ?? 0],
                ['label' => 'Awaiting Check-in', 'value' => $data['awaitingCheckIn'] ?? 0],
                ['label' => 'Attendance Rate %', 'value' => $data['attendanceRate'] ?? '—'],
                [
                    'label' => 'Peak Check-in Window',
                    'value' => $peak
                        ? ($peak['label'].' ('.$peak['count'].')')
                        : '—',
                ],
            ],
            'tables' => [
                [
                    'heading' => 'Attendance by Event',
                    'headers' => [
                        'Event',
                        'Date',
                        'Status',
                        'Tickets',
                        'Checked In',
                        'No-shows',
                        'Awaiting',
                        'Attendance %',
                        'Final?',
                    ],
                    'rows' => collect($byEvent)->map(fn ($r) => [
                        $r['name'],
                        $r['date'] ?? '—',
                        $r['status'] ?? '—',
                        $r['tickets'],
                        $r['checked_in'],
                        $r['attendance_final'] ? $r['no_shows'] : '—',
                        $r['attendance_final'] ? '—' : $r['awaiting_check_in'],
                        $r['attendance_rate'],
                        $r['attendance_final'] ? 'Yes' : 'No',
                    ])->all(),
                ],
                [
                    'heading' => 'Check-in Timing (relative to event start)',
                    'headers' => ['Window', 'Check-ins'],
                    'rows' => collect($timing)->map(fn ($r) => [
                        $r['label'],
                        $r['count'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildEngagement(array $reports, array $labels): array
    {
        $data = $reports['engagement'];
        $before = $data['engagementBeforeEvent'] ?? null;
        $vsSales = $data['engagementVsSales'] ?? [];

        $tables = [
            [
                'heading' => 'Engagement Over Time',
                'headers' => ['Month', 'Likes', 'Saves', 'Comments', 'Ratings'],
                'rows' => collect($labels)->map(fn ($label, $i) => [
                    $label,
                    $data['engagementTrend']['likes'][$i] ?? 0,
                    $data['engagementTrend']['saves'][$i] ?? 0,
                    $data['engagementTrend']['comments'][$i] ?? 0,
                    $data['engagementTrend']['ratings'][$i] ?? 0,
                ])->all(),
            ],
            [
                'heading' => 'Engagement by Event',
                'headers' => ['Event', 'Likes', 'Saves', 'Comments', 'Ratings', 'Score', 'Tickets Sold'],
                'rows' => collect($data['popularityByEvent'])->map(fn ($r) => [
                    $r['name'],
                    $r['likes'],
                    $r['saves'] ?? 0,
                    $r['comments'],
                    $r['ratings'],
                    $r['score'],
                    $r['tickets_sold'] ?? 0,
                ])->all(),
            ],
                [
                    'heading' => 'Tickets vs Engagement by Event',
                    'headers' => ['Event', 'Tickets Sold', 'Engagement', 'What this means', 'Likes', 'Comments', 'Saves'],
                    'rows' => collect($vsSales)->map(fn ($r) => [
                        $r['name'],
                        $r['tickets_sold'],
                        $r['engagement'],
                        $r['insight'] ?? '—',
                        $r['likes'] ?? 0,
                        $r['comments'] ?? 0,
                        $r['saves'] ?? 0,
                    ])->all(),
                ],
        ];

        if (is_array($before) && ! empty($before['labels'])) {
            $tables[] = [
                'heading' => 'Before Event Day (−28 → Event Day)',
                'headers' => ['Day Offset', 'Likes', 'Saves', 'Comments', 'Ratings', 'Tickets'],
                'rows' => collect($before['labels'])->map(fn ($label, $i) => [
                    $label,
                    $before['likes'][$i] ?? 0,
                    $before['saves'][$i] ?? 0,
                    $before['comments'][$i] ?? 0,
                    $before['ratings'][$i] ?? 0,
                    $before['tickets'][$i] ?? 0,
                ])->all(),
            ];
        }

        $quality = $data['reviewQuality'] ?? [];
        $tables[] = [
            'heading' => 'Average Rating Trend',
            'headers' => ['Month', 'Avg Score', 'Ratings Count'],
            'rows' => collect($labels)->map(fn ($label, $i) => [
                $label,
                $quality['averageTrend'][$i] ?? '—',
                $quality['countTrend'][$i] ?? 0,
            ])->all(),
        ];
        $tables[] = [
            'heading' => 'Rating Distribution',
            'headers' => ['Score', 'Count'],
            'rows' => collect($quality['distribution'] ?? [])->map(fn ($r) => [
                $r['label'], $r['count'],
            ])->all(),
        ];
        $tables[] = [
            'heading' => 'Low-rated Events',
            'headers' => ['Event', 'Avg Rating', 'Ratings', 'Status'],
            'rows' => collect($quality['lowRatedEvents'] ?? [])->map(fn ($r) => [
                $r['name'], $r['rating'], $r['ratings_count'], $r['status'],
            ])->all() ?: [['—', '—', 0, 'None']],
        ];

        return [
            'title' => 'Organizer Reports — Engagement',
            'summary' => [
                ['label' => 'Total Likes', 'value' => $data['totalLikes']],
                ['label' => 'Total Saves', 'value' => $data['totalSaves'] ?? 0],
                ['label' => 'Total Comments', 'value' => $data['totalComments']],
                ['label' => 'Total Ratings', 'value' => $data['totalRatings']],
                ['label' => 'Average Rating', 'value' => $data['averageRating'] ?? ($quality['averageRating'] ?? '—')],
            ],
            'tables' => $tables,
        ];
    }

    private function buildAudience(array $reports): array
    {
        $data = $reports['attendees'];
        $demographics = $data['demographics'] ?? ['age' => [], 'gender' => [], 'location' => [], 'province' => []];
        $topCustomers = $data['topCustomers'] ?? [];

        return [
            'title' => 'Organizer Reports — Audience',
            'summary' => [
                ['label' => 'Unique Attendees', 'value' => $data['totalAttendees']],
                ['label' => 'Confirmation Rate %', 'value' => $data['confirmationRate'] ?? 0],
                ['label' => 'New Attendees', 'value' => $data['newAttendees'] ?? 0],
                ['label' => 'Repeat Attendees', 'value' => $data['repeatAttendees'] ?? 0],
                ['label' => 'Avg Spend / Guest (LKR)', 'value' => number_format($data['avgSpendPerAttendee'] ?? 0, 2)],
            ],
            'tables' => [
                [
                    'heading' => 'Age Groups',
                    'headers' => ['Group', 'Count'],
                    'rows' => collect($demographics['age'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Gender',
                    'headers' => ['Gender', 'Count'],
                    'rows' => collect($demographics['gender'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Province',
                    'headers' => ['Province', 'Count'],
                    'rows' => collect($demographics['province'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'District',
                    'headers' => ['District', 'Count'],
                    'rows' => collect($demographics['location'] ?? [])->map(fn ($r) => [$r['label'], $r['count']])->all(),
                ],
                [
                    'heading' => 'Top Customers',
                    'headers' => ['Name', 'Email', 'Tickets', 'Spend (LKR)'],
                    'rows' => collect($topCustomers)->map(fn ($r) => [
                        $r['name'], $r['email'], $r['tickets'], number_format($r['spend'], 2),
                    ])->all(),
                ],
                [
                    'heading' => 'Attendees by Event',
                    'headers' => ['Event', 'Date', 'Status', 'Attendees'],
                    'rows' => collect($data['attendeesByEvent'] ?? [])->map(fn ($r) => [
                        $r['name'], $r['date'] ?? '—', $r['status'] ?? '—', $r['count'],
                    ])->all(),
                ],
            ],
        ];
    }

    private function buildActivity(array $reports): array
    {
        $transactions = $reports['recentTransactions'] ?? [];

        return [
            'title' => 'Organizer Reports — Activity',
            'summary' => [
                ['label' => 'Recent Transactions', 'value' => count($transactions)],
            ],
            'tables' => [
                [
                    'heading' => 'Recent Transactions',
                    'headers' => ['Customer', 'Email', 'Event', 'Category', 'Amount (LKR)', 'Status', 'When'],
                    'rows' => collect($transactions)->map(fn ($r) => [
                        $r['customer'],
                        $r['email'] ?? '—',
                        $r['event'],
                        $r['category'] ?? '—',
                        number_format($r['amount'], 2),
                        $r['status'],
                        $r['relative'] ?? ($r['when'] ?? '—'),
                    ])->all(),
                ],
            ],
        ];
    }
}

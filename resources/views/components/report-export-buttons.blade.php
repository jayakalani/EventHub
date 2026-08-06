@props([
    'excelRoute',
    'pdfRoute',
    'section',
    'scope' => 'organizer',
    /** @var array<string, mixed>|null Explicit filters; null falls back to the current request query. */
    'filters' => null,
    /** Optional filter form id — Excel/PDF pick up live field values on click. */
    'filterFormId' => null,
])

@php
    $filterSource = is_array($filters) ? $filters : [
        'from' => request('from'),
        'to' => request('to'),
        'event_id' => request('event_id'),
        'status' => request('status'),
        'organizer' => request('organizer'),
        'event' => request('event'),
        'cro' => request('cro'),
        'range' => request('range'),
    ];

    // Empty / missing filters → export all data. Any set filter → export that scoped slice.
    $exportQuery = array_filter([
        'section' => $section,
        'from' => $filterSource['from'] ?? null,
        'to' => $filterSource['to'] ?? null,
        'event_id' => $filterSource['event_id'] ?? null,
        'status' => $filterSource['status'] ?? null,
        'organizer' => $filterSource['organizer'] ?? null,
        'event' => $filterSource['event'] ?? null,
        'cro' => $filterSource['cro'] ?? null,
        'range' => $filterSource['range'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');

    $chartMaps = [
        'organizer' => [
            'full' => [
                // Revenue
                ['canvasId' => 'overviewRevenueChart', 'title' => 'Revenue — Revenue trend'],
                ['canvasId' => 'monthlyRevenueBarChart', 'title' => 'Revenue — Monthly revenue'],
                ['canvasId' => 'cumulativeRevenueChart', 'title' => 'Revenue — Cumulative revenue'],
                ['canvasId' => 'refundsVsSalesChart', 'title' => 'Revenue — Refunds vs confirmed sales'],
                ['canvasId' => 'refundsByEventChart', 'title' => 'Revenue — Refunds by event'],
                ['canvasId' => 'refundsByCategoryChart', 'title' => 'Revenue — Refunds by category'],
                // Tickets
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Tickets — Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Tickets — Ticket sales by category'],
                ['canvasId' => 'ticketTypeTrendChart', 'title' => 'Tickets — Ticket type trend'],
                ['canvasId' => 'conversionFunnelChart', 'title' => 'Tickets — Conversion funnel'],
                ['canvasId' => 'salesVelocityChart', 'title' => 'Tickets — Sales velocity before event day'],
                // Events
                ['canvasId' => 'revenuePerEventChart', 'title' => 'Events — Revenue per event'],
                ['canvasId' => 'top5EventsRevenueChart', 'title' => 'Events — Top 5 by revenue'],
                ['canvasId' => 'top5EventsTicketsChart', 'title' => 'Events — Top 5 by tickets'],
                ['canvasId' => 'revenueFillScatterChart', 'title' => 'Events — Revenue vs fill rate'],
                ['canvasId' => 'eventCompareMetricsChart', 'title' => 'Events — Event comparison'],
                // Attendance
                ['canvasId' => 'attendanceBreakdownChart', 'title' => 'Attendance — Attendance mix'],
                ['canvasId' => 'checkInTimingChart', 'title' => 'Attendance — Check-in timing'],
                ['canvasId' => 'attendanceByEventChart', 'title' => 'Attendance — By event'],
                // Audience
                ['canvasId' => 'demographicsAgeChart', 'title' => 'Audience — Age groups'],
                ['canvasId' => 'demographicsGenderChart', 'title' => 'Audience — Gender'],
                ['canvasId' => 'demographicsLocationChart', 'title' => 'Audience — Location'],
                ['canvasId' => 'repeatVsNewChart', 'title' => 'Audience — Repeat vs new attendees'],
                ['canvasId' => 'audienceEngagementVsSalesChart', 'title' => 'Audience — Engagement vs ticket sales'],
                ['canvasId' => 'overviewAttendeesByEventChart', 'title' => 'Audience — Attendees by event'],
                // Engagement
                ['canvasId' => 'overviewEngagementBarChart', 'title' => 'Engagement — Engagement analytics'],
                ['canvasId' => 'engagementOverTimeChart', 'title' => 'Engagement — Over time'],
                ['canvasId' => 'engagementBeforeEventChart', 'title' => 'Engagement — Before event day'],
                ['canvasId' => 'engagementVsSalesChart', 'title' => 'Engagement — Vs ticket sales'],
                ['canvasId' => 'ratingTrendChart', 'title' => 'Engagement — Average rating trend'],
                ['canvasId' => 'ratingDistributionChart', 'title' => 'Engagement — Rating distribution'],
            ],
            'overview' => [
                ['canvasId' => 'overviewRevenueChart', 'title' => 'Revenue trend'],
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Ticket sales by category'],
                ['canvasId' => 'overviewEngagementBarChart', 'title' => 'Engagement analytics'],
            ],
            'revenue' => [
                ['canvasId' => 'overviewRevenueChart', 'title' => 'Revenue trend'],
                ['canvasId' => 'monthlyRevenueBarChart', 'title' => 'Monthly revenue'],
                ['canvasId' => 'cumulativeRevenueChart', 'title' => 'Cumulative revenue'],
                ['canvasId' => 'refundsVsSalesChart', 'title' => 'Refunds vs confirmed sales'],
                ['canvasId' => 'refundsByEventChart', 'title' => 'Refunds by event'],
                ['canvasId' => 'refundsByCategoryChart', 'title' => 'Refunds by category'],
            ],
            'tickets' => [
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Ticket sales by category'],
                ['canvasId' => 'ticketTypeTrendChart', 'title' => 'Ticket type trend'],
                ['canvasId' => 'conversionFunnelChart', 'title' => 'Conversion funnel'],
                ['canvasId' => 'salesVelocityChart', 'title' => 'Sales velocity before event day'],
            ],
            'events' => [
                ['canvasId' => 'revenuePerEventChart', 'title' => 'Revenue per event'],
                ['canvasId' => 'top5EventsRevenueChart', 'title' => 'Top 5 events by revenue'],
                ['canvasId' => 'top5EventsTicketsChart', 'title' => 'Top 5 events by tickets'],
                ['canvasId' => 'revenueFillScatterChart', 'title' => 'Revenue vs fill rate'],
                ['canvasId' => 'eventCompareMetricsChart', 'title' => 'Event comparison'],
            ],
            'attendance' => [
                ['canvasId' => 'attendanceBreakdownChart', 'title' => 'Attendance mix'],
                ['canvasId' => 'checkInTimingChart', 'title' => 'Check-in timing'],
                ['canvasId' => 'attendanceByEventChart', 'title' => 'Attendance by event'],
            ],
            'engagement' => [
                ['canvasId' => 'overviewEngagementBarChart', 'title' => 'Engagement analytics'],
                ['canvasId' => 'engagementOverTimeChart', 'title' => 'Engagement over time'],
                ['canvasId' => 'engagementBeforeEventChart', 'title' => 'Engagement before event day'],
                ['canvasId' => 'engagementVsSalesChart', 'title' => 'Engagement vs ticket sales'],
                ['canvasId' => 'ratingTrendChart', 'title' => 'Average rating trend'],
                ['canvasId' => 'ratingDistributionChart', 'title' => 'Rating distribution'],
            ],
            'audience' => [
                ['canvasId' => 'demographicsAgeChart', 'title' => 'Age groups'],
                ['canvasId' => 'demographicsGenderChart', 'title' => 'Gender'],
                ['canvasId' => 'demographicsLocationChart', 'title' => 'Location'],
                ['canvasId' => 'repeatVsNewChart', 'title' => 'Repeat vs new attendees'],
                ['canvasId' => 'audienceEngagementVsSalesChart', 'title' => 'Engagement vs ticket sales'],
                ['canvasId' => 'overviewAttendeesByEventChart', 'title' => 'Attendees by event'],
            ],
            'attendees' => [
                ['canvasId' => 'demographicsAgeChart', 'title' => 'Age groups'],
                ['canvasId' => 'demographicsGenderChart', 'title' => 'Gender'],
                ['canvasId' => 'demographicsLocationChart', 'title' => 'Location'],
                ['canvasId' => 'repeatVsNewChart', 'title' => 'Repeat vs new attendees'],
                ['canvasId' => 'overviewAttendeesByEventChart', 'title' => 'Attendees by event'],
            ],
            'sales' => [
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Ticket sales by category'],
                ['canvasId' => 'ticketTypeTrendChart', 'title' => 'Ticket type trend'],
                ['canvasId' => 'conversionFunnelChart', 'title' => 'Conversion funnel'],
                ['canvasId' => 'salesVelocityChart', 'title' => 'Sales velocity before event day'],
            ],
            'activity' => [],
        ],
        'admin' => [
            'admin' => [
                ['canvasId' => 'adminOverviewUserGrowthChart', 'title' => 'User Growth'],
                ['canvasId' => 'adminOverviewUserDistributionChart', 'title' => 'User Distribution'],
                ['canvasId' => 'adminOverviewRevenueTrendChart', 'title' => 'Revenue Trend'],
                ['canvasId' => 'adminOverviewTicketSalesChart', 'title' => 'Ticket Sales Trend'],
                ['canvasId' => 'adminOverviewEventsByCategoryChart', 'title' => 'Events by Category'],
                ['canvasId' => 'adminEventsStatusChart', 'title' => 'Events by Status'],
            ],
            'users' => [
                ['canvasId' => 'userStatusChart', 'title' => 'Account Status Breakdown'],
                ['canvasId' => 'userRoleChart', 'title' => 'Users by Role'],
                ['canvasId' => 'userRegistrationChart', 'title' => 'User Registration Trend'],
            ],
            'payments' => [
                ['canvasId' => 'paymentStatusChart', 'title' => 'Payment Status'],
                ['canvasId' => 'paymentMethodChart', 'title' => 'Payment Methods'],
                ['canvasId' => 'paymentRevenueChart', 'title' => 'Payment Revenue Trend'],
            ],
        ],
        'cro' => [
            'inquiries' => [
                ['canvasId' => 'overviewResolutionRateChart', 'title' => 'Resolution Rate Trend'],
                ['canvasId' => 'overviewInquiryResolutionChart', 'title' => 'Inquiry vs Resolution Trend'],
                ['canvasId' => 'overviewResponseTimeChart', 'title' => 'Average Response Time'],
                ['canvasId' => 'overviewComplaintCategoriesChart', 'title' => 'Complaint Categories'],
                ['canvasId' => 'overviewCsatTrendChart', 'title' => 'CSAT Trend'],
                ['canvasId' => 'overviewCsatDistributionChart', 'title' => 'CSAT Distribution'],
                ['canvasId' => 'inquiryStatusChart', 'title' => 'Inquiry Status Distribution'],
                ['canvasId' => 'inquiryResolutionTrendChart', 'title' => 'Inquiry Submitted vs Resolved'],
                ['canvasId' => 'inquiryResponseTimeChart', 'title' => 'Inquiry Response Time Trend'],
                ['canvasId' => 'inquiryByEventChart', 'title' => 'Inquiries by Event'],
            ],
            'complaints' => [
                ['canvasId' => 'overviewComplaintCategoriesChart', 'title' => 'Complaint Categories'],
                ['canvasId' => 'overviewCsatTrendChart', 'title' => 'CSAT Trend'],
                ['canvasId' => 'overviewCsatDistributionChart', 'title' => 'CSAT Distribution'],
                ['canvasId' => 'complaintStatusChart', 'title' => 'Complaints by Status'],
                ['canvasId' => 'complaintTypeChart', 'title' => 'Complaints by Type'],
                ['canvasId' => 'complaintCategoryPieChart', 'title' => 'Complaint Categories Breakdown'],
                ['canvasId' => 'complaintSubmissionsChart', 'title' => 'Complaint Submission Trend'],
                ['canvasId' => 'complaintStatusByTypeChart', 'title' => 'Status Breakdown by Type'],
            ],
        ],
    ];

    $charts = $chartMaps[$scope][$section] ?? [];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}
    @if ($filterFormId) data-report-filter-form="{{ $filterFormId }}" @endif>
    <a href="{{ route($excelRoute, $exportQuery) }}"
        data-report-excel-export
        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:bg-emerald-700 hover:shadow-md hover:-translate-y-0.5">
        <i class="bi bi-file-earmark-excel"></i>
        <span class="hidden sm:inline">Export Excel</span>
        <span class="sm:hidden">Excel</span>
    </a>
    <button type="button"
        data-dashboard-pdf-export
        data-export-url="{{ route($pdfRoute) }}"
        data-export-params='@json($exportQuery)'
        data-export-charts='@json($charts)'
        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:bg-slate-50 hover:shadow-md hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-70">
        <i class="bi bi-file-earmark-pdf text-rose-600"></i>
        <span class="hidden sm:inline">Export PDF</span>
        <span class="sm:hidden">PDF</span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const FILTER_KEYS = ['from', 'to', 'event_id', 'status', 'organizer', 'event', 'cro', 'range'];

                function readFormFilters(formId) {
                    if (!formId) return {};
                    const form = document.getElementById(formId);
                    if (!form) return {};

                    const params = {};
                    FILTER_KEYS.forEach((key) => {
                        const field = form.elements.namedItem(key);
                        if (!field || typeof field.value !== 'string') return;
                        const value = field.value.trim();
                        if (value !== '') {
                            params[key] = value;
                        }
                    });
                    return params;
                }

                function mergeExportParams(baseParams, formParams) {
                    const merged = { ...(baseParams || {}) };
                    FILTER_KEYS.forEach((key) => {
                        delete merged[key];
                    });
                    return { ...merged, ...formParams };
                }

                document.addEventListener('click', function (event) {
                    const excelLink = event.target.closest('[data-report-excel-export]');
                    if (!excelLink) return;

                    const wrapper = excelLink.closest('[data-report-filter-form]');
                    const formId = wrapper?.getAttribute('data-report-filter-form');
                    if (!formId) return;

                    event.preventDefault();

                    const url = new URL(excelLink.href, window.location.origin);
                    const formParams = readFormFilters(formId);
                    FILTER_KEYS.forEach((key) => url.searchParams.delete(key));
                    Object.entries(formParams).forEach(([key, value]) => {
                        url.searchParams.set(key, value);
                    });

                    window.location.href = url.toString();
                });

                document.addEventListener('click', function (event) {
                    const pdfButton = event.target.closest('[data-dashboard-pdf-export]');
                    if (!pdfButton) return;

                    const wrapper = pdfButton.closest('[data-report-filter-form]');
                    const formId = wrapper?.getAttribute('data-report-filter-form');
                    if (!formId) return;

                    try {
                        const baseParams = JSON.parse(pdfButton.getAttribute('data-export-params') || '{}');
                        const formParams = readFormFilters(formId);
                        pdfButton.setAttribute(
                            'data-export-params',
                            JSON.stringify(mergeExportParams(baseParams, formParams))
                        );
                    } catch (error) {
                        // Keep baked-in params if live merge fails.
                    }
                }, true);
            })();
        </script>
    @endpush
@endonce

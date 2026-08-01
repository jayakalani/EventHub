@props([
    'excelRoute',
    'pdfRoute',
    'section',
    'scope' => 'organizer',
])

@php
    $exportQuery = array_filter([
        'section' => $section,
        'from' => request('from'),
        'to' => request('to'),
        'event_id' => request('event_id'),
        'status' => request('status'),
        'organizer' => request('organizer'),
        'event' => request('event'),
        'cro' => request('cro'),
        'range' => request('range'),
    ], fn ($value) => $value !== null && $value !== '');

    $chartMaps = [
        'organizer' => [
            'full' => [
                // Revenue
                ['canvasId' => 'overviewRevenueChart', 'title' => 'Revenue — Revenue trend'],
                ['canvasId' => 'monthlyRevenueBarChart', 'title' => 'Revenue — Monthly revenue'],
                ['canvasId' => 'cumulativeRevenueChart', 'title' => 'Revenue — Cumulative revenue'],
                ['canvasId' => 'refundsVsSalesChart', 'title' => 'Revenue — Refunds vs confirmed sales'],
                // Tickets
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Tickets — Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Tickets — Ticket sales by category'],
                ['canvasId' => 'ticketTypeTrendChart', 'title' => 'Tickets — Ticket type trend'],
                ['canvasId' => 'conversionFunnelChart', 'title' => 'Tickets — Conversion funnel'],
                // Events
                ['canvasId' => 'revenuePerEventChart', 'title' => 'Events — Revenue per event'],
                ['canvasId' => 'top5EventsRevenueChart', 'title' => 'Events — Top 5 by revenue'],
                ['canvasId' => 'top5EventsTicketsChart', 'title' => 'Events — Top 5 by tickets'],
                ['canvasId' => 'revenueFillScatterChart', 'title' => 'Events — Revenue vs fill rate'],
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
            ],
            'tickets' => [
                ['canvasId' => 'ticketSalesOverTimeChart', 'title' => 'Ticket sales over time'],
                ['canvasId' => 'salesByCategoryChart', 'title' => 'Ticket sales by category'],
                ['canvasId' => 'ticketTypeTrendChart', 'title' => 'Ticket type trend'],
                ['canvasId' => 'conversionFunnelChart', 'title' => 'Conversion funnel'],
            ],
            'events' => [
                ['canvasId' => 'revenuePerEventChart', 'title' => 'Revenue per event'],
                ['canvasId' => 'top5EventsRevenueChart', 'title' => 'Top 5 events by revenue'],
                ['canvasId' => 'top5EventsTicketsChart', 'title' => 'Top 5 events by tickets'],
                ['canvasId' => 'revenueFillScatterChart', 'title' => 'Revenue vs fill rate'],
            ],
            'engagement' => [
                ['canvasId' => 'overviewEngagementBarChart', 'title' => 'Engagement analytics'],
                ['canvasId' => 'engagementOverTimeChart', 'title' => 'Engagement over time'],
                ['canvasId' => 'engagementBeforeEventChart', 'title' => 'Engagement before event day'],
                ['canvasId' => 'engagementVsSalesChart', 'title' => 'Engagement vs ticket sales'],
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
            'system' => [
                ['canvasId' => 'systemActivityChart', 'title' => 'System Activity Trend'],
                ['canvasId' => 'systemAuditActionChart', 'title' => 'Audit Actions'],
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

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <a href="{{ route($excelRoute, $exportQuery) }}"
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

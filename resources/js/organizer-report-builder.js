import { captureDashboardChartImages } from './dashboard-pdf-export';
import { renderOrganizerDashboardExportCharts } from './organizer-dashboard';
import { renderOrganizerReportExportCharts } from './organizer-reports';

const ANALYTICS_CHARTS = [
    { canvasId: 'organizerRevenueChart', title: 'Revenue trend', section: 'overview' },
    { canvasId: 'organizerTicketSalesChart', title: 'Ticket sales trend', section: 'overview' },
    { canvasId: 'overviewRevenueChart', title: 'Revenue trend', section: 'revenue' },
    { canvasId: 'monthlyRevenueBarChart', title: 'Monthly revenue', section: 'revenue' },
    { canvasId: 'cumulativeRevenueChart', title: 'Cumulative revenue', section: 'revenue' },
    { canvasId: 'refundsVsSalesChart', title: 'Refunds vs confirmed sales', section: 'revenue' },
    { canvasId: 'refundsByEventChart', title: 'Refunds by event', section: 'revenue' },
    { canvasId: 'refundsByCategoryChart', title: 'Refunds by category', section: 'revenue' },
    { canvasId: 'ticketSalesOverTimeChart', title: 'Ticket sales over time', section: 'tickets' },
    { canvasId: 'salesByCategoryChart', title: 'Ticket sales by category', section: 'tickets' },
    { canvasId: 'ticketTypeTrendChart', title: 'Ticket type trend', section: 'tickets' },
    { canvasId: 'conversionFunnelChart', title: 'Conversion funnel', section: 'tickets' },
    { canvasId: 'salesVelocityChart', title: 'Tickets per day before event', section: 'tickets' },
    { canvasId: 'salesVelocityCumulativeChart', title: 'Cumulative tickets before event', section: 'tickets' },
    { canvasId: 'revenuePerEventChart', title: 'Revenue per event', section: 'events' },
    { canvasId: 'top5EventsRevenueChart', title: 'Top 5 by revenue', section: 'events' },
    { canvasId: 'top5EventsTicketsChart', title: 'Top 5 by tickets', section: 'events' },
    { canvasId: 'revenueFillScatterChart', title: 'Fill rate by event', section: 'events' },
    { canvasId: 'eventCompareMetricsChart', title: 'Event comparison', section: 'events' },
    { canvasId: 'attendanceBreakdownChart', title: 'Attendance mix', section: 'attendance' },
    { canvasId: 'checkInTimingChart', title: 'Check-in timing', section: 'attendance' },
    { canvasId: 'attendanceByEventChart', title: 'Attendance by event', section: 'attendance' },
    { canvasId: 'demographicsAgeChart', title: 'Age groups', section: 'audience' },
    { canvasId: 'demographicsGenderChart', title: 'Gender', section: 'audience' },
    { canvasId: 'demographicsLocationChart', title: 'Location', section: 'audience' },
    { canvasId: 'repeatVsNewChart', title: 'Repeat vs new attendees', section: 'audience' },
    { canvasId: 'audienceEngagementVsSalesChart', title: 'Tickets vs engagement by event', section: 'audience' },
    { canvasId: 'overviewAttendeesByEventChart', title: 'Attendees by event', section: 'audience' },
    { canvasId: 'overviewEngagementBarChart', title: 'Engagement analytics', section: 'engagement' },
    { canvasId: 'engagementOverTimeChart', title: 'Engagement over time', section: 'engagement' },
    { canvasId: 'engagementBeforeEventChart', title: 'Engagement before event day', section: 'engagement' },
    { canvasId: 'engagementVsSalesChart', title: 'Tickets vs engagement by event', section: 'engagement' },
    { canvasId: 'ratingTrendChart', title: 'Average rating trend', section: 'engagement' },
    { canvasId: 'ratingDistributionChart', title: 'Rating distribution', section: 'engagement' },
];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function filenameFromDisposition(header, fallback = 'organizer-analytics.pdf') {
    if (!header) return fallback;

    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const plainMatch = header.match(/filename=\"?([^\";]+)\"?/i);
    return plainMatch?.[1] ?? fallback;
}

function chartTargetsFor(section) {
    if (!section || section === 'full') {
        return ANALYTICS_CHARTS;
    }

    return ANALYTICS_CHARTS.filter((chart) => chart.section === section);
}

async function waitForPaint() {
    await new Promise((resolve) => {
        requestAnimationFrame(() => setTimeout(resolve, 400));
    });
}

async function fetchChartPayload(form, filters) {
    const params = new URLSearchParams();
    if (filters.event_id) params.set('event_id', String(filters.event_id));
    if (filters.status) params.set('status', String(filters.status));
    if (filters.period) params.set('period', String(filters.period));
    if (filters.date_from) params.set('date_from', String(filters.date_from));
    if (filters.date_to) params.set('date_to', String(filters.date_to));

    const url = form.dataset.chartDataUrl;
    if (!url) {
        throw new Error('Chart data URL is missing');
    }
    const query = params.toString();
    const response = await fetch(query ? `${url}?${query}` : url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Chart data failed (${response.status})`);
    }

    return response.json();
}

function alpineState(form) {
    const root = form.closest('[x-data]');
    if (!root) return null;
    if (typeof window.Alpine !== 'undefined' && typeof window.Alpine.$data === 'function') {
        return window.Alpine.$data(root);
    }
    return root.__x?.$data ?? null;
}

function readFilters(form) {
    const filters = {};
    new FormData(form).forEach((value, key) => {
        const match = key.match(/^filters\[([^\]]+)\]$/);
        if (!match || value === '') return;
        filters[match[1]] = value;
    });

    const state = alpineState(form);
    if (state?.filters && typeof state.filters === 'object') {
        Object.entries(state.filters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                filters[key] = value;
            }
        });
    }

    return filters;
}

async function downloadPdfFromForm(form, charts) {
    const formData = new FormData(form);

    charts.forEach((chart, index) => {
        formData.append(`charts[${index}][title]`, chart.title);
        formData.append(`charts[${index}][image]`, chart.image);
        if (chart.section) {
            formData.append(`charts[${index}][section]`, chart.section);
        }
    });

    const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`PDF export failed (${response.status})`);
    }

    const contentType = response.headers.get('Content-Type') || '';
    if (!contentType.includes('pdf')) {
        throw new Error('PDF export did not return a PDF');
    }

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filenameFromDisposition(
        response.headers.get('Content-Disposition'),
        'organizer-analytics.pdf',
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}

async function generateAnalyticsPdf(form, button) {
    const filters = readFilters(form);
    const section = String(filters.section || 'full');
    const originalLabel = button?.innerHTML;

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating…';
    }

    try {
        const payload = await fetchChartPayload(form, filters);
        renderOrganizerDashboardExportCharts(payload.dashboard ?? {}, filters.period);
        renderOrganizerReportExportCharts(payload.reports ?? {});
        await waitForPaint();

        const charts = captureDashboardChartImages(chartTargetsFor(section));
        await downloadPdfFromForm(form, charts);
    } finally {
        if (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (originalLabel) button.innerHTML = originalLabel;
        }
    }
}

function bindOrganizerReportBuilder() {
    const form = document.getElementById('organizer-report-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        const report = form.querySelector('[name="report"]')?.value
            ?? alpineState(form)?.selectedKey;
        const format = form.querySelector('[name="format"]')?.value
            ?? alpineState(form)?.format;

        if (report !== 'insights_analytics' || format !== 'pdf') {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('[type="submit"]');

        try {
            await generateAnalyticsPdf(form, button);
        } catch (error) {
            console.error(error);
            window.alert('Unable to export PDF with charts. Please try again.');
        }
    });
}

document.addEventListener('DOMContentLoaded', bindOrganizerReportBuilder);

import { captureDashboardChartImages } from './dashboard-pdf-export';
import { renderAdminDashboardExportCharts } from './admin-dashboard';
import { renderAdminReportExportCharts } from './admin-reports';

const ANALYTICS_CHARTS = [
    { canvasId: 'dashboardTopEventsChart', title: 'Top Events by Revenue', section: 'performance' },
    { canvasId: 'dashboardConversionFunnelChart', title: 'Conversion Funnel', section: 'performance' },
    { canvasId: 'adminSupportVolumeChart', title: 'Support volume', section: 'support' },
    { canvasId: 'adminSupportSlaChart', title: 'Open ticket SLA', section: 'support' },
    { canvasId: 'adminOverviewUserGrowthChart', title: 'User Growth', section: 'overview' },
    { canvasId: 'adminOverviewUserDistributionChart', title: 'User Distribution', section: 'overview' },
    { canvasId: 'adminOverviewRevenueTrendChart', title: 'Revenue Trend', section: 'overview' },
    { canvasId: 'adminOverviewTicketSalesChart', title: 'Ticket Sales Trend', section: 'overview' },
    { canvasId: 'adminOverviewEventsByCategoryChart', title: 'Events by Category', section: 'overview' },
    { canvasId: 'adminEventsStatusChart', title: 'Events by Status', section: 'events' },
    { canvasId: 'adminPlatformGrowthChart', title: 'Platform Growth', section: 'events' },
    { canvasId: 'adminTopCategoriesChart', title: 'Top Categories', section: 'events' },
    { canvasId: 'userStatusChart', title: 'Account Status Breakdown', section: 'users' },
    { canvasId: 'userRoleChart', title: 'Users by Role', section: 'users' },
    { canvasId: 'userRegistrationChart', title: 'User Registration Trend', section: 'users' },
    { canvasId: 'paymentStatusChart', title: 'Payment Status', section: 'payments' },
    { canvasId: 'paymentMethodChart', title: 'Payment Methods', section: 'payments' },
    { canvasId: 'paymentRevenueChart', title: 'Payment Revenue Trend', section: 'payments' },
];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function filenameFromDisposition(header, fallback = 'admin-analytics.pdf') {
    if (!header) return fallback;

    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const plainMatch = header.match(/filename=\"?([^\";]+)\"?/i);
    return plainMatch?.[1] ?? fallback;
}

function chartTargetsFor(section) {
    const key = section === 'admin' ? 'events' : section;
    if (!key || key === 'all' || key === 'full') {
        return ANALYTICS_CHARTS;
    }

    return ANALYTICS_CHARTS.filter((chart) => chart.section === key);
}

async function waitForPaint() {
    await new Promise((resolve) => {
        requestAnimationFrame(() => setTimeout(resolve, 400));
    });
}

async function fetchChartPayload(form, filters) {
    const params = new URLSearchParams();
    if (filters.organizer_id) params.set('organizer_id', String(filters.organizer_id));
    if (filters.event_id) params.set('event_id', String(filters.event_id));
    if (filters.cro_id) params.set('cro_id', String(filters.cro_id));
    if (filters.date_from) params.set('date_from', String(filters.date_from));
    if (filters.date_to) params.set('date_to', String(filters.date_to));
    if (filters.section) params.set('section', String(filters.section));

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
        'admin-analytics.pdf',
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}

async function generateAnalyticsPdf(form, button) {
    const filters = readFilters(form);
    const section = String(filters.section || 'all');
    const originalLabel = button?.innerHTML;

    if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating…';
    }

    try {
        const payload = await fetchChartPayload(form, filters);
        renderAdminDashboardExportCharts(payload.dashboard ?? {}, payload.supportCharts ?? {});
        renderAdminReportExportCharts(payload.reports ?? {});
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

function bindAdminReportBuilder() {
    const form = document.getElementById('admin-report-form');
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

document.addEventListener('DOMContentLoaded', bindAdminReportBuilder);

import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PieController,
    PointElement,
    Tooltip,
} from 'chart.js';
import {
    clearChartEmptyState,
    isEmptyChartInput,
    isEmptySeries,
    showChartEmptyState,
} from './report-empty-state';
import { bindDashboardPdfExportButtons } from './dashboard-pdf-export';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PieController,
    PointElement,
    Tooltip,
);

const palette = {
    indigo: 'rgb(79, 70, 229)',
    blue: 'rgb(37, 99, 235)',
    cyan: 'rgb(6, 182, 212)',
    emerald: 'rgb(16, 185, 129)',
    amber: 'rgb(245, 158, 11)',
    rose: 'rgb(244, 63, 94)',
    purple: 'rgb(147, 51, 234)',
    slate: 'rgb(100, 116, 139)',
};

const chartColors = [
    palette.indigo,
    palette.blue,
    palette.cyan,
    palette.emerald,
    palette.amber,
    palette.rose,
    palette.purple,
    palette.slate,
];

const defaultFont = {
    family: 'Figtree, ui-sans-serif, system-ui, sans-serif',
    size: 12,
};

function destroyChartOn(canvasOrId) {
    const canvas = typeof canvasOrId === 'string'
        ? document.getElementById(canvasOrId)
        : canvasOrId;
    if (!canvas) return;
    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();
}

function isEmptyLineChart(labels, datasets) {
    if (!Array.isArray(labels) || labels.length === 0) {
        return true;
    }

    if (!Array.isArray(datasets) || datasets.length === 0) {
        return true;
    }

    return datasets.every((dataset) => isEmptySeries(dataset.data ?? []));
}

function createLineChart(canvasId, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    destroyChartOn(canvas);

    if (isEmptyLineChart(labels, datasets)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: defaultFont, padding: fullscreen ? 20 : 16, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    destroyChartOn(canvas);

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: options.label ?? 'Count',
                data,
                backgroundColor: options.colors ?? chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.75)')),
                borderRadius: 8,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    destroyChartOn(canvas);

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: (options.colors ?? chartColors).slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: fullscreen ? 12 : 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: defaultFont, padding: fullscreen ? 18 : 14, usePointStyle: true },
                },
            },
        },
    });
}

const adminReportCardTargets = {
    userGrowth: 'adminOverviewUserGrowthChart',
    userDistribution: 'adminOverviewUserDistributionChart',
    revenueTrend: 'adminOverviewRevenueTrendChart',
    ticketSalesTrend: 'adminOverviewTicketSalesChart',
    eventsByCategory: 'adminOverviewEventsByCategoryChart',
    platformGrowth: 'adminPlatformGrowthChart',
    eventsStatus: 'adminEventsStatusChart',
    topCategories: 'adminTopCategoriesChart',
    userRoles: 'userRoleChart',
    userRegistration: 'userRegistrationChart',
    userStatus: 'userStatusChart',
    paymentRevenue: 'paymentRevenueChart',
    paymentStatus: 'paymentStatusChart',
    paymentMethod: 'paymentMethodChart',
    systemActivity: 'systemActivityChart',
    systemAuditActions: 'systemAuditActionChart',
};

function buildAdminReportChartPainters(data) {
    const chartLabels = data?.chartLabels ?? [];
    const shortLabels = data?.chartLabelsShort ?? chartLabels.map((label) => String(label).split(' ')[0]);
    const overview = data?.overview ?? {};
    const admin = data?.admin ?? {};
    const users = data?.users ?? {};
    const payments = data?.payments ?? {};
    const system = data?.system ?? {};
    const roleDistribution = overview.userDistribution ?? users.usersByRole ?? [];
    const revenueTrend = overview.revenueTrend ?? {};
    const ticketSalesTrend = overview.ticketSalesTrend ?? {
        weekly: overview.ticketSalesWeekly ?? [],
        monthly: [],
        yearly: [],
    };
    const eventsByCategory = overview.eventsByCategory ?? [];
    let ticketSalesRange = 'weekly';

    const categoryColors = [
        'rgba(79, 70, 229, 0.8)',
        'rgba(37, 99, 235, 0.8)',
        'rgba(6, 182, 212, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(244, 63, 94, 0.8)',
        'rgba(147, 51, 234, 0.8)',
        'rgba(100, 116, 139, 0.8)',
    ];

    const ticketSalesColor = 'rgba(37, 99, 235, 0.75)';

    function ticketSalesPointsFor(range = ticketSalesRange) {
        return ticketSalesTrend[range] ?? ticketSalesTrend.weekly ?? [];
    }

    function buildTicketSalesChart(canvasId, options = {}) {
        const range = options.range ?? ticketSalesRange;
        const points = ticketSalesPointsFor(range);
        const colors = points.map(() => ticketSalesColor);

        return createBarChart(
            canvasId,
            points.map((item) => item.label),
            points.map((item) => item.count),
            { ...options, label: 'Tickets sold', colors },
        );
    }

    const chartBuilders = {
        userGrowth: (canvasId, options = {}) => createLineChart(canvasId, shortLabels, [{
            label: 'New Registrations',
            data: overview.userGrowth ?? users.registrationTrend ?? [],
            borderColor: palette.indigo,
            backgroundColor: 'rgba(79, 70, 229, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: options.fullscreen ? 6 : 4,
            pointHoverRadius: options.fullscreen ? 9 : 6,
            borderWidth: options.fullscreen ? 3 : 2,
        }], options),

        userDistribution: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            roleDistribution.map((i) => i.label),
            roleDistribution.map((i) => i.count),
            options,
        ),

        revenueTrend: (canvasId, options = {}) => createLineChart(
            canvasId,
            revenueTrend.labels ?? shortLabels,
            [{
                label: 'Revenue (LKR)',
                data: revenueTrend.values ?? payments.revenueTrend ?? [],
                borderColor: palette.emerald,
                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: options.fullscreen ? 6 : 4,
                pointHoverRadius: options.fullscreen ? 9 : 6,
                borderWidth: options.fullscreen ? 3 : 2,
            }],
            options,
        ),

        ticketSalesTrend: (canvasId, options = {}) => buildTicketSalesChart(canvasId, options),
        ticketSalesWeekly: (canvasId, options = {}) => buildTicketSalesChart(canvasId, {
            ...options,
            range: options.range ?? 'weekly',
        }),

        eventsByCategory: (canvasId, options = {}) => createBarChart(
            canvasId,
            eventsByCategory.map((item) => item.label),
            eventsByCategory.map((item) => item.count),
            { ...options, label: 'Events', colors: categoryColors },
        ),

        platformGrowth: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [
            {
                label: 'New Users',
                data: admin.platformGrowth ?? [],
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.35,
                pointRadius: options.fullscreen ? 6 : 4,
                pointHoverRadius: options.fullscreen ? 9 : 6,
                borderWidth: options.fullscreen ? 3 : 2,
            },
            {
                label: 'New Events',
                data: admin.eventGrowth ?? [],
                borderColor: palette.cyan,
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                fill: true,
                tension: 0.35,
                pointRadius: options.fullscreen ? 6 : 4,
                pointHoverRadius: options.fullscreen ? 9 : 6,
                borderWidth: options.fullscreen ? 3 : 2,
            },
        ], options),

        eventsStatus: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            (admin.eventsByStatus ?? []).map((i) => i.label),
            (admin.eventsByStatus ?? []).map((i) => i.count),
            options,
        ),

        topCategories: (canvasId, options = {}) => createBarChart(
            canvasId,
            (admin.topCategories ?? []).map((i) => i.label),
            (admin.topCategories ?? []).map((i) => i.count),
            { ...options, label: 'Events' },
        ),

        userRoles: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            (users.usersByRole ?? []).map((i) => i.label),
            (users.usersByRole ?? []).map((i) => i.count),
            options,
        ),

        userRegistration: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [{
            label: 'Registrations',
            data: users.registrationTrend ?? [],
            borderColor: palette.blue,
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: options.fullscreen ? 6 : 4,
            pointHoverRadius: options.fullscreen ? 9 : 6,
            borderWidth: options.fullscreen ? 3 : 2,
        }], options),

        userStatus: (canvasId, options = {}) => createBarChart(
            canvasId,
            ['Active', 'Inactive', 'Verified', 'Unverified', 'Locked'],
            [
                users.activeUsers ?? 0,
                users.inactiveUsers ?? 0,
                users.verifiedUsers ?? 0,
                users.unverifiedUsers ?? 0,
                users.lockedUsers ?? 0,
            ],
            {
                ...options,
                label: 'Users',
                colors: [
                    'rgba(16, 185, 129, 0.75)',
                    'rgba(100, 116, 139, 0.75)',
                    'rgba(37, 99, 235, 0.75)',
                    'rgba(245, 158, 11, 0.75)',
                    'rgba(244, 63, 94, 0.75)',
                ],
            },
        ),

        paymentRevenue: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [{
            label: 'Revenue (LKR)',
            data: payments.revenueTrend ?? [],
            borderColor: palette.emerald,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: options.fullscreen ? 6 : 4,
            pointHoverRadius: options.fullscreen ? 9 : 6,
            borderWidth: options.fullscreen ? 3 : 2,
        }], options),

        paymentStatus: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            (payments.paymentsByStatus ?? []).map((i) => i.label),
            (payments.paymentsByStatus ?? []).map((i) => i.count),
            options,
        ),

        paymentMethod: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            (payments.paymentsByMethod ?? []).map((i) => i.label),
            (payments.paymentsByMethod ?? []).map((i) => i.count),
            options,
        ),

        systemActivity: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [{
            label: 'Audit Log Entries',
            data: system.activityTrend ?? [],
            borderColor: palette.purple,
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: options.fullscreen ? 6 : 4,
            pointHoverRadius: options.fullscreen ? 9 : 6,
            borderWidth: options.fullscreen ? 3 : 2,
        }], options),

        systemAuditActions: (canvasId, options = {}) => createBarChart(
            canvasId,
            (system.auditByAction ?? []).map((i) => i.label),
            (system.auditByAction ?? []).map((i) => i.count),
            { ...options, label: 'Actions' },
        ),
    };

    return {
        chartBuilders,
        buildTicketSalesChart,
        ticketSalesTrend,
        setTicketSalesRange(range) {
            ticketSalesRange = range;
        },
        getTicketSalesRange() {
            return ticketSalesRange;
        },
    };
}

export function renderAdminReportExportCharts(data) {
    if (!data) return;

    window.adminReportData = data;
    const { chartBuilders } = buildAdminReportChartPainters(data);
    Object.entries(adminReportCardTargets).forEach(([key, canvasId]) => {
        chartBuilders[key]?.(canvasId);
    });
}

function initAdminReports() {
    bindDashboardPdfExportButtons();

    const data = window.adminReportData;
    if (!data) return;

    const painters = buildAdminReportChartPainters(data);
    const { chartBuilders, buildTicketSalesChart, ticketSalesTrend, setTicketSalesRange, getTicketSalesRange } = painters;

    const chartInstances = Object.entries(adminReportCardTargets)
        .map(([key, canvasId]) => chartBuilders[key]?.(canvasId))
        .filter(Boolean);

    let fullscreenChart = null;
    let ticketSalesChartIndex = chartInstances.findIndex(
        (chart) => chart?.canvas?.id === 'adminOverviewTicketSalesChart',
    );

    function rebuildTicketSalesChart(range = getTicketSalesRange()) {
        setTicketSalesRange(range);
        const chart = buildTicketSalesChart('adminOverviewTicketSalesChart', { range });
        if (ticketSalesChartIndex >= 0) {
            chartInstances[ticketSalesChartIndex] = chart;
        } else if (chart) {
            ticketSalesChartIndex = chartInstances.push(chart) - 1;
        }
    }

    function destroyFullscreenChart() {
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }
        destroyChartOn('adminReportsChartFullscreen');
    }

    window.addEventListener('admin-reports-chart-expand', (event) => {
        const key = event.detail?.key;
        const builder = chartBuilders[key];
        if (!builder) return;

        destroyFullscreenChart();
        requestAnimationFrame(() => {
            fullscreenChart = builder('adminReportsChartFullscreen', {
                fullscreen: true,
                range: event.detail?.range ?? getTicketSalesRange(),
            });
        });
    });

    window.addEventListener('admin-reports-chart-collapse', destroyFullscreenChart);

    window.addEventListener('admin-reports-ticket-range', (event) => {
        const range = event.detail?.range;
        if (!range || !ticketSalesTrend[range]) return;
        rebuildTicketSalesChart(range);

        if (fullscreenChart && document.getElementById('adminReportsChartFullscreen')) {
            destroyFullscreenChart();
            requestAnimationFrame(() => {
                fullscreenChart = buildTicketSalesChart('adminReportsChartFullscreen', {
                    fullscreen: true,
                    range,
                });
            });
        }
    });

    const resizeCharts = () => {
        chartInstances.forEach((chart) => {
            if (chart && typeof chart.resize === 'function' && chart.canvas) {
                chart.resize();
            }
        });
        if (fullscreenChart) fullscreenChart.resize();
    };

    window.addEventListener('admin-reports-tab-changed', resizeCharts);
    window.addEventListener('resize', resizeCharts);
    window.addEventListener('dashboard-pdf-export-prepare', () => {
        requestAnimationFrame(() => {
            resizeCharts();
            setTimeout(resizeCharts, 120);
        });
    });
}

document.addEventListener('DOMContentLoaded', initAdminReports);

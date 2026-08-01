import {
    Chart,
    ArcElement,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
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
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
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

function initAdminReports() {
    const data = window.adminReportData;
    if (!data) return;

    const { chartLabels } = data;
    const shortLabels = data.chartLabelsShort ?? chartLabels.map((label) => String(label).split(' ')[0]);
    const overview = data.overview ?? {};
    const roleDistribution = overview.userDistribution ?? data.users?.usersByRole ?? [];
    const revenueTrend = overview.revenueTrend ?? {};
    const weeklyTickets = overview.ticketSalesWeekly ?? [];
    const eventsByCategory = overview.eventsByCategory ?? [];

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

    const weekColors = [
        'rgba(37, 99, 235, 0.75)',
        'rgba(79, 70, 229, 0.75)',
        'rgba(6, 182, 212, 0.75)',
        'rgba(16, 185, 129, 0.75)',
    ];

    const chartBuilders = {
        userGrowth: (canvasId, options = {}) => createLineChart(canvasId, shortLabels, [{
            label: 'New Registrations',
            data: overview.userGrowth ?? data.users?.registrationTrend ?? [],
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
                data: revenueTrend.values ?? data.payments?.revenueTrend ?? [],
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

        ticketSalesWeekly: (canvasId, options = {}) => createBarChart(
            canvasId,
            weeklyTickets.map((item) => item.label),
            weeklyTickets.map((item) => item.count),
            { ...options, label: 'Tickets sold', colors: weekColors },
        ),

        eventsByCategory: (canvasId, options = {}) => createBarChart(
            canvasId,
            eventsByCategory.map((item) => item.label),
            eventsByCategory.map((item) => item.count),
            { ...options, label: 'Events', colors: categoryColors },
        ),

        platformGrowth: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [
            {
                label: 'New Users',
                data: data.admin.platformGrowth,
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
                data: data.admin.eventGrowth,
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
            data.admin.eventsByStatus.map((i) => i.label),
            data.admin.eventsByStatus.map((i) => i.count),
            options,
        ),

        topCategories: (canvasId, options = {}) => createBarChart(
            canvasId,
            data.admin.topCategories.map((i) => i.label),
            data.admin.topCategories.map((i) => i.count),
            { ...options, label: 'Events' },
        ),

        userRoles: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            data.users.usersByRole.map((i) => i.label),
            data.users.usersByRole.map((i) => i.count),
            options,
        ),

        userRegistration: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [{
            label: 'Registrations',
            data: data.users.registrationTrend,
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
                data.users.activeUsers,
                data.users.inactiveUsers,
                data.users.verifiedUsers,
                data.users.unverifiedUsers,
                data.users.lockedUsers,
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
            data: data.payments.revenueTrend,
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
            data.payments.paymentsByStatus.map((i) => i.label),
            data.payments.paymentsByStatus.map((i) => i.count),
            options,
        ),

        paymentMethod: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            data.payments.paymentsByMethod.map((i) => i.label),
            data.payments.paymentsByMethod.map((i) => i.count),
            options,
        ),

        systemActivity: (canvasId, options = {}) => createLineChart(canvasId, chartLabels, [{
            label: 'Audit Log Entries',
            data: data.system.activityTrend,
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
            data.system.auditByAction.map((i) => i.label),
            data.system.auditByAction.map((i) => i.count),
            { ...options, label: 'Actions' },
        ),
    };

    const cardTargets = {
        userGrowth: 'adminOverviewUserGrowthChart',
        userDistribution: 'adminOverviewUserDistributionChart',
        revenueTrend: 'adminOverviewRevenueTrendChart',
        ticketSalesWeekly: 'adminOverviewTicketSalesChart',
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

    const chartInstances = Object.entries(cardTargets)
        .map(([key, canvasId]) => chartBuilders[key]?.(canvasId))
        .filter(Boolean);

    let fullscreenChart = null;

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
            fullscreenChart = builder('adminReportsChartFullscreen', { fullscreen: true });
        });
    });

    window.addEventListener('admin-reports-chart-collapse', destroyFullscreenChart);

    const resizeCharts = () => {
        chartInstances.forEach((chart) => chart.resize());
        if (fullscreenChart) fullscreenChart.resize();
    };

    window.addEventListener('admin-reports-tab-changed', resizeCharts);
    window.addEventListener('resize', resizeCharts);

    bindDashboardPdfExportButtons();
}

document.addEventListener('DOMContentLoaded', initAdminReports);

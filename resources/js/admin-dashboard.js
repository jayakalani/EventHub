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
};

const chartColors = [
    palette.indigo,
    palette.blue,
    palette.cyan,
    palette.emerald,
    palette.amber,
    palette.rose,
    palette.purple,
];

const roleColors = {
    Administrator: palette.indigo,
    Organizer: palette.blue,
    CRO: palette.amber,
    Attendee: palette.cyan,
};

const defaultFont = {
    family: 'Figtree, ui-sans-serif, system-ui, sans-serif',
    size: 12,
};

function formatCompactLkr(value) {
    const amount = Number(value) || 0;

    if (amount >= 1_000_000) {
        return `LKR ${(amount / 1_000_000).toFixed(amount % 1_000_000 === 0 ? 0 : 1)}M`;
    }

    if (amount >= 1_000) {
        return `LKR ${Math.round(amount / 1_000)}K`;
    }

    return `LKR ${amount.toLocaleString()}`;
}

function destroyChartOn(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();
}

function fontFor(fullscreen = false) {
    return {
        ...defaultFont,
        size: fullscreen ? 14 : 12,
    };
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

    destroyChartOn(canvasId);

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
                    labels: { font: fontFor(fullscreen), padding: fullscreen ? 20 : 16, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: fullscreen ? 15 : 13, weight: 'bold' },
                    bodyFont: { size: fullscreen ? 14 : 12 },
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                    callbacks: options.tooltipCallbacks ?? {},
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: fontFor(fullscreen) },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: {
                        font: fontFor(fullscreen),
                        ...(options.yTicks ?? {}),
                    },
                },
            },
        },
    });
}

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    destroyChartOn(canvasId);

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    const fullscreen = Boolean(options.fullscreen);
    const horizontal = Boolean(options.horizontal);
    const font = fontFor(fullscreen);
    const truncateAt = fullscreen ? 42 : 22;

    const categoryTicks = {
        font,
        callback(value) {
            const label = this.getLabelForValue(value);
            if (!label) return '';
            return label.length > truncateAt ? `${label.slice(0, truncateAt - 1)}…` : label;
        },
    };

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: options.label ?? 'Count',
                data,
                backgroundColor: options.colors ?? chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.8)')),
                borderRadius: 8,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: fullscreen ? 15 : 13, weight: 'bold' },
                    bodyFont: { size: fullscreen ? 14 : 12 },
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                    callbacks: options.tooltipCallbacks ?? {},
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: horizontal
                        ? { color: 'rgba(148, 163, 184, 0.2)' }
                        : { display: false },
                    ticks: {
                        font,
                        ...(horizontal ? (options.valueTicks ?? {}) : { maxRotation: 45, minRotation: 0 }),
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: horizontal
                        ? { display: false }
                        : { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: {
                        font,
                        ...(horizontal ? categoryTicks : { precision: 0 }),
                    },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data, colors = null, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    destroyChartOn(canvasId);

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
                backgroundColor: colors ?? chartColors.slice(0, labels.length),
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
                    labels: { font: fontFor(fullscreen), padding: fullscreen ? 18 : 14, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                    callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((sum, value) => sum + Number(value), 0);
                            const value = Number(context.raw) || 0;
                            const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${context.label}: ${value} (${percent}%)`;
                        },
                    },
                },
            },
        },
    });
}

function createPieChart(canvasId, labels, data, colors = null, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    destroyChartOn(canvasId);

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'pie',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors ?? chartColors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: fullscreen ? 10 : 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: fontFor(fullscreen), padding: fullscreen ? 16 : 10, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: fullscreen ? 14 : 10,
                    cornerRadius: 8,
                },
            },
        },
    });
}

const adminDashboardCardTargets = {
    topEvents: 'dashboardTopEventsChart',
    conversionFunnel: 'dashboardConversionFunnelChart',
    supportVolume: 'adminSupportVolumeChart',
    supportSla: 'adminSupportSlaChart',
};

function buildAdminDashboardChartPainters(data, supportCharts = {}) {
    const topEventRows = data?.topEvents ?? [];
    const funnelRows = data?.conversionFunnel ?? [];
    const volumeLabels = supportCharts.volume?.labels ?? [];
    const slaRows = supportCharts.sla ?? [];

    return {
        topEvents: (canvasId, options = {}) => createBarChart(
            canvasId,
            topEventRows.map((item) => item.name),
            topEventRows.map((item) => item.net),
            {
                ...options,
                horizontal: true,
                label: 'Net revenue (LKR)',
                colors: ['rgba(16, 185, 129, 0.85)'],
                tooltipCallbacks: {
                    label(context) {
                        const row = topEventRows[context.dataIndex] ?? {};
                        const tickets = Number(row.tickets || 0).toLocaleString();
                        return ` ${formatCompactLkr(context.raw)} · ${tickets} tickets`;
                    },
                },
                valueTicks: {
                    callback(value) {
                        return formatCompactLkr(value).replace('LKR ', '');
                    },
                },
            },
        ),
        conversionFunnel: (canvasId, options = {}) => createBarChart(
            canvasId,
            funnelRows.map((item) => item.label),
            funnelRows.map((item) => item.count),
            {
                ...options,
                horizontal: true,
                label: 'Count',
                colors: [
                    'rgba(6, 182, 212, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                ],
                tooltipCallbacks: {
                    label(context) {
                        const row = funnelRows[context.dataIndex] ?? {};
                        const rate = row.rate == null ? '' : ` · ${row.rate}% of previous`;
                        return ` ${Number(context.raw || 0).toLocaleString()}${rate}`;
                    },
                },
            },
        ),
        supportVolume: (canvasId, options = {}) => createLineChart(canvasId, volumeLabels, [
            {
                label: 'Inquiries',
                data: supportCharts.volume?.inquiries ?? [],
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: options.fullscreen ? 6 : 4,
                pointHoverRadius: options.fullscreen ? 8 : 6,
                pointBackgroundColor: palette.indigo,
                borderWidth: options.fullscreen ? 3 : 2,
            },
            {
                label: 'Complaints',
                data: supportCharts.volume?.complaints ?? [],
                borderColor: palette.rose,
                backgroundColor: 'rgba(244, 63, 94, 0.10)',
                fill: true,
                tension: 0.35,
                pointRadius: options.fullscreen ? 6 : 4,
                pointHoverRadius: options.fullscreen ? 8 : 6,
                pointBackgroundColor: palette.rose,
                borderWidth: options.fullscreen ? 3 : 2,
            },
        ], options),
        supportSla: (canvasId, options = {}) => createBarChart(
            canvasId,
            slaRows.map((item) => item.label),
            slaRows.map((item) => item.count),
            {
                ...options,
                horizontal: true,
                label: 'Open tickets',
                colors: slaRows.map((item) => item.color ?? 'rgba(148, 163, 184, 0.85)'),
            },
        ),
    };
}

export function renderAdminDashboardExportCharts(data, supportCharts = {}) {
    if (!data) return;

    window.adminDashboardData = data;
    window.adminSupportCharts = supportCharts;

    const chartBuilders = buildAdminDashboardChartPainters(data, supportCharts);
    Object.entries(adminDashboardCardTargets).forEach(([key, canvasId]) => {
        chartBuilders[key]?.(canvasId);
    });
}

function initAdminDashboard() {
    bindDashboardPdfExportButtons();

    const data = window.adminDashboardData;
    if (!data) return;

    const supportCharts = window.adminSupportCharts ?? {};
    const chartBuilders = buildAdminDashboardChartPainters(data, supportCharts);

    const chartInstances = Object.entries(adminDashboardCardTargets)
        .map(([key, canvasId]) => chartBuilders[key]?.(canvasId))
        .filter(Boolean);

    let fullscreenChart = null;

    function destroyFullscreenChart() {
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }

        destroyChartOn('adminChartFullscreen');
    }

    window.addEventListener('admin-chart-expand', (event) => {
        const key = event.detail?.key;
        const builder = chartBuilders[key];
        if (!builder) return;

        destroyFullscreenChart();

        requestAnimationFrame(() => {
            fullscreenChart = builder('adminChartFullscreen', { fullscreen: true });
        });
    });

    window.addEventListener('admin-chart-collapse', destroyFullscreenChart);

    window.addEventListener('resize', () => {
        chartInstances.forEach((chart) => chart.resize());
        if (fullscreenChart) fullscreenChart.resize();
    });

    window.addEventListener('dashboard-pdf-export-prepare', () => {
        chartInstances.forEach((chart) => {
            try {
                chart.resize();
                chart.update('none');
            } catch (error) {
                console.warn('Unable to refresh admin chart before PDF export', error);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initAdminDashboard);

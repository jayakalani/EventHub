import {
    Chart,
    ArcElement,
    BarElement,
    BarController,
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
import { bindDashboardPdfExportButtons } from './dashboard-pdf-export';

Chart.register(
    ArcElement,
    BarElement,
    BarController,
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

const defaultFont = {
    family: 'Figtree, ui-sans-serif, system-ui, sans-serif',
    size: 12,
};

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

function createSupportTrendChart(canvasId, periodData, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const labels = periodData?.labels ?? [];
    if (!labels.length) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Inquiries',
                    data: periodData.inquiries ?? [],
                    borderColor: palette.indigo,
                    backgroundColor: 'rgba(79, 70, 229, 0.10)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: fullscreen ? 5 : 4,
                    pointHoverRadius: fullscreen ? 8 : 7,
                    borderWidth: fullscreen ? 3 : 2,
                },
                {
                    label: 'Complaints',
                    data: periodData.complaints ?? [],
                    borderColor: palette.rose,
                    backgroundColor: 'rgba(244, 63, 94, 0.10)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: fullscreen ? 5 : 4,
                    pointHoverRadius: fullscreen ? 8 : 7,
                    borderWidth: fullscreen ? 3 : 2,
                },
                {
                    label: 'Refunds',
                    data: periodData.refunds ?? [],
                    borderColor: palette.amber,
                    backgroundColor: 'rgba(245, 158, 11, 0.10)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: fullscreen ? 5 : 4,
                    pointHoverRadius: fullscreen ? 8 : 7,
                    borderWidth: fullscreen ? 3 : 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { font: fontFor(fullscreen), padding: fullscreen ? 18 : 12, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: fullscreen ? 15 : 13, weight: 'bold' },
                    bodyFont: { size: fullscreen ? 14 : 12 },
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: fontFor(fullscreen) },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: fontFor(fullscreen),
                        precision: 0,
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    const hasData = data.some((value) => Number(value) > 0);
    if (!hasData && !options.force) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);
    const colors = options.colors ?? [
        palette.emerald,
        palette.amber,
        palette.blue,
        palette.rose,
        palette.indigo,
    ];

    return new Chart(canvas, {
        type: options.type ?? 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: fullscreen ? 12 : 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: options.type === 'pie' ? 0 : '65%',
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

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);
    const colors = (options.colors ?? chartColors).map((c) => (
        c.includes('rgba') ? c : c.replace('rgb', 'rgba').replace(')', ', 0.85)')
    ));

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: options.label ?? 'Cases',
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: fullscreen ? 56 : 42,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: Boolean(options.showLegend) },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: fontFor(fullscreen),
                        maxRotation: fullscreen ? 0 : 25,
                        minRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: fontFor(fullscreen),
                        precision: 0,
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                },
            },
        },
    });
}

function createStackedBarChart(canvasId, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    const hasData = datasets.some((dataset) => (dataset.data ?? []).some((value) => Number(value) > 0));
    if (!hasData && !options.force) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: datasets.map((dataset) => ({
                ...dataset,
                stack: options.stack ?? 'attendance',
                borderRadius: 6,
                borderSkipped: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: options.horizontal ? 'y' : 'x',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: fontFor(fullscreen), padding: fullscreen ? 18 : 14, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: fullscreen ? 14 : 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    grid: options.horizontal
                        ? { color: 'rgba(148, 163, 184, 0.2)' }
                        : { display: false },
                    ticks: { font: fontFor(fullscreen), precision: 0 },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: options.horizontal
                        ? { display: false }
                        : { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: fontFor(fullscreen), precision: 0 },
                },
            },
        },
    });
}

function buildCroDashboardChartRuntime(data) {
    const periods = data.charts?.periods ?? {};
    let currentPeriod = data.charts?.defaultPeriod ?? 'week';
    const complaintStatus = data.charts?.complaintStatus ?? { labels: [], counts: [] };
    const supportCategories = data.charts?.supportCategories ?? { labels: [], counts: [] };
    const satisfactionDistribution = data.charts?.satisfactionDistribution ?? { labels: [], counts: [] };
    const attendance = data.attendance ?? {};

    const chartBuilders = {
        supportTrend: (canvasId, options = {}) => createSupportTrendChart(
            canvasId,
            periods[options.period ?? currentPeriod] ?? { labels: [] },
            options,
        ),
        complaintStatus: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            complaintStatus.labels ?? [],
            complaintStatus.counts ?? [],
            {
                ...options,
                type: 'pie',
                colors: [palette.emerald, palette.amber, palette.blue],
            },
        ),
        supportCategories: (canvasId, options = {}) => createBarChart(
            canvasId,
            supportCategories.labels ?? [],
            supportCategories.counts ?? [],
            options,
        ),
        satisfactionDistribution: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            satisfactionDistribution.labels ?? [],
            satisfactionDistribution.counts ?? [],
            {
                ...options,
                type: 'pie',
                colors: [palette.emerald, palette.cyan, palette.amber, palette.rose, palette.indigo],
            },
        ),
        attendanceBreakdown: (canvasId, options = {}) => {
            const breakdown = attendance.breakdown ?? [];
            const colors = {
                checked_in: 'rgba(16, 185, 129, 0.85)',
                no_shows: 'rgba(244, 63, 94, 0.85)',
                awaiting: 'rgba(245, 158, 11, 0.85)',
            };
            return createDoughnutChart(
                canvasId,
                breakdown.map((item) => item.label),
                breakdown.map((item) => item.count),
                {
                    ...options,
                    type: 'doughnut',
                    colors: breakdown.map((item) => colors[item.key] ?? 'rgba(148, 163, 184, 0.85)'),
                },
            );
        },
        checkInTiming: (canvasId, options = {}) => {
            const timing = attendance.checkInTiming ?? [];
            return createBarChart(
                canvasId,
                timing.map((item) => item.label),
                timing.map((item) => item.count),
                {
                    ...options,
                    label: 'Check-ins',
                    colors: [
                        'rgba(100, 116, 139, 0.7)',
                        'rgba(79, 70, 229, 0.7)',
                        'rgba(37, 99, 235, 0.75)',
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(244, 63, 94, 0.75)',
                        'rgba(147, 51, 234, 0.7)',
                    ],
                },
            );
        },
        attendanceByEvent: (canvasId, options = {}) => {
            const rows = (attendance.byEvent ?? []).slice(0, 12);
            return createStackedBarChart(
                canvasId,
                rows.map((item) => item.name),
                [
                    {
                        label: 'Checked in',
                        data: rows.map((item) => Number(item.checked_in || 0)),
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    },
                    {
                        label: 'No-shows',
                        data: rows.map((item) => Number(item.no_shows || 0)),
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    },
                    {
                        label: 'Awaiting check-in',
                        data: rows.map((item) => Number(item.awaiting_check_in || 0)),
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    },
                ],
                {
                    ...options,
                    stack: 'attendance',
                    horizontal: true,
                },
            );
        },
    };

    const sectionCharts = {
        support: null,
        performance: null,
        attendance: null,
    };

    let supportTrendChart = null;
    let fullscreenChart = null;

    function resizeSectionCharts(section) {
        const charts = sectionCharts[section] ?? [];
        charts.forEach((chart) => chart?.resize?.());
        if (section === 'support') {
            supportTrendChart?.resize?.();
        }
    }

    function ensureSectionCharts(section, options = {}) {
        if (section === 'support' && (!sectionCharts.support?.length || options.force)) {
            supportTrendChart = chartBuilders.supportTrend('croSupportTrendChart', options);
            sectionCharts.support = [
                supportTrendChart,
                chartBuilders.complaintStatus('croComplaintStatusChart', options),
                chartBuilders.supportCategories('croSupportCategoriesChart', options),
            ].filter(Boolean);
        }

        if (section === 'performance' && (!sectionCharts.performance?.length || options.force)) {
            sectionCharts.performance = [
                chartBuilders.satisfactionDistribution('croSatisfactionDistributionChart', options),
            ].filter(Boolean);
        }

        if (section === 'attendance' && (!sectionCharts.attendance?.length || options.force)) {
            sectionCharts.attendance = [
                chartBuilders.attendanceBreakdown('croAttendanceBreakdownChart', options),
                chartBuilders.checkInTiming('croCheckInTimingChart', options),
                chartBuilders.attendanceByEvent('croAttendanceByEventChart', options),
            ].filter(Boolean);
        }

        requestAnimationFrame(() => {
            resizeSectionCharts(section);
            requestAnimationFrame(() => resizeSectionCharts(section));
        });
    }

    return {
        currentPeriod,
        periods,
        chartBuilders,
        ensureSectionCharts,
        resizeAll() {
            Object.keys(sectionCharts).forEach((section) => resizeSectionCharts(section));
        },
        setPeriod(period) {
            if (periods[period]) {
                currentPeriod = period;
            }
        },
    };
}

function initCroDashboard() {
    const data = window.croDashboardData;
    if (!data) return;

    const runtime = buildCroDashboardChartRuntime(data);
    const { periods, chartBuilders, ensureSectionCharts, resizeAll } = runtime;
    let currentPeriod = runtime.currentPeriod;
    let fullscreenChart = null;

    document.querySelectorAll('[data-cro-period-label]').forEach((el) => {
        el.textContent = periods[currentPeriod]?.label ?? currentPeriod;
    });

    function destroyFullscreenChart() {
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }

        destroyChartOn('croChartFullscreen');
    }

    window.addEventListener('cro-chart-expand', (event) => {
        const key = event.detail?.key;
        const builder = chartBuilders[key];
        if (!builder) return;

        destroyFullscreenChart();

        requestAnimationFrame(() => {
            fullscreenChart = builder('croChartFullscreen', {
                fullscreen: true,
                period: currentPeriod,
            });
        });
    });

    window.addEventListener('cro-chart-collapse', destroyFullscreenChart);

    window.addEventListener('resize', () => {
        resizeAll();
        if (fullscreenChart) fullscreenChart.resize();
    });

    window.addEventListener('cro-dashboard-section-changed', (event) => {
        const section = event.detail?.section;
        if (!section) return;
        ensureSectionCharts(section);
    });

    window.addEventListener('dashboard-pdf-export-prepare', () => {
        ['support', 'performance', 'attendance'].forEach((section) => ensureSectionCharts(section, { force: true }));
    });

    const initialHash = (window.location.hash || '').replace('#', '');
    const initialSection = ['today', 'attendance', 'performance', 'support', 'inquiry', 'complaints'].includes(initialHash)
        ? initialHash
        : 'today';
    ensureSectionCharts(initialSection);

    bindDashboardPdfExportButtons();
}

export function renderCroDashboardExportCharts(data) {
    if (!data) return;

    const runtime = buildCroDashboardChartRuntime(data);
    ['support', 'performance', 'attendance'].forEach((section) => {
        runtime.ensureSectionCharts(section, { force: true });
    });
}

document.addEventListener('DOMContentLoaded', initCroDashboard);

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

const statusColors = [
    'rgba(245, 158, 11, 0.85)',
    'rgba(37, 99, 235, 0.85)',
    'rgba(16, 185, 129, 0.85)',
    'rgba(100, 116, 139, 0.85)',
];

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

function destroyChartOn(canvas) {
    const node = typeof canvas === 'string' ? document.getElementById(canvas) : canvas;
    if (!node) return;
    const existing = Chart.getChart(node);
    if (existing) existing.destroy();
}

function createLineChart(canvasId, labels, datasets, yMax = null) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;
    destroyChartOn(canvas);

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
                    labels: { font: defaultFont, padding: 16, usePointStyle: true },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: defaultFont } },
                y: {
                    beginAtZero: true,
                    max: yMax,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createBarChart(canvasId, labels, datasets, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;
    destroyChartOn(canvas);

    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: options.horizontal ? 'y' : 'x',
            plugins: {
                legend: {
                    display: datasets.length > 1,
                    position: 'bottom',
                    labels: { font: defaultFont, padding: 12, usePointStyle: true },
                },
            },
            scales: {
                x: {
                    stacked: options.stacked ?? false,
                    beginAtZero: true,
                    grid: options.horizontal ? { color: 'rgba(148, 163, 184, 0.2)' } : { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    stacked: options.stacked ?? false,
                    beginAtZero: true,
                    grid: options.horizontal ? { display: false } : { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data, colors = null, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;
    destroyChartOn(canvas);

    const hasData = data.some((value) => Number(value) > 0);
    if (!hasData) return null;

    return new Chart(canvas, {
        type: options.type ?? 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors ?? chartColors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: options.type === 'pie' ? 0 : '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: defaultFont, padding: 14, usePointStyle: true },
                },
                tooltip: {
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

function initCroReports() {
    const data = window.croReportData;
    if (!data) return;

    const { chartLabels } = data;
    const charts = [];
    let initialized = false;

    const register = (chart) => {
        if (chart) charts.push(chart);
        return chart;
    };

    const inquiries = data.inquiries ?? {};
    const complaints = data.complaints ?? {};
    const satisfaction = data.satisfaction ?? {};
    const resolutionTrend = inquiries.resolutionTrend ?? { submitted: [], resolved: [], resolutionRate: [] };
    const responseTimeTrend = inquiries.responseTimeTrend ?? [];
    const categoryBreakdown = complaints.categoryBreakdown ?? complaints.typeBreakdown ?? [];
    const csatDistribution = satisfaction.distribution ?? { labels: [], counts: [] };
    const csatTrend = satisfaction.trend ?? [];

    function buildCharts() {
        register(createDoughnutChart(
            'inquiryStatusChart',
            (inquiries.statusBreakdown ?? []).map((i) => i.label),
            (inquiries.statusBreakdown ?? []).map((i) => i.count),
            statusColors,
        ));

        register(createLineChart('inquiryResolutionTrendChart', chartLabels, [
            {
                label: 'Submitted',
                data: resolutionTrend.submitted,
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
            {
                label: 'Resolved',
                data: resolutionTrend.resolved,
                borderColor: palette.emerald,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
        ]));

        register(createBarChart(
            'inquiryResponseTimeChart',
            chartLabels,
            [{
                label: 'Avg minutes',
                data: responseTimeTrend.map((value) => value ?? 0),
                backgroundColor: 'rgba(6, 182, 212, 0.75)',
                borderRadius: 8,
            }],
        ));

        const topEvents = inquiries.byEvent ?? [];
        register(createBarChart(
            'inquiryByEventChart',
            topEvents.map((i) => i.label),
            [{
                label: 'Inquiries',
                data: topEvents.map((i) => i.count),
                backgroundColor: 'rgba(79, 70, 229, 0.75)',
                borderRadius: 8,
            }],
            { horizontal: true },
        ));

        register(createDoughnutChart(
            'complaintStatusChart',
            (complaints.statusBreakdown ?? []).map((i) => i.label),
            (complaints.statusBreakdown ?? []).map((i) => i.count),
            statusColors,
        ));

        register(createDoughnutChart(
            'complaintTypeChart',
            (complaints.typeBreakdown ?? []).map((i) => i.label),
            (complaints.typeBreakdown ?? []).map((i) => i.count),
        ));

        register(createDoughnutChart(
            'complaintCategoryPieChart',
            categoryBreakdown.map((i) => i.label),
            categoryBreakdown.map((i) => i.count),
            null,
            { type: 'pie' },
        ));

        register(createLineChart('complaintSubmissionsChart', chartLabels, [{
            label: 'Complaints Submitted',
            data: complaints.submissionsTrend ?? [],
            borderColor: palette.rose,
            backgroundColor: 'rgba(244, 63, 94, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        }]));

        const statusByType = complaints.statusByType ?? [];
        register(createBarChart(
            'complaintStatusByTypeChart',
            statusByType.map((i) => i.label),
            [
                {
                    label: 'Open',
                    data: statusByType.map((i) => i.open),
                    backgroundColor: statusColors[0],
                    borderRadius: 4,
                },
                {
                    label: 'In Progress',
                    data: statusByType.map((i) => i.in_progress),
                    backgroundColor: statusColors[1],
                    borderRadius: 4,
                },
                {
                    label: 'Resolved',
                    data: statusByType.map((i) => i.resolved),
                    backgroundColor: statusColors[2],
                    borderRadius: 4,
                },
                {
                    label: 'Closed',
                    data: statusByType.map((i) => i.closed),
                    backgroundColor: statusColors[3],
                    borderRadius: 4,
                },
            ],
            { stacked: true },
        ));

        // Overview charts (reports page)
        register(createLineChart('overviewInquiryResolutionChart', chartLabels, [
            {
                label: 'Submitted',
                data: resolutionTrend.submitted,
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 7,
            },
            {
                label: 'Resolved',
                data: resolutionTrend.resolved,
                borderColor: palette.emerald,
                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 7,
            },
        ]));

        register(createBarChart(
            'overviewResponseTimeChart',
            chartLabels,
            [{
                label: 'Avg minutes',
                data: responseTimeTrend.map((value) => value ?? 0),
                backgroundColor: 'rgba(79, 70, 229, 0.75)',
                borderRadius: 8,
            }],
        ));

        register(createDoughnutChart(
            'overviewComplaintCategoriesChart',
            categoryBreakdown.map((i) => i.label),
            categoryBreakdown.map((i) => i.count),
            null,
            { type: 'pie' },
        ));

        register(createLineChart('overviewResolutionRateChart', chartLabels, [{
            label: 'Resolution Rate (%)',
            data: resolutionTrend.resolutionRate,
            borderColor: palette.cyan,
            backgroundColor: 'rgba(6, 182, 212, 0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
        }], 100));

        register(createLineChart('overviewCsatTrendChart', chartLabels, [{
            label: 'Avg rating',
            data: csatTrend.map((value) => value ?? null),
            borderColor: palette.amber,
            backgroundColor: 'rgba(245, 158, 11, 0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            spanGaps: true,
        }], 5));

        register(createDoughnutChart(
            'overviewCsatDistributionChart',
            csatDistribution.labels ?? [],
            csatDistribution.counts ?? [],
            [
                'rgba(16, 185, 129, 0.85)',
                'rgba(6, 182, 212, 0.85)',
                'rgba(245, 158, 11, 0.85)',
                'rgba(244, 63, 94, 0.85)',
                'rgba(79, 70, 229, 0.85)',
            ],
            { type: 'pie' },
        ));
    }

    function ensureCharts() {
        if (!initialized) {
            buildCharts();
            initialized = true;
        }

        requestAnimationFrame(() => {
            charts.forEach((chart) => chart.resize());
            requestAnimationFrame(() => charts.forEach((chart) => chart.resize()));
        });
    }

    const resizeCharts = () => charts.forEach((chart) => chart.resize());
    window.addEventListener('cro-reports-tab-changed', () => ensureCharts());
    window.addEventListener('dashboard-pdf-export-prepare', () => ensureCharts());
    window.addEventListener('cro-dashboard-section-changed', (event) => {
        if (['inquiry', 'complaints'].includes(event.detail?.section)) {
            ensureCharts();
        } else {
            resizeCharts();
        }
    });
    window.addEventListener('resize', resizeCharts);

    const isDashboard = Boolean(window.croDashboardData);
    if (isDashboard) {
        const hash = (window.location.hash || '').replace('#', '');
        if (['inquiry', 'complaints'].includes(hash)) {
            ensureCharts();
        }
    } else {
        ensureCharts();
    }

    bindDashboardPdfExportButtons();
}

export function renderCroReportExportCharts(data) {
    if (!data) return;

    const chartLabels = data.chartLabels ?? [];
    const inquiries = data.inquiries ?? {};
    const complaints = data.complaints ?? {};
    const resolutionTrend = inquiries.resolutionTrend ?? { submitted: [], resolved: [], resolutionRate: [] };
    const responseTimeTrend = inquiries.responseTimeTrend ?? [];
    const categoryBreakdown = complaints.categoryBreakdown ?? complaints.typeBreakdown ?? [];

    createDoughnutChart(
        'inquiryStatusChart',
        (inquiries.statusBreakdown ?? []).map((item) => item.label),
        (inquiries.statusBreakdown ?? []).map((item) => item.count),
        statusColors,
    );

    createLineChart('inquiryResolutionTrendChart', chartLabels, [
        {
            label: 'Submitted',
            data: resolutionTrend.submitted,
            borderColor: palette.indigo,
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
        {
            label: 'Resolved',
            data: resolutionTrend.resolved,
            borderColor: palette.emerald,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]);

    createBarChart(
        'inquiryResponseTimeChart',
        chartLabels,
        [{
            label: 'Avg minutes',
            data: responseTimeTrend.map((value) => value ?? 0),
            backgroundColor: 'rgba(6, 182, 212, 0.75)',
            borderRadius: 8,
        }],
    );

    const topEvents = inquiries.byEvent ?? [];
    createBarChart(
        'inquiryByEventChart',
        topEvents.map((item) => item.label),
        [{
            label: 'Inquiries',
            data: topEvents.map((item) => item.count),
            backgroundColor: 'rgba(79, 70, 229, 0.75)',
            borderRadius: 8,
        }],
        { horizontal: true },
    );

    createDoughnutChart(
        'complaintTypeChart',
        (complaints.typeBreakdown ?? []).map((item) => item.label),
        (complaints.typeBreakdown ?? []).map((item) => item.count),
    );

    createDoughnutChart(
        'complaintCategoryPieChart',
        categoryBreakdown.map((item) => item.label),
        categoryBreakdown.map((item) => item.count),
        null,
        { type: 'pie' },
    );

    createLineChart('complaintSubmissionsChart', chartLabels, [{
        label: 'Complaints Submitted',
        data: complaints.submissionsTrend ?? [],
        borderColor: palette.rose,
        backgroundColor: 'rgba(244, 63, 94, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]);

    const statusByType = complaints.statusByType ?? [];
    createBarChart(
        'complaintStatusByTypeChart',
        statusByType.map((item) => item.label),
        [
            {
                label: 'Open',
                data: statusByType.map((item) => item.open),
                backgroundColor: statusColors[0],
                borderRadius: 4,
            },
            {
                label: 'In Progress',
                data: statusByType.map((item) => item.in_progress),
                backgroundColor: statusColors[1],
                borderRadius: 4,
            },
            {
                label: 'Resolved',
                data: statusByType.map((item) => item.resolved),
                backgroundColor: statusColors[2],
                borderRadius: 4,
            },
            {
                label: 'Closed',
                data: statusByType.map((item) => item.closed),
                backgroundColor: statusColors[3],
                borderRadius: 4,
            },
        ],
        { stacked: true },
    );
}

document.addEventListener('DOMContentLoaded', initCroReports);

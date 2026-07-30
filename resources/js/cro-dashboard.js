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
    PointElement,
    Tooltip,
} from 'chart.js';

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

function createLineChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Inquiries',
                data,
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: fullscreen ? 6 : 5,
                pointHoverRadius: fullscreen ? 9 : 8,
                pointBackgroundColor: palette.indigo,
                borderWidth: fullscreen ? 3 : 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: false,
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
    if (!hasData) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);
    const colors = [
        palette.emerald,
        palette.amber,
        palette.blue,
    ];

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
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

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    destroyChartOn(canvasId);

    const fullscreen = Boolean(options.fullscreen);
    const colors = chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.85)'));

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Cases',
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
                legend: { display: false },
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

function initCroDashboard() {
    const data = window.croDashboardData;
    if (!data) return;

    const inquiryTrend = data.charts?.inquiryTrend ?? { labels: [], counts: [] };
    const complaintStatus = data.charts?.complaintStatus ?? { labels: [], counts: [] };
    const supportCategories = data.charts?.supportCategories ?? { labels: [], counts: [] };

    const chartBuilders = {
        inquiryTrend: (canvasId, options = {}) => createLineChart(
            canvasId,
            inquiryTrend.labels ?? [],
            inquiryTrend.counts ?? [],
            options,
        ),
        complaintStatus: (canvasId, options = {}) => createDoughnutChart(
            canvasId,
            complaintStatus.labels ?? [],
            complaintStatus.counts ?? [],
            options,
        ),
        supportCategories: (canvasId, options = {}) => createBarChart(
            canvasId,
            supportCategories.labels ?? [],
            supportCategories.counts ?? [],
            options,
        ),
    };

    const cardTargets = {
        inquiryTrend: 'croInquiryTrendChart',
        complaintStatus: 'croComplaintStatusChart',
        supportCategories: 'croSupportCategoriesChart',
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

        destroyChartOn('croChartFullscreen');
    }

    window.addEventListener('cro-chart-expand', (event) => {
        const key = event.detail?.key;
        const builder = chartBuilders[key];
        if (!builder) return;

        destroyFullscreenChart();

        requestAnimationFrame(() => {
            fullscreenChart = builder('croChartFullscreen', { fullscreen: true });
        });
    });

    window.addEventListener('cro-chart-collapse', destroyFullscreenChart);

    window.addEventListener('resize', () => {
        chartInstances.forEach((chart) => chart.resize());
        if (fullscreenChart) fullscreenChart.resize();
    });
}

document.addEventListener('DOMContentLoaded', initCroDashboard);

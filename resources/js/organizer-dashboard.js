import {
    Chart,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarElement,
    CategoryScale,
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
    emerald: 'rgb(16, 185, 129)',
};

const defaultFont = {
    family: 'Figtree, ui-sans-serif, system-ui, sans-serif',
    size: 12,
};

const chartConfigs = {
    revenue: {
        label: 'Revenue (LKR)',
        color: palette.emerald,
        fill: 'rgba(16, 185, 129, 0.12)',
        canvasId: 'organizerRevenueChart',
        yTickCallback: (value) => Number(value).toLocaleString(),
    },
    tickets: {
        label: 'Tickets Sold',
        color: palette.blue,
        fill: 'rgba(37, 99, 235, 0.12)',
        canvasId: 'organizerTicketSalesChart',
        yTickCallback: (value) => value,
    },
};

function createTrendChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: options.label ?? 'Trend',
                data,
                borderColor: options.color ?? palette.indigo,
                backgroundColor: options.fill ?? 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: options.pointRadius ?? 4,
                pointHoverRadius: options.pointHoverRadius ?? 7,
                pointBackgroundColor: options.color ?? palette.indigo,
                borderWidth: options.borderWidth ?? 2.5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutQuart',
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: options.showLegend ?? false,
                    position: 'bottom',
                    labels: { font: defaultFont, padding: 16, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { ...defaultFont, size: options.tickSize ?? 12 },
                        color: '#64748b',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.18)' },
                    ticks: {
                        font: { ...defaultFont, size: options.tickSize ?? 12 },
                        color: '#64748b',
                        callback: options.yTickCallback ?? ((value) => value),
                    },
                },
            },
        },
    });
}

function updateMetricUi(key, metric) {
    const totalEl = document.querySelector(`[data-chart-total="${key}"]`);
    const changeEl = document.querySelector(`[data-chart-change="${key}"]`);

    if (totalEl) {
        totalEl.textContent = metric.totalFormatted ?? '0';
    }

    if (changeEl) {
        const up = metric.up !== false;
        const percent = Number(metric.changePercent ?? 0);
        changeEl.textContent = `${up ? '▲' : '▼'} ${Math.abs(percent)}%`;
        changeEl.className = `inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold ${
            up ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'
        }`;
    }
}

function updatePeriodLabels(label) {
    document.querySelectorAll('[data-chart-period-label]').forEach((el) => {
        el.textContent = label;
    });
}

function initOrganizerDashboard() {
    const data = window.organizerDashboardData;
    if (!data?.charts?.periods) return;

    let currentPeriod = data.charts.defaultPeriod ?? 'month';
    let fullscreenChart = null;
    const chartInstances = {};

    function periodPayload(period = currentPeriod) {
        return data.charts.periods[period] ?? data.charts.periods.month;
    }

    function metricFor(key, period = currentPeriod) {
        return periodPayload(period)[key] ?? { labels: [], series: [], totalFormatted: '0', changePercent: 0, up: true };
    }

    function renderCharts(period = currentPeriod) {
        currentPeriod = period;
        const payload = periodPayload(period);
        updatePeriodLabels(payload.label ?? 'This Month');

        Object.entries(chartConfigs).forEach(([key, config]) => {
            const metric = metricFor(key, period);
            updateMetricUi(key, metric);
            chartInstances[key] = createTrendChart(
                config.canvasId,
                metric.labels ?? [],
                metric.series ?? [],
                config,
            );
        });

        if (fullscreenChart && document.getElementById('organizerChartFullscreen')) {
            // Keep fullscreen in sync if open with a known chart key stored on the canvas.
            const activeKey = document.getElementById('organizerChartFullscreen')?.dataset?.chartKey;
            if (activeKey && chartConfigs[activeKey]) {
                const metric = metricFor(activeKey, period);
                fullscreenChart = createTrendChart(
                    'organizerChartFullscreen',
                    metric.labels ?? [],
                    metric.series ?? [],
                    {
                        ...chartConfigs[activeKey],
                        showLegend: true,
                        pointRadius: 6,
                        pointHoverRadius: 9,
                        borderWidth: 3,
                        tickSize: 13,
                    },
                );
            }
        }
    }

    function destroyFullscreenChart() {
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }

        const canvas = document.getElementById('organizerChartFullscreen');
        if (canvas) {
            delete canvas.dataset.chartKey;
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();
        }
    }

    renderCharts(currentPeriod);

    window.addEventListener('organizer-chart-period', (event) => {
        const period = event.detail?.period;
        if (!period || !data.charts.periods[period]) return;
        renderCharts(period);
    });

    window.addEventListener('organizer-chart-expand', (event) => {
        const key = event.detail?.key;
        const period = event.detail?.period ?? currentPeriod;
        const config = chartConfigs[key];
        if (!config) return;

        destroyFullscreenChart();

        const metric = metricFor(key, period);
        const canvas = document.getElementById('organizerChartFullscreen');
        if (canvas) {
            canvas.dataset.chartKey = key;
        }

        requestAnimationFrame(() => {
            fullscreenChart = createTrendChart(
                'organizerChartFullscreen',
                metric.labels ?? [],
                metric.series ?? [],
                {
                    ...config,
                    showLegend: true,
                    pointRadius: 6,
                    pointHoverRadius: 9,
                    borderWidth: 3,
                    tickSize: 13,
                },
            );
        });
    });

    window.addEventListener('organizer-chart-collapse', destroyFullscreenChart);

    window.addEventListener('resize', () => {
        Object.values(chartInstances).forEach((chart) => chart?.resize());
        if (fullscreenChart) fullscreenChart.resize();
    });
}

document.addEventListener('DOMContentLoaded', initOrganizerDashboard);

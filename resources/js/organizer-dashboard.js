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
import {
    clearChartEmptyState,
    isEmptyChartInput,
    showChartEmptyState,
} from './report-empty-state';
import { bindDashboardPdfExportButtons } from './dashboard-pdf-export';

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
        tooltipValue: (value) => `LKR ${Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`,
    },
    tickets: {
        label: 'Tickets Sold',
        color: palette.blue,
        fill: 'rgba(37, 99, 235, 0.12)',
        canvasId: 'organizerTicketSalesChart',
        yTickCallback: (value) => value,
        tooltipValue: (value) => `${Number(value).toLocaleString()} tickets`,
    },
};

function createTrendChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const existing = Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    clearChartEmptyState(canvas);

    const formatTooltipValue = options.tooltipValue
        ?? ((value) => Number(value).toLocaleString());

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
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: options.showLegend ?? false,
                    position: 'bottom',
                    labels: { font: defaultFont, padding: 16, usePointStyle: true },
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                    titleFont: { size: options.fullscreen ? 15 : 13, weight: 'bold' },
                    bodyFont: { size: options.fullscreen ? 14 : 12 },
                    padding: options.fullscreen ? 14 : 12,
                    cornerRadius: 8,
                    displayColors: true,
                    caretPadding: 8,
                    callbacks: {
                        title(items) {
                            return items[0]?.label ?? '';
                        },
                        label(context) {
                            const raw = context.parsed?.y;
                            if (raw === null || raw === undefined) {
                                return `${context.dataset.label}: —`;
                            }

                            return `${context.dataset.label}: ${formatTooltipValue(raw)}`;
                        },
                    },
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
                        fullscreen: true,
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
                        fullscreen: true,
                    },
                );
        });
    });

    window.addEventListener('organizer-chart-collapse', destroyFullscreenChart);

    window.addEventListener('resize', () => {
        Object.values(chartInstances).forEach((chart) => chart?.resize());
        if (fullscreenChart) fullscreenChart.resize();
    });

    bindDashboardPdfExportButtons();
    initLiveSalesPulse();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString();
}

function renderPurchaseCard(purchase) {
    const ticketNumber = purchase.ticket_number || '—';
    const badges = (purchase.category_badges?.length
        ? purchase.category_badges
        : [{ label: purchase.category || 'General', color: '#6366f1' }])
        .map((badge) => {
            const color = badge.color || '#6366f1';
            return `<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-black/5" style="background-color: ${escapeHtml(color)}18;">
                <span class="h-1.5 w-1.5 rounded-full" style="background-color: ${escapeHtml(color)}"></span>
                ${escapeHtml(badge.label || 'General')}
            </span>`;
        })
        .join('');

    return `<a href="${escapeHtml(purchase.url || '#')}" class="btn-smooth flex items-start gap-3 px-5 py-4 hover:bg-white/45">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/60 bg-emerald-50/80 text-sm font-bold text-emerald-700 backdrop-blur-sm">
            <i class="bi bi-ticket-perforated"></i>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <p class="truncate font-mono text-sm font-semibold text-slate-900">${escapeHtml(ticketNumber)}</p>
                <p class="shrink-0 text-[11px] font-medium text-slate-400">${escapeHtml(purchase.booked_at || '—')}</p>
            </div>
            <div class="mt-1.5 flex flex-wrap gap-1.5">${badges}</div>
            <div class="mt-1.5 flex items-center justify-between gap-3">
                <p class="truncate text-xs text-slate-500">${escapeHtml(purchase.event || '')}</p>
                <p class="shrink-0 text-sm font-bold text-slate-900">LKR ${formatNumber(purchase.amount)}</p>
            </div>
        </div>
    </a>`;
}

function applyLivePulse(payload) {
    const today = payload?.todaySummary || {};
    const dayOfOps = payload?.dayOfOps || {};

    const eventsEl = document.querySelector('[data-live="eventsToday"]');
    const ticketsEl = document.querySelector('[data-live="ticketsSold"]');
    const revenueEl = document.querySelector('[data-live="revenue"]');
    const checkinRatioEl = document.querySelector('[data-live="checkinRatio"]');
    const checkinRateEl = document.querySelector('[data-live="checkinRate"]');
    const refreshedEl = document.querySelector('[data-live-refreshed]');
    const listEl = document.querySelector('[data-live-sales-list]');

    if (eventsEl) eventsEl.textContent = formatNumber(today.eventsToday);
    if (ticketsEl) ticketsEl.textContent = formatNumber(today.ticketsSold);
    if (revenueEl) revenueEl.textContent = `LKR ${formatNumber(today.revenue)}`;

    if (checkinRatioEl && dayOfOps.active) {
        checkinRatioEl.textContent = `${formatNumber(dayOfOps.checked_in)}/${formatNumber(dayOfOps.sold)}`;
    }
    if (checkinRateEl && dayOfOps.active) {
        checkinRateEl.textContent = `Check-in · ${dayOfOps.rate ?? 0}%`;
    }

    if (refreshedEl && payload?.refreshed_label) {
        refreshedEl.textContent = ` · updated ${payload.refreshed_label}`;
    }

    if (listEl && Array.isArray(payload?.recentPurchases)) {
        if (payload.recentPurchases.length === 0) {
            listEl.innerHTML = `<div class="p-4" data-live-sales-empty>
                <p class="py-8 text-center text-sm text-slate-500">No recent sales yet.</p>
            </div>`;
        } else {
            listEl.innerHTML = payload.recentPurchases.map(renderPurchaseCard).join('');
        }
    }
}

function initLiveSalesPulse() {
    const url = window.organizerDashboardLiveUrl;
    if (!url) return;

    const intervalMs = 20000;
    let timer = null;
    let inFlight = false;

    async function tick() {
        if (document.hidden || inFlight) return;
        inFlight = true;
        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            applyLivePulse(payload);
        } catch (error) {
            // Keep silent — dashboard still works without live updates.
        } finally {
            inFlight = false;
        }
    }

    function start() {
        if (timer) return;
        timer = window.setInterval(tick, intervalMs);
    }

    function stop() {
        if (!timer) return;
        window.clearInterval(timer);
        timer = null;
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stop();
        } else {
            tick();
            start();
        }
    });

    start();
}

document.addEventListener('DOMContentLoaded', initOrganizerDashboard);

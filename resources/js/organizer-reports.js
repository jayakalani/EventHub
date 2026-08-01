import {
    Chart,
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    ScatterController,
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
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    ScatterController,
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

function destroyChartOn(canvas) {
    if (!canvas) return;
    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();
}

function prepareCanvas(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    destroyChartOn(canvas);
    clearChartEmptyState(canvas);
    return canvas;
}

function createLineChart(canvasId, labels, datasets) {
    const canvas = prepareCanvas(canvasId);
    if (!canvas) return null;

    const hasData = Array.isArray(datasets)
        && datasets.some((dataset) => !isEmptySeries(dataset.data ?? []));

    if (!labels?.length || !hasData) {
        return showChartEmptyState(canvas);
    }

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
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = prepareCanvas(canvasId);
    if (!canvas) return null;

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: options.label ?? 'Count',
                data,
                backgroundColor: options.color
                    ?? options.colors
                    ?? chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.75)')),
                borderRadius: 8,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: options.horizontal ? 'y' : 'x',
            plugins: {
                legend: { display: Boolean(options.showLegend) },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    stacked: Boolean(options.stacked),
                    grid: options.horizontal ? { color: 'rgba(148, 163, 184, 0.2)' } : { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    beginAtZero: true,
                    stacked: Boolean(options.stacked),
                    grid: options.horizontal ? { display: false } : { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createStackedBarChart(canvasId, labels, datasets) {
    const canvas = prepareCanvas(canvasId);
    if (!canvas) return null;

    const hasData = Array.isArray(datasets)
        && datasets.some((dataset) => !isEmptySeries(dataset.data ?? []));

    if (!labels?.length || !hasData) {
        return showChartEmptyState(canvas);
    }

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: datasets.map((dataset) => ({
                ...dataset,
                stack: 'revenue',
                borderRadius: 6,
                borderSkipped: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: defaultFont, padding: 14, usePointStyle: true },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const label = context.dataset.label ?? '';
                            const value = context.parsed?.y ?? context.parsed ?? 0;
                            return `${label}: LKR ${Number(value).toLocaleString()}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createScatterChart(canvasId, points, options = {}) {
    const canvas = prepareCanvas(canvasId);
    if (!canvas) return null;

    if (!Array.isArray(points) || points.length === 0) {
        return showChartEmptyState(canvas);
    }

    const xLabel = options.xLabel ?? 'Fill rate (%)';
    const yLabel = options.yLabel ?? 'Revenue (LKR)';
    const xMax = options.xMax;
    const pointColor = options.pointColor ?? 'rgba(79, 70, 229, 0.75)';
    const pointBorder = options.pointBorder ?? 'rgba(79, 70, 229, 1)';
    const formatTooltip = options.formatTooltip
        ?? ((point) => {
            const name = point.name ?? 'Event';
            const fill = Number(point.x ?? 0).toLocaleString();
            const revenue = Number(point.y ?? 0).toLocaleString();
            return `${name}: LKR ${revenue} · ${fill}% fill`;
        });

    const xScale = {
        type: 'linear',
        min: 0,
        title: {
            display: true,
            text: xLabel,
            font: defaultFont,
        },
        grid: { color: 'rgba(148, 163, 184, 0.2)' },
        ticks: { font: defaultFont },
    };

    if (xMax != null) {
        xScale.max = xMax;
    }

    return new Chart(canvas, {
        type: 'scatter',
        data: {
            datasets: [{
                label: options.datasetLabel ?? 'Events',
                data: points,
                backgroundColor: pointColor,
                borderColor: pointBorder,
                pointRadius: 8,
                pointHoverRadius: 11,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            return formatTooltip(context.raw ?? {});
                        },
                    },
                },
            },
            scales: {
                x: xScale,
                y: {
                    type: 'linear',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: yLabel,
                        font: defaultFont,
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data, percentages = []) {
    const canvas = prepareCanvas(canvasId);
    if (!canvas) return null;

    if (isEmptyChartInput(labels, data)) {
        return showChartEmptyState(canvas);
    }

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: chartColors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: defaultFont,
                        padding: 14,
                        usePointStyle: true,
                        generateLabels(chart) {
                            const dataset = chart.data.datasets[0] ?? {};
                            return (chart.data.labels ?? []).map((label, index) => {
                                const percentage = percentages[index] ?? null;
                                const text = percentage == null
                                    ? String(label)
                                    : `${label} (${percentage}%)`;

                                return {
                                    text,
                                    fillStyle: Array.isArray(dataset.backgroundColor)
                                        ? dataset.backgroundColor[index]
                                        : dataset.backgroundColor,
                                    strokeStyle: '#ffffff',
                                    lineWidth: 2,
                                    hidden: false,
                                    index,
                                };
                            });
                        },
                    },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const label = context.label ?? '';
                            const value = context.parsed ?? 0;
                            const percentage = percentages[context.dataIndex];
                            if (percentage == null) {
                                return `${label}: ${value}`;
                            }
                            return `${label}: ${value} (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });
}

function buildChartSpecs(data) {
    const { chartLabels } = data;
    const topSales = data.ticketSales.topSellingEvents ?? [];
    const topRevenue = data.revenue.topRevenueEvents ?? [];
    const topAttendeeEvents = (data.attendees.attendeesByEvent ?? []).slice(0, 5);
    const salesByCategory = data.salesByCategory ?? [];
    const engagementBreakdown = data.engagement.engagementBreakdown ?? [];
    const eventPerformance = data.eventPerformance ?? [];
    const rankedByRevenue = [...eventPerformance]
        .sort((a, b) => Number(b.revenue || 0) - Number(a.revenue || 0));
    const top5ByRevenue = rankedByRevenue.slice(0, 5);
    const top5ByTickets = [...eventPerformance]
        .sort((a, b) => Number(b.tickets_sold || 0) - Number(a.tickets_sold || 0))
        .slice(0, 5);
    const revenuePerEvent = rankedByRevenue.slice(0, 10);

    return {
        revenueTrend: {
            canvasId: 'overviewRevenueChart',
            render: (targetId) => createLineChart(targetId, chartLabels, [{
                label: 'Revenue (LKR)',
                data: data.revenue.revenueTrend,
                borderColor: palette.emerald,
                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 8,
            }]),
        },
        monthlyRevenue: {
            canvasId: 'monthlyRevenueBarChart',
            render: (targetId) => createBarChart(
                targetId,
                chartLabels,
                data.revenue.revenueTrend ?? [],
                {
                    label: 'Monthly revenue (LKR)',
                    color: 'rgba(16, 185, 129, 0.85)',
                    showLegend: true,
                },
            ),
        },
        cumulativeRevenue: {
            canvasId: 'cumulativeRevenueChart',
            render: (targetId) => createLineChart(targetId, chartLabels, [{
                label: 'Cumulative revenue (LKR)',
                data: data.revenue.cumulativeRevenueTrend
                    ?? (data.revenue.revenueTrend ?? []).reduce((acc, value) => {
                        const next = (acc.length ? acc[acc.length - 1] : 0) + Number(value || 0);
                        acc.push(Math.round(next * 100) / 100);
                        return acc;
                    }, []),
                borderColor: palette.indigo,
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 7,
            }]),
        },
        refundsVsSales: {
            canvasId: 'refundsVsSalesChart',
            render: (targetId) => createStackedBarChart(
                targetId,
                chartLabels,
                [
                    {
                        label: 'Confirmed sales',
                        data: data.revenue.revenueTrend ?? [],
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    },
                    {
                        label: 'Refunds',
                        data: data.revenue.refundTrend ?? [],
                        backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    },
                ],
            ),
        },
        ticketSalesOverTime: {
            canvasId: 'ticketSalesOverTimeChart',
            render: (targetId) => createLineChart(targetId, chartLabels, [{
                label: 'Tickets sold',
                data: data.ticketSales.salesTrend ?? [],
                borderColor: palette.blue,
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 5,
                pointHoverRadius: 8,
            }]),
        },
        ticketTypeTrend: {
            canvasId: 'ticketTypeTrendChart',
            render: (targetId) => {
                const series = data.ticketTypeTrend ?? [];
                if (!series.length) {
                    return createLineChart(targetId, [], []);
                }

                const colors = [
                    palette.indigo,
                    palette.blue,
                    palette.emerald,
                    palette.amber,
                    palette.rose,
                ];

                return createLineChart(
                    targetId,
                    chartLabels,
                    series.map((item, index) => ({
                        label: item.label,
                        data: item.data,
                        borderColor: colors[index % colors.length],
                        backgroundColor: colors[index % colors.length]
                            .replace('rgb', 'rgba')
                            .replace(')', ', 0.08)'),
                        fill: false,
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                    })),
                );
            },
        },
        conversionFunnel: {
            canvasId: 'conversionFunnelChart',
            render: (targetId) => {
                const funnel = data.conversionFunnel ?? [];
                return createBarChart(
                    targetId,
                    funnel.map((item) => item.label),
                    funnel.map((item) => item.count),
                    {
                        label: 'Count',
                        horizontal: true,
                        colors: [
                            'rgba(6, 182, 212, 0.85)',
                            'rgba(79, 70, 229, 0.85)',
                            'rgba(16, 185, 129, 0.85)',
                        ],
                        showLegend: false,
                    },
                );
            },
        },
        salesByCategory: {
            canvasId: 'salesByCategoryChart',
            render: (targetId) => createDoughnutChart(
                targetId,
                salesByCategory.map((item) => item.label),
                salesByCategory.map((item) => item.count),
                salesByCategory.map((item) => item.percentage),
            ),
        },
        engagement: {
            canvasId: 'overviewEngagementBarChart',
            render: (targetId) => createBarChart(
                targetId,
                engagementBreakdown.map((item) => item.label),
                engagementBreakdown.map((item) => item.count),
                {
                    label: 'Interactions',
                    colors: [
                        'rgba(244, 63, 94, 0.8)',
                        'rgba(79, 70, 229, 0.8)',
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                    ],
                },
            ),
        },
        engagementOverTime: {
            canvasId: 'engagementOverTimeChart',
            render: (targetId) => {
                const trend = data.engagement.engagementTrend ?? {};
                return createLineChart(targetId, chartLabels, [
                    {
                        label: 'Likes',
                        data: trend.likes ?? [],
                        borderColor: palette.rose,
                        backgroundColor: 'rgba(244, 63, 94, 0.12)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Saves',
                        data: trend.saves ?? [],
                        borderColor: palette.indigo,
                        backgroundColor: 'rgba(79, 70, 229, 0.12)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Comments',
                        data: trend.comments ?? [],
                        borderColor: palette.blue,
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Ratings',
                        data: trend.ratings ?? [],
                        borderColor: palette.amber,
                        backgroundColor: 'rgba(245, 158, 11, 0.12)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                    },
                ]);
            },
        },
        engagementBeforeEvent: {
            canvasId: 'engagementBeforeEventChart',
            render: (targetId) => {
                const before = data.engagement.engagementBeforeEvent ?? {};
                return createLineChart(targetId, before.labels ?? [], [
                    {
                        label: 'Likes',
                        data: before.likes ?? [],
                        borderColor: palette.rose,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Saves',
                        data: before.saves ?? [],
                        borderColor: palette.indigo,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Comments',
                        data: before.comments ?? [],
                        borderColor: palette.blue,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: 'Ticket sales',
                        data: before.tickets ?? [],
                        borderColor: palette.emerald,
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.3,
                        borderDash: [6, 4],
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                ]);
            },
        },
        engagementVsSales: {
            canvasId: 'engagementVsSalesChart',
            render: (targetId) => {
                const rows = data.engagement.engagementVsSales ?? [];
                return createScatterChart(
                    targetId,
                    rows.map((item) => ({
                        x: Number(item.engagement || 0),
                        y: Number(item.tickets_sold || 0),
                        name: item.name,
                        likes: item.likes,
                        comments: item.comments,
                        saves: item.saves,
                    })),
                    {
                        xLabel: 'Engagement score (likes + saves + comments + ratings)',
                        yLabel: 'Tickets sold',
                        pointColor: 'rgba(244, 63, 94, 0.75)',
                        pointBorder: 'rgba(244, 63, 94, 1)',
                        formatTooltip: (point) => {
                            const name = point.name ?? 'Event';
                            const engagement = Number(point.x ?? 0).toLocaleString();
                            const tickets = Number(point.y ?? 0).toLocaleString();
                            return `${name}: ${engagement} engagement · ${tickets} tickets`;
                        },
                    },
                );
            },
        },
        audienceEngagementVsSales: {
            canvasId: 'audienceEngagementVsSalesChart',
            render: (targetId) => {
                const rows = data.engagement.engagementVsSales ?? [];
                return createScatterChart(
                    targetId,
                    rows.map((item) => ({
                        x: Number(item.engagement || 0),
                        y: Number(item.tickets_sold || 0),
                        name: item.name,
                        likes: item.likes,
                        comments: item.comments,
                        saves: item.saves,
                    })),
                    {
                        xLabel: 'Engagement score (likes + saves + comments + ratings)',
                        yLabel: 'Tickets sold',
                        pointColor: 'rgba(79, 70, 229, 0.75)',
                        pointBorder: 'rgba(79, 70, 229, 1)',
                        formatTooltip: (point) => {
                            const name = point.name ?? 'Event';
                            const engagement = Number(point.x ?? 0).toLocaleString();
                            const tickets = Number(point.y ?? 0).toLocaleString();
                            return `${name}: ${engagement} engagement · ${tickets} tickets`;
                        },
                    },
                );
            },
        },
        revenueByEvent: {
            canvasId: 'overviewRevenueByEventChart',
            render: (targetId) => createBarChart(
                targetId,
                topRevenue.map((item) => item.name),
                topRevenue.map((item) => item.revenue),
                {
                    label: 'Revenue (LKR)',
                    horizontal: true,
                    colors: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(79, 70, 229, 0.75)',
                        'rgba(6, 182, 212, 0.75)',
                        'rgba(37, 99, 235, 0.75)',
                        'rgba(99, 102, 241, 0.75)',
                    ],
                },
            ),
        },
        revenuePerEvent: {
            canvasId: 'revenuePerEventChart',
            render: (targetId) => createBarChart(
                targetId,
                revenuePerEvent.map((item) => item.name),
                revenuePerEvent.map((item) => item.revenue),
                {
                    label: 'Revenue (LKR)',
                    colors: [
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(79, 70, 229, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(244, 63, 94, 0.75)',
                        'rgba(147, 51, 234, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                        'rgba(99, 102, 241, 0.75)',
                        'rgba(100, 116, 139, 0.75)',
                    ],
                    showLegend: true,
                },
            ),
        },
        top5Revenue: {
            canvasId: 'top5EventsRevenueChart',
            render: (targetId) => createBarChart(
                targetId,
                top5ByRevenue.map((item) => item.name),
                top5ByRevenue.map((item) => item.revenue),
                {
                    label: 'Revenue (LKR)',
                    horizontal: true,
                    color: 'rgba(16, 185, 129, 0.85)',
                    showLegend: true,
                },
            ),
        },
        top5Tickets: {
            canvasId: 'top5EventsTicketsChart',
            render: (targetId) => createBarChart(
                targetId,
                top5ByTickets.map((item) => item.name),
                top5ByTickets.map((item) => item.tickets_sold),
                {
                    label: 'Tickets sold',
                    horizontal: true,
                    color: 'rgba(37, 99, 235, 0.85)',
                    showLegend: true,
                },
            ),
        },
        revenueRanking: {
            canvasId: 'revenueRankingChart',
            render: (targetId) => createBarChart(
                targetId,
                top5ByRevenue.map((item) => item.name),
                top5ByRevenue.map((item) => item.revenue),
                {
                    label: 'Revenue (LKR)',
                    horizontal: true,
                    color: 'rgba(16, 185, 129, 0.85)',
                    showLegend: true,
                },
            ),
        },
        revenueFillScatter: {
            canvasId: 'revenueFillScatterChart',
            render: (targetId) => createScatterChart(
                targetId,
                eventPerformance.map((item) => ({
                    x: Number(item.fill_rate || 0),
                    y: Number(item.revenue || 0),
                    name: item.name,
                    tickets: item.tickets_sold,
                })),
                {
                    xMax: 100,
                    xLabel: 'Fill rate (%)',
                    yLabel: 'Revenue (LKR)',
                    formatTooltip: (point) => {
                        const name = point.name ?? 'Event';
                        const fill = Number(point.x ?? 0).toLocaleString();
                        const revenue = Number(point.y ?? 0).toLocaleString();
                        const tickets = Number(point.tickets ?? 0).toLocaleString();
                        return `${name}: LKR ${revenue} · ${fill}% fill · ${tickets} tickets`;
                    },
                },
            ),
        },
        ticketSalesByEvent: {
            canvasId: 'overviewTicketSalesByEventChart',
            render: (targetId) => createBarChart(
                targetId,
                topSales.slice(0, 6).map((item) => item.name),
                topSales.slice(0, 6).map((item) => item.sold),
                {
                    label: 'Tickets Sold',
                    colors: [
                        'rgba(37, 99, 235, 0.85)',
                        'rgba(79, 70, 229, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(99, 102, 241, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                    ],
                },
            ),
        },
        attendeesByEvent: {
            canvasId: 'overviewAttendeesByEventChart',
            render: (targetId) => createBarChart(
                targetId,
                topAttendeeEvents.map((item) => item.name),
                topAttendeeEvents.map((item) => item.count),
                {
                    label: 'Attendees',
                    horizontal: true,
                    colors: [
                        'rgba(79, 70, 229, 0.85)',
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(6, 182, 212, 0.75)',
                        'rgba(16, 185, 129, 0.75)',
                        'rgba(14, 165, 233, 0.75)',
                    ],
                },
            ),
        },
        demographicsAge: {
            canvasId: 'demographicsAgeChart',
            render: (targetId) => {
                const rows = data.attendees.demographics?.age ?? [];
                return createDoughnutChart(
                    targetId,
                    rows.map((item) => item.label),
                    rows.map((item) => item.count),
                );
            },
        },
        demographicsGender: {
            canvasId: 'demographicsGenderChart',
            render: (targetId) => {
                const rows = data.attendees.demographics?.gender ?? [];
                return createDoughnutChart(
                    targetId,
                    rows.map((item) => item.label),
                    rows.map((item) => item.count),
                );
            },
        },
        demographicsLocation: {
            canvasId: 'demographicsLocationChart',
            render: (targetId) => {
                const rows = data.attendees.demographics?.location ?? [];
                return createDoughnutChart(
                    targetId,
                    rows.map((item) => item.label),
                    rows.map((item) => item.count),
                );
            },
        },
        repeatVsNew: {
            canvasId: 'repeatVsNewChart',
            render: (targetId) => createBarChart(
                targetId,
                ['New attendees', 'Repeat attendees'],
                [
                    Number(data.attendees.newAttendees ?? 0),
                    Number(data.attendees.repeatAttendees ?? 0),
                ],
                {
                    label: 'Unique buyers',
                    colors: ['rgba(79, 70, 229, 0.85)', 'rgba(16, 185, 129, 0.85)'],
                    showLegend: true,
                },
            ),
        },
    };
}

function initOrganizerReports() {
    const data = window.organizerReportData;
    if (!data) return;

    const specs = buildChartSpecs(data);
    const charts = [];
    let fullscreenChart = null;

    Object.values(specs).forEach((spec) => {
        const chart = spec.render(spec.canvasId);
        if (chart) charts.push(chart);
    });

    const destroyFullscreenChart = () => {
        if (fullscreenChart) {
            fullscreenChart.destroy();
            fullscreenChart = null;
        }
        destroyChartOn(document.getElementById('organizerReportChartFullscreen'));
    };

    window.addEventListener('organizer-reports-chart-expand', (event) => {
        const key = event.detail?.key;
        const spec = specs[key];
        if (!spec) return;

        destroyFullscreenChart();

        const canvas = document.getElementById('organizerReportChartFullscreen');
        if (!canvas) return;

        canvas.dataset.chartKey = key;
        requestAnimationFrame(() => {
            fullscreenChart = spec.render('organizerReportChartFullscreen');
            requestAnimationFrame(() => {
                fullscreenChart?.resize();
                setTimeout(() => fullscreenChart?.resize(), 120);
            });
        });
    });

    window.addEventListener('organizer-reports-chart-collapse', destroyFullscreenChart);

    const resizeCharts = () => {
        charts.forEach((chart) => chart.resize());
        if (fullscreenChart) fullscreenChart.resize();
    };

    window.addEventListener('resize', resizeCharts);
    window.addEventListener('organizer-reports-tab-changed', () => {
        requestAnimationFrame(() => {
            resizeCharts();
            setTimeout(resizeCharts, 80);
        });
    });

    bindDashboardPdfExportButtons();
}

document.addEventListener('DOMContentLoaded', initOrganizerReports);

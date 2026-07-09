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

function createLineChart(canvasId, labels, datasets) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

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
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

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
            indexAxis: options.horizontal ? 'y' : 'x',
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: options.horizontal ? { color: 'rgba(148, 163, 184, 0.2)' } : { display: false },
                    ticks: { font: defaultFont },
                },
                y: {
                    beginAtZero: true,
                    grid: options.horizontal ? { display: false } : { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont },
                },
            },
        },
    });
}

function createDoughnutChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

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
                    labels: { font: defaultFont, padding: 14, usePointStyle: true },
                },
            },
        },
    });
}

function initOrganizerReports() {
    const data = window.organizerReportData;
    if (!data) return;

    const { chartLabels } = data;
    const charts = [];
    const register = (chart) => {
        if (chart) charts.push(chart);
        return chart;
    };

    const topSales = data.ticketSales.topSellingEvents ?? [];
    register(createLineChart('salesTrendChart', chartLabels, [{
        label: 'Tickets Sold',
        data: data.ticketSales.salesTrend,
        borderColor: palette.blue,
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]));

    register(createBarChart(
        'salesByEventChart',
        topSales.map((i) => i.name),
        topSales.map((i) => i.sold),
        { label: 'Tickets Sold', horizontal: true },
    ));

    register(createLineChart('revenueTrendChart', chartLabels, [{
        label: 'Revenue (LKR)',
        data: data.revenue.revenueTrend,
        borderColor: palette.emerald,
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]));

    const topRevenue = data.revenue.topRevenueEvents ?? [];
    register(createBarChart(
        'revenueByEventChart',
        topRevenue.map((i) => i.name),
        topRevenue.map((i) => i.revenue),
        {
            label: 'Revenue (LKR)',
            horizontal: true,
            colors: ['rgba(16, 185, 129, 0.75)', 'rgba(79, 70, 229, 0.75)', 'rgba(6, 182, 212, 0.75)', 'rgba(37, 99, 235, 0.75)', 'rgba(147, 51, 234, 0.75)'],
        },
    ));

    register(createLineChart('attendeeTrendChart', chartLabels, [{
        label: 'New Bookings',
        data: data.attendees.registrationTrend,
        borderColor: palette.indigo,
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]));

    const topAttendeeEvents = (data.attendees.attendeesByEvent ?? []).slice(0, 5);
    register(createBarChart(
        'attendeesByEventChart',
        topAttendeeEvents.map((i) => i.name),
        topAttendeeEvents.map((i) => i.count),
        { label: 'Attendees', horizontal: true },
    ));

    register(createLineChart('engagementTrendChart', chartLabels, [
        {
            label: 'Likes',
            data: data.engagement.engagementTrend.likes,
            borderColor: palette.rose,
            backgroundColor: 'rgba(244, 63, 94, 0.05)',
            fill: false,
            tension: 0.35,
            pointRadius: 3,
        },
        {
            label: 'Comments',
            data: data.engagement.engagementTrend.comments,
            borderColor: palette.blue,
            backgroundColor: 'rgba(37, 99, 235, 0.05)',
            fill: false,
            tension: 0.35,
            pointRadius: 3,
        },
        {
            label: 'Ratings',
            data: data.engagement.engagementTrend.ratings,
            borderColor: palette.amber,
            backgroundColor: 'rgba(245, 158, 11, 0.05)',
            fill: false,
            tension: 0.35,
            pointRadius: 3,
        },
    ]));

    register(createDoughnutChart(
        'engagementBreakdownChart',
        data.engagement.engagementBreakdown.map((i) => i.label),
        data.engagement.engagementBreakdown.map((i) => i.count),
    ));

    const topPopular = data.engagement.topEvents ?? [];
    register(createBarChart(
        'popularityChart',
        topPopular.map((i) => i.name),
        topPopular.map((i) => i.score),
        { label: 'Engagement Score', horizontal: true },
    ));

    register(createLineChart('overviewRevenueChart', chartLabels, [{
        label: 'Revenue (LKR)',
        data: data.revenue.revenueTrend,
        borderColor: palette.emerald,
        backgroundColor: 'rgba(16, 185, 129, 0.15)',
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 8,
    }]));

    register(createBarChart(
        'overviewTicketSalesChart',
        chartLabels,
        data.ticketSales.salesTrend,
        {
            label: 'Tickets Sold',
            colors: chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.8)')),
        },
    ));

    const resizeCharts = () => charts.forEach((chart) => chart.resize());
    window.addEventListener('organizer-reports-tab-changed', resizeCharts);
    window.addEventListener('resize', resizeCharts);
}

document.addEventListener('DOMContentLoaded', initOrganizerReports);

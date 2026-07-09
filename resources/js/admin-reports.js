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

    const chart = new Chart(canvas, {
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

    return chart;
}

function createBarChart(canvasId, labels, data, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    if (!labels.length) return null;

    const chart = new Chart(canvas, {
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

    return chart;
}

function createDoughnutChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    if (!labels.length) return null;

    const chart = new Chart(canvas, {
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

    return chart;
}

function initAdminReports() {
    const data = window.adminReportData;
    if (!data) return;

    const { chartLabels } = data;
    const charts = [];

    const register = (chart) => {
        if (chart) charts.push(chart);
        return chart;
    };

    register(createLineChart('adminPlatformGrowthChart', chartLabels, [
        {
            label: 'New Users',
            data: data.admin.platformGrowth,
            borderColor: palette.indigo,
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
        {
            label: 'New Events',
            data: data.admin.eventGrowth,
            borderColor: palette.cyan,
            backgroundColor: 'rgba(6, 182, 212, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]));

    register(createDoughnutChart(
        'adminEventsStatusChart',
        data.admin.eventsByStatus.map((i) => i.label),
        data.admin.eventsByStatus.map((i) => i.count),
    ));

    register(createBarChart(
        'adminTopCategoriesChart',
        data.admin.topCategories.map((i) => i.label),
        data.admin.topCategories.map((i) => i.count),
        { label: 'Events' },
    ));

    register(createDoughnutChart(
        'userRoleChart',
        data.users.usersByRole.map((i) => i.label),
        data.users.usersByRole.map((i) => i.count),
    ));

    register(createLineChart('userRegistrationChart', chartLabels, [
        {
            label: 'Registrations',
            data: data.users.registrationTrend,
            borderColor: palette.blue,
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]));

    register(createBarChart(
        'userStatusChart',
        ['Active', 'Inactive', 'Verified', 'Unverified', 'Locked'],
        [
            data.users.activeUsers,
            data.users.inactiveUsers,
            data.users.verifiedUsers,
            data.users.unverifiedUsers,
            data.users.lockedUsers,
        ],
        {
            label: 'Users',
            colors: [
                'rgba(16, 185, 129, 0.75)',
                'rgba(100, 116, 139, 0.75)',
                'rgba(37, 99, 235, 0.75)',
                'rgba(245, 158, 11, 0.75)',
                'rgba(244, 63, 94, 0.75)',
            ],
        },
    ));

    register(createLineChart('paymentRevenueChart', chartLabels, [
        {
            label: 'Revenue (LKR)',
            data: data.payments.revenueTrend,
            borderColor: palette.emerald,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]));

    register(createDoughnutChart(
        'paymentStatusChart',
        data.payments.paymentsByStatus.map((i) => i.label),
        data.payments.paymentsByStatus.map((i) => i.count),
    ));

    register(createDoughnutChart(
        'paymentMethodChart',
        data.payments.paymentsByMethod.map((i) => i.label),
        data.payments.paymentsByMethod.map((i) => i.count),
    ));

    register(createLineChart('systemActivityChart', chartLabels, [
        {
            label: 'Audit Log Entries',
            data: data.system.activityTrend,
            borderColor: palette.purple,
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]));

    register(createBarChart(
        'systemAuditActionChart',
        data.system.auditByAction.map((i) => i.label),
        data.system.auditByAction.map((i) => i.count),
        { label: 'Actions' },
    ));

    register(createLineChart('overviewRevenueChart', chartLabels, [{
        label: 'Revenue (LKR)',
        data: data.payments.revenueTrend,
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
        data.admin.ticketSalesTrend ?? [],
        {
            label: 'Tickets Sold',
            colors: chartColors.map((c) => c.replace('rgb', 'rgba').replace(')', ', 0.8)')),
        },
    ));

    register(createLineChart('overviewUserRegChart', chartLabels, [{
        label: 'Registrations',
        data: data.users.registrationTrend,
        borderColor: palette.indigo,
        backgroundColor: 'rgba(79, 70, 229, 0.15)',
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 8,
    }]));

    const resizeCharts = () => charts.forEach((chart) => chart.resize());

    window.addEventListener('admin-reports-tab-changed', resizeCharts);
    window.addEventListener('resize', resizeCharts);
}

document.addEventListener('DOMContentLoaded', initAdminReports);

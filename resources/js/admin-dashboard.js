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

function createLineChart(canvasId, labels, datasets) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

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
    if (!canvas || !labels.length) return null;

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
                    ticks: { font: defaultFont, maxRotation: 45, minRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.2)' },
                    ticks: { font: defaultFont, precision: 0 },
                },
            },
        },
    });
}

function initAdminDashboard() {
    const data = window.adminDashboardData;
    if (!data) return;

    const { chartLabels, charts } = data;
    const categoryData = charts.ticketSalesByCategory ?? [];

    const chartInstances = [
        createLineChart('dashboardUserGrowthChart', chartLabels, [{
            label: 'New Registrations',
            data: charts.userGrowth ?? [],
            borderColor: palette.indigo,
            backgroundColor: 'rgba(79, 70, 229, 0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: palette.indigo,
        }]),
        createLineChart('dashboardRevenueChart', chartLabels, [{
            label: 'Revenue (LKR)',
            data: charts.revenue ?? [],
            borderColor: palette.emerald,
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: palette.emerald,
        }]),
        createBarChart(
            'dashboardTicketSalesChart',
            categoryData.map((item) => item.label),
            categoryData.map((item) => item.count),
            { label: 'Tickets Sold' },
        ),
    ].filter(Boolean);

    window.addEventListener('resize', () => {
        chartInstances.forEach((chart) => chart.resize());
    });
}

document.addEventListener('DOMContentLoaded', initAdminDashboard);

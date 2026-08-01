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
import { bindDashboardPdfExportButtons } from './dashboard-pdf-export';

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

function createLineChart(canvasId, labels, datasets, yMax = null) {
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

function createDoughnutChart(canvasId, labels, data, colors = null) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !labels.length) return null;

    return new Chart(canvas, {
        type: 'doughnut',
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

function initCroReports() {
    const data = window.croReportData;
    if (!data) return;

    const { chartLabels } = data;
    const charts = [];
    const register = (chart) => {
        if (chart) charts.push(chart);
        return chart;
    };

    const inquiries = data.inquiries;
    const complaints = data.complaints;

    register(createDoughnutChart(
        'inquiryStatusChart',
        inquiries.statusBreakdown.map((i) => i.label),
        inquiries.statusBreakdown.map((i) => i.count),
        statusColors,
    ));

    register(createLineChart('inquiryResolutionTrendChart', chartLabels, [
        {
            label: 'Submitted',
            data: inquiries.resolutionTrend.submitted,
            borderColor: palette.indigo,
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
        {
            label: 'Resolved',
            data: inquiries.resolutionTrend.resolved,
            borderColor: palette.emerald,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ]));

    register(createLineChart('inquiryResolutionRateChart', chartLabels, [{
        label: 'Resolution Rate (%)',
        data: inquiries.resolutionTrend.resolutionRate,
        borderColor: palette.cyan,
        backgroundColor: 'rgba(6, 182, 212, 0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointHoverRadius: 6,
    }], 100));

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
        complaints.statusBreakdown.map((i) => i.label),
        complaints.statusBreakdown.map((i) => i.count),
        statusColors,
    ));

    register(createDoughnutChart(
        'complaintTypeChart',
        complaints.typeBreakdown.map((i) => i.label),
        complaints.typeBreakdown.map((i) => i.count),
    ));

    register(createLineChart('complaintSubmissionsChart', chartLabels, [{
        label: 'Complaints Submitted',
        data: complaints.submissionsTrend,
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

    register(createLineChart('overviewResolutionRateChart', chartLabels, [{
        label: 'Resolution Rate (%)',
        data: inquiries.resolutionTrend.resolutionRate,
        borderColor: palette.cyan,
        backgroundColor: 'rgba(6, 182, 212, 0.15)',
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 8,
    }], 100));

    register(createLineChart('overviewComplaintTrendChart', chartLabels, [{
        label: 'Complaints',
        data: complaints.submissionsTrend,
        borderColor: palette.rose,
        backgroundColor: 'rgba(244, 63, 94, 0.15)',
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 8,
    }]));

    const resizeCharts = () => charts.forEach((chart) => chart.resize());
    window.addEventListener('cro-reports-tab-changed', resizeCharts);
    window.addEventListener('resize', resizeCharts);

    bindDashboardPdfExportButtons();
}

document.addEventListener('DOMContentLoaded', initCroReports);

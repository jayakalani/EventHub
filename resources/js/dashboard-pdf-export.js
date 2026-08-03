/**
 * Capture live Chart.js canvases and POST them with filter params for PDF export.
 */

import { Chart } from 'chart.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? '';
}

function filenameFromDisposition(header, fallback = 'dashboard.pdf') {
    if (!header) return fallback;

    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }

    const plainMatch = header.match(/filename=\"?([^\";]+)\"?/i);
    return plainMatch?.[1] ?? fallback;
}

/**
 * Temporarily reveal Alpine/x-show ancestors so Chart.js can measure and export.
 * @returns {() => void} restore function
 */
function revealHiddenAncestors(element) {
    const changes = [];
    let node = element?.parentElement ?? null;

    while (node && node !== document.body) {
        const computed = window.getComputedStyle(node);
        if (computed.display === 'none') {
            changes.push({
                node,
                display: node.style.display,
                visibility: node.style.visibility,
                position: node.style.position,
                left: node.style.left,
                top: node.style.top,
                width: node.style.width,
                height: node.style.height,
                overflow: node.style.overflow,
                pointerEvents: node.style.pointerEvents,
                zIndex: node.style.zIndex,
            });

            // Off-screen but laid out so Chart.js has a real size.
            node.style.display = 'block';
            node.style.visibility = 'hidden';
            node.style.position = 'fixed';
            node.style.left = '-10000px';
            node.style.top = '0';
            node.style.width = '900px';
            node.style.height = 'auto';
            node.style.overflow = 'visible';
            node.style.pointerEvents = 'none';
            node.style.zIndex = '-1';
        }
        node = node.parentElement;
    }

    return () => {
        changes.forEach((change) => {
            change.node.style.display = change.display;
            change.node.style.visibility = change.visibility;
            change.node.style.position = change.position;
            change.node.style.left = change.left;
            change.node.style.top = change.top;
            change.node.style.width = change.width;
            change.node.style.height = change.height;
            change.node.style.overflow = change.overflow;
            change.node.style.pointerEvents = change.pointerEvents;
            change.node.style.zIndex = change.zIndex;
        });
    };
}

/**
 * @param {HTMLCanvasElement} canvas
 */
function isCanvasMarkedEmpty(canvas) {
    return canvas.style.display === 'none' || canvas.hasAttribute('hidden');
}

/**
 * @param {import('chart.js').Chart} chart
 */
function chartHasDrawableData(chart) {
    const labels = chart?.data?.labels ?? [];
    const datasets = chart?.data?.datasets ?? [];
    if (!datasets.length) return false;

    // Prefer charts with real plotted values…
    const hasNonZero = datasets.some((dataset) => {
        const values = dataset.data ?? [];
        if (!Array.isArray(values) || values.length === 0) return false;

        return values.some((value) => {
            if (value === null || value === undefined) return false;
            if (typeof value === 'object') {
                const x = Number(value.x ?? 0);
                const y = Number(value.y ?? value.value ?? 0);
                return Number.isFinite(x) && Number.isFinite(y) && (x !== 0 || y !== 0);
            }
            const numeric = Number(value);
            return Number.isFinite(numeric) && numeric !== 0;
        });
    });

    if (hasNonZero) return true;

    // …but still export flat/zero series when axes exist (visible chart on dashboard).
    return labels.length > 0 && datasets.some((dataset) => Array.isArray(dataset.data) && dataset.data.length > 0);
}

/**
 * Export chart canvas onto a solid white background for clean PDF pages.
 * Uses JPEG so DomPDF does not require the PHP GD extension (needed for PNG).
 * @param {import('chart.js').Chart} chart
 * @returns {string}
 */
function chartToWhiteBackgroundImage(chart) {
    const source = chart.canvas;
    const maxWidth = 1100;
    const scale = source.width > maxWidth ? maxWidth / source.width : 1;
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = Math.max(Math.round(source.width * scale), 1);
    exportCanvas.height = Math.max(Math.round(source.height * scale), 1);

    const ctx = exportCanvas.getContext('2d');
    if (!ctx) {
        return chart.toBase64Image('image/jpeg', 0.82);
    }

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
    ctx.drawImage(source, 0, 0, exportCanvas.width, exportCanvas.height);

    return exportCanvas.toDataURL('image/jpeg', 0.82);
}

/**
 * @param {Array<{ canvasId: string, title: string }>} targets
 * @returns {Array<{ title: string, image: string }>}
 */
export function captureDashboardChartImages(targets = []) {
    return targets
        .map(({ canvasId, title }) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas || isCanvasMarkedEmpty(canvas)) {
                return null;
            }

            const restore = revealHiddenAncestors(canvas);

            try {
                const chart = Chart.getChart(canvas);
                if (!chart || !chartHasDrawableData(chart)) {
                    return null;
                }

                const previousAnimation = chart.options.animation;
                const previousBg = chart.options.backgroundColor;
                chart.options.animation = false;
                chart.options.backgroundColor = '#ffffff';
                chart.resize();
                chart.update('none');

                // Ensure non-zero drawable area for off-screen tabs.
                if (canvas.width < 10 || canvas.height < 10) {
                    canvas.width = Math.max(canvas.width, 800);
                    canvas.height = Math.max(canvas.height, 360);
                    chart.resize();
                    chart.update('none');
                }

                const image = chartToWhiteBackgroundImage(chart);
                chart.options.animation = previousAnimation;
                chart.options.backgroundColor = previousBg;

                return {
                    title: title || 'Chart',
                    image,
                };
            } catch (error) {
                console.warn('Unable to capture chart for PDF export', canvasId, error);
                return null;
            } finally {
                restore();
            }
        })
        .filter(Boolean);
}

/**
 * @param {{ url: string, params?: Record<string, string|number|null|undefined>, charts?: Array<{ canvasId: string, title: string }> }} options
 */
export async function submitDashboardPdfExport({ url, params = {}, charts = [] }) {
    if (!url) return;

    const formData = new FormData();
    formData.append('_token', csrfToken());

    Object.entries(params).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        formData.append(key, String(value));
    });

    captureDashboardChartImages(charts).forEach((chart, index) => {
        formData.append(`charts[${index}][title]`, chart.title);
        formData.append(`charts[${index}][image]`, chart.image);
    });

    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`PDF export failed (${response.status})`);
    }

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filenameFromDisposition(
        response.headers.get('Content-Disposition'),
        'dashboard.pdf',
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}

export function bindDashboardPdfExportButtons(root = document) {
    root.querySelectorAll('[data-dashboard-pdf-export]').forEach((button) => {
        if (button.dataset.pdfExportBound === '1') return;
        button.dataset.pdfExportBound = '1';

        button.addEventListener('click', async (event) => {
            event.preventDefault();

            let params = {};
            let charts = [];

            try {
                params = JSON.parse(button.getAttribute('data-export-params') || '{}');
            } catch {
                params = {};
            }

            try {
                charts = JSON.parse(button.getAttribute('data-export-charts') || '[]');
            } catch {
                charts = [];
            }

            const originalLabel = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');

            try {
                await submitDashboardPdfExport({
                    url: button.getAttribute('data-export-url') || '',
                    params,
                    charts,
                });
            } catch (error) {
                console.error(error);
                window.alert('Unable to export PDF. Please try again.');
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.innerHTML = originalLabel;
            }
        });
    });
}

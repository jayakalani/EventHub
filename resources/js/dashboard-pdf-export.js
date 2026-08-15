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
 * Temporarily reveal Alpine/x-show/x-cloak ancestors so Chart.js can measure and export.
 * x-cloak uses display: none !important, so a plain style.display = 'block' is not enough.
 * @returns {() => void} restore function
 */
function revealHiddenAncestors(element) {
    const changes = [];
    let node = element?.parentElement ?? null;

    while (node && node !== document.body) {
        const computed = window.getComputedStyle(node);
        const hasCloak = node.hasAttribute('x-cloak');
        const isHidden = computed.display === 'none' || hasCloak;

        if (isHidden) {
            changes.push({
                node,
                cloak: hasCloak,
                display: node.style.display,
                displayPriority: node.style.getPropertyPriority('display'),
                visibility: node.style.visibility,
                opacity: node.style.opacity,
                opacityPriority: node.style.getPropertyPriority('opacity'),
                position: node.style.position,
                left: node.style.left,
                top: node.style.top,
                width: node.style.width,
                height: node.style.height,
                overflow: node.style.overflow,
                pointerEvents: node.style.pointerEvents,
                zIndex: node.style.zIndex,
            });

            if (hasCloak) {
                node.removeAttribute('x-cloak');
            }

            // Off-screen but laid out so Chart.js has a real size.
            node.style.setProperty('display', 'block', 'important');
            node.style.setProperty('opacity', '1', 'important');
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
            // Never re-apply x-cloak: Alpine x-show owns visibility after init.
            // Re-adding x-cloak forces display:none !important and blanks the active tab.

            if (change.display) {
                change.node.style.setProperty('display', change.display, change.displayPriority || '');
            } else {
                change.node.style.removeProperty('display');
            }

            if (change.opacity) {
                change.node.style.setProperty('opacity', change.opacity, change.opacityPriority || '');
            } else {
                change.node.style.removeProperty('opacity');
            }

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

function revealExportCanvases(targets = []) {
    const restores = [];

    targets.forEach(({ canvasId }) => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            restores.push(revealHiddenAncestors(canvas));
        }
    });

    return () => {
        restores.reverse().forEach((restore) => restore());
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
 * Keeps the Chart.js backing-store resolution (retina) so the PDF does not upscale a small JPEG.
 * Uses JPEG so DomPDF does not require the PHP GD extension (needed for PNG).
 * @param {import('chart.js').Chart} chart
 * @returns {string}
 */
function chartToWhiteBackgroundImage(chart) {
    const source = chart.canvas;
    const width = Math.max(source.width || 0, 1);
    const height = Math.max(source.height || 0, 1);
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = width;
    exportCanvas.height = height;

    const ctx = exportCanvas.getContext('2d');
    if (!ctx) {
        return chart.toBase64Image('image/jpeg', 0.95);
    }

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(source, 0, 0);

    return exportCanvas.toDataURL('image/jpeg', 0.95);
}

/**
 * @param {Array<{ canvasId: string, title: string, section?: string }>} targets
 * @returns {Array<{ title: string, image: string, section?: string }>}
 */
export function captureDashboardChartImages(targets = []) {
    return targets
        .map(({ canvasId, title, section }) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas || isCanvasMarkedEmpty(canvas)) {
                return null;
            }

            const restore = revealHiddenAncestors(canvas);
            const wrapper = canvas.parentElement;
            const previousWrapper = wrapper
                ? { width: wrapper.style.width, height: wrapper.style.height }
                : null;

            try {
                const chart = Chart.getChart(canvas);
                if (!chart || !chartHasDrawableData(chart)) {
                    return null;
                }

                const cssWidth = Math.max(canvas.clientWidth || 0, chart.width || 0, 1);
                const cssHeight = Math.max(canvas.clientHeight || 0, chart.height || 0, 1);
                const exportWidth = 1200;
                const exportHeight = cssWidth > 40 && cssHeight > 40
                    ? Math.max(Math.round(exportWidth * (cssHeight / cssWidth)), 280)
                    : 400;

                if (wrapper) {
                    wrapper.style.width = `${exportWidth}px`;
                    wrapper.style.height = `${exportHeight}px`;
                }

                const previousAnimation = chart.options.animation;
                const previousBg = chart.options.backgroundColor;
                const previousRatio = chart.options.devicePixelRatio;

                chart.options.animation = false;
                chart.options.backgroundColor = '#ffffff';
                chart.options.devicePixelRatio = 2;
                chart.resize(exportWidth, exportHeight);
                chart.update('none');

                const image = chartToWhiteBackgroundImage(chart);

                chart.options.animation = previousAnimation;
                chart.options.backgroundColor = previousBg;
                chart.options.devicePixelRatio = previousRatio;

                return {
                    title: title || 'Chart',
                    image,
                    section: section || '',
                };
            } catch (error) {
                console.warn('Unable to capture chart for PDF export', canvasId, error);
                return null;
            } finally {
                if (wrapper && previousWrapper) {
                    wrapper.style.width = previousWrapper.width;
                    wrapper.style.height = previousWrapper.height;
                }

                try {
                    const chart = Chart.getChart(canvas);
                    chart?.resize();
                    chart?.update('none');
                } catch (error) {
                    console.warn('Unable to restore chart after PDF capture', canvasId, error);
                }

                restore();
            }
        })
        .filter(Boolean);
}

function readFilterFormParams(formId) {
    if (!formId) return {};

    const form = document.getElementById(formId);
    if (!form) return {};

    const keys = ['from', 'to', 'event_id', 'status', 'focus_event', 'organizer', 'event', 'cro', 'range'];
    const params = {};

    keys.forEach((key) => {
        const field = form.elements.namedItem(key);
        if (!field || typeof field.value !== 'string') return;
        const value = field.value.trim();
        if (value !== '') {
            params[key] = value;
        }
    });

    if (!params.event_id && params.focus_event) {
        params.event_id = params.focus_event;
    }

    return params;
}

function waitForPaint(ms = 180) {
    return new Promise((resolve) => {
        requestAnimationFrame(() => setTimeout(resolve, ms));
    });
}

/**
 * Warm Chart.js listeners. Charts are captured off-screen — do not change visible tabs.
 * @param {Array<{ canvasId: string, title: string, section?: string }>} charts
 */
async function prepareSectionChartsForExport(charts = []) {
    window.dispatchEvent(new CustomEvent('dashboard-pdf-export-prepare', {
        detail: { charts },
    }));
    window.dispatchEvent(new CustomEvent('cro-reports-tab-changed'));
    window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
        detail: { tab: 'export' },
    }));
    window.dispatchEvent(new CustomEvent('admin-reports-tab-changed', {
        detail: { tab: 'export' },
    }));

    const hasSectionMeta = charts.some((chart) => String(chart.section || '').trim());
    if (! hasSectionMeta) {
        ['support', 'performance', 'attendance', 'inquiry', 'complaints'].forEach((section) => {
            window.dispatchEvent(new CustomEvent('cro-dashboard-section-changed', { detail: { section } }));
        });
    }

    await waitForPaint(320);
}

function ensureRequiredExportAssets(button) {
    const requiredCanvasId = button.getAttribute('data-require-canvas');
    if (! requiredCanvasId) return true;
    if (document.getElementById(requiredCanvasId)) return true;

    const url = new URL(window.location.href);
    url.searchParams.set('insights', '1');
    url.searchParams.set('auto_pdf', '1');
    window.location.assign(url.toString());
    return false;
}

/**
 * @param {{ url: string, params?: Record<string, string|number|null|undefined>, charts?: Array<{ canvasId: string, title: string }>, filterFormId?: string }} options
 */
export async function submitDashboardPdfExport({ url, params = {}, charts = [], filterFormId = '' }) {
    if (!url) return;

    const restoreCanvases = revealExportCanvases(charts);

    try {
        await prepareSectionChartsForExport(charts);

        const formData = new FormData();
        formData.append('_token', csrfToken());

        const mergedParams = {
            ...params,
            ...readFilterFormParams(filterFormId),
        };

        if (!mergedParams.event_id && mergedParams.focus_event) {
            mergedParams.event_id = mergedParams.focus_event;
        }

        Object.entries(mergedParams).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') return;
            formData.append(key, String(value));
        });

        // Off-screen capture only — never switch Alpine tabs (avoids blank UI after export).
        captureDashboardChartImages(charts).forEach((chart, index) => {
            formData.append(`charts[${index}][title]`, chart.title);
            formData.append(`charts[${index}][image]`, chart.image);
            if (chart.section) {
                formData.append(`charts[${index}][section]`, chart.section);
            }
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
    } finally {
        restoreCanvases();
    }
}

export function bindDashboardPdfExportButtons(root = document) {
    root.querySelectorAll('[data-dashboard-pdf-export]').forEach((button) => {
        if (button.dataset.pdfExportBound === '1') return;
        button.dataset.pdfExportBound = '1';

        button.addEventListener('click', async (event) => {
            event.preventDefault();

            if (! ensureRequiredExportAssets(button)) {
                return;
            }

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
                    filterFormId: button.getAttribute('data-export-filter-form') || '',
                });
            } catch (error) {
                console.error(error);
                const message = error instanceof Error && error.message
                    ? error.message
                    : 'Unable to export PDF. Please try again.';
                window.alert(message);
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.innerHTML = originalLabel;
            }
        });
    });
}

/**
 * Shared empty-state helpers for dashboard/report Chart.js canvases.
 */

export function clearChartEmptyState(canvas) {
    if (!canvas?.parentElement) return;

    canvas.parentElement
        .querySelectorAll('[data-report-empty]')
        .forEach((el) => el.remove());

    canvas.style.display = '';
    canvas.removeAttribute('hidden');
}

export function showChartEmptyState(canvas, options = {}) {
    if (!canvas) return null;

    clearChartEmptyState(canvas);
    canvas.style.display = 'none';

    const host = canvas.parentElement;
    if (!host) return null;

    const title = options.title ?? 'No report data found.';
    const hint = options.hint ?? 'Try another date range or event.';

    const empty = document.createElement('div');
    empty.dataset.reportEmpty = '1';
    empty.setAttribute('role', 'status');
    empty.className = options.className
        ?? 'flex h-full min-h-[12rem] w-full flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-8 text-center';
    empty.innerHTML = `
        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200/80">
            <i class="bi bi-inbox text-lg" aria-hidden="true"></i>
        </div>
        <p class="mt-3 text-sm font-semibold text-slate-800">${title}</p>
        <p class="mt-1 max-w-xs text-xs text-slate-500">${hint}</p>
    `;
    host.appendChild(empty);

    return null;
}

export function isEmptySeries(values = []) {
    if (!Array.isArray(values) || values.length === 0) {
        return true;
    }

    return values.every((value) => {
        if (value === null || value === undefined) return true;
        if (typeof value === 'object') {
            const y = value.y ?? value.value ?? 0;
            return Number(y) === 0;
        }
        return Number(value) === 0;
    });
}

export function isEmptyChartInput(labels = [], data = []) {
    if (!Array.isArray(labels) || labels.length === 0) {
        return true;
    }

    return isEmptySeries(data);
}

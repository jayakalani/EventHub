@props([
    'route',
    'params' => [],
    'charts' => [],
])

@php
    $exportQuery = collect($params)
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
@endphp

<button type="button"
    data-dashboard-pdf-export
    data-export-url="{{ route($route) }}"
    data-export-params='@json($exportQuery)'
    data-export-charts='@json($charts)'
    {{ $attributes->merge([
        'class' => 'btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-rose-200 hover:bg-white/80 sm:text-sm disabled:cursor-wait disabled:opacity-70',
    ]) }}>
    <i class="bi bi-file-earmark-pdf text-rose-600"></i>
    <span class="hidden sm:inline">Export PDF</span>
    <span class="sm:hidden">PDF</span>
</button>

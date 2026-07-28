@props([
    'excelRoute',
    'pdfRoute',
    'section',
])

@php
    $exportQuery = array_filter([
        'section' => $section,
        'from' => request('from'),
        'to' => request('to'),
        'event_id' => request('event_id'),
        'status' => request('status'),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <a href="{{ route($excelRoute, $exportQuery) }}"
        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:bg-emerald-700 hover:shadow-md hover:-translate-y-0.5">
        <i class="bi bi-file-earmark-excel"></i>
        <span class="hidden sm:inline">Export Excel</span>
        <span class="sm:hidden">Excel</span>
    </a>
    <a href="{{ route($pdfRoute, $exportQuery) }}"
        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:bg-slate-50 hover:shadow-md hover:-translate-y-0.5">
        <i class="bi bi-file-earmark-pdf text-rose-600"></i>
        <span class="hidden sm:inline">Export PDF</span>
        <span class="sm:hidden">PDF</span>
    </a>
</div>

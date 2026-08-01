@props([
    'title' => 'No report data found.',
    'hint' => 'Try another date range or event.',
])

<div {{ $attributes->merge(['class' => 'flex h-full min-h-[12rem] flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-8 text-center']) }}
    data-report-empty="1"
    role="status">
    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200/80">
        <i class="bi bi-inbox text-lg" aria-hidden="true"></i>
    </div>
    <p class="mt-3 text-sm font-semibold text-slate-800">{{ $title }}</p>
    <p class="mt-1 max-w-xs text-xs text-slate-500">{{ $hint }}</p>
</div>

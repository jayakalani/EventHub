@props(['title', 'description', 'canvasId'])

<div {{ $attributes->merge(['class' => 'report-chart-card rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl']) }}>
    <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    <div class="mt-6 h-64 sm:h-72">
        <canvas id="{{ $canvasId }}"></canvas>
    </div>
</div>

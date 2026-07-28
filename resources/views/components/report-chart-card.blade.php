@props([
    'title',
    'description',
    'canvasId',
    'expandKey' => null,
    'expandTitle' => null,
    'expandDescription' => null,
])

@php
    $modalTitle = $expandTitle ?? $title;
    $modalDescription = $expandDescription ?? $description;
@endphp

<div {{ $attributes->merge(['class' => 'report-chart-card group relative rounded-2xl border border-white/60 bg-white/55 p-4 shadow-sm backdrop-blur-xl transition-all duration-300 ease-out sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="text-base font-bold text-slate-900">{{ $title }}</h3>
            <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
        </div>

        @if ($expandKey)
            <button type="button"
                @click="openChart('{{ $expandKey }}', @js($modalTitle), @js($modalDescription))"
                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90 hover:shadow-sm"
                title="View fullscreen"
                aria-label="View {{ $title }} fullscreen">
                <i class="bi bi-arrows-fullscreen text-xs"></i>
            </button>
        @endif
    </div>

    @if ($expandKey)
        <button type="button"
            @click="openChart('{{ $expandKey }}', @js($modalTitle), @js($modalDescription))"
            class="btn-smooth mt-4 block h-56 w-full cursor-pointer rounded-xl text-left hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-64"
            aria-label="Open {{ $title }} fullscreen">
            <canvas id="{{ $canvasId }}" class="pointer-events-none"></canvas>
        </button>
    @else
        <div class="mt-4 h-56 sm:h-64">
            <canvas id="{{ $canvasId }}"></canvas>
        </div>
    @endif
</div>

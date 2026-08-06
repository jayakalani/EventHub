@props([
    'name',
    'events' => [],
    'focusEventId' => null,
    'focusEventName' => null,
    'effectiveEventId' => null,
    'isOverride' => false,
    'query' => [],
    'accent' => 'indigo',
])

@php
    $focusLabel = $focusEventId
        ? ($focusEventName ?: 'Focused event')
        : 'All Events';

    $selected = $isOverride
        ? ($effectiveEventId ? (string) $effectiveEventId : 'all')
        : 'focus';

    $ring = match ($accent) {
        'emerald' => 'focus:border-emerald-500 focus:ring-emerald-500',
        'cyan' => 'focus:border-cyan-500 focus:ring-cyan-500',
        default => 'focus:border-indigo-500 focus:ring-indigo-500',
    };

    $preserve = collect($query)
        ->except([$name, 'focus_event'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->all();
@endphp

<div
    class="sm:w-64"
    x-data="{ open: {{ $isOverride ? 'true' : 'false' }} }"
>
    <div class="flex items-center justify-end gap-2">
        @if ($isOverride)
            <span class="hidden rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 sm:inline">
                Section only
            </span>
        @endif
        <button
            type="button"
            @click="open = !open"
            class="btn-smooth inline-flex items-center gap-1 rounded-lg border border-white/70 bg-white/55 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm backdrop-blur-sm hover:bg-white/80"
        >
            <i class="bi bi-sliders"></i>
            <span x-text="open ? 'Hide' : 'This section'"></span>
        </button>
    </div>

    <form
        method="GET"
        action="{{ route('organizer.dashboard') }}"
        class="mt-2"
        x-show="open"
        x-cloak
        x-transition
    >
        <input type="hidden" name="focus_event" value="{{ $focusEventId ?? '' }}">

        @foreach ($preserve as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <label for="{{ $name }}" class="sr-only">Apply event filter to this section only</label>
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md {{ $ring }}"
        >
            <option value="focus" @selected($selected === 'focus')>
                Match focus ({{ $focusLabel }})
            </option>
            <option value="all" @selected($selected === 'all')>
                All Events
            </option>
            @foreach ($events as $eventOption)
                <option
                    value="{{ $eventOption['id'] }}"
                    @selected($selected === (string) $eventOption['id'])
                >
                    {{ $eventOption['name'] }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-[10px] text-slate-400">
            Applies to this section only
        </p>
    </form>
</div>

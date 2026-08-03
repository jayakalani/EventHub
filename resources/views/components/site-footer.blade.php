@props([
    'homeHref' => null,
])

@php
    $homeHref = $homeHref ?? route('welcome');
    $columnHeading = 'text-xs font-semibold uppercase tracking-wider text-white';
    $tagline = t([
        'en' => 'Explore events. Book tickets. Get seats.',
        'si' => 'ප්‍රසංග සොයා බලන්න. ටිකට් වෙන්කරවා ගන්න. ආසන ලබා ගන්න.',
    ]);
@endphp

<footer {{ $attributes->class('mt-auto border-t border-white/10 bg-[#0A0247] text-slate-300') }}>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            {{-- Brand --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ $homeHref }}" class="group inline-flex items-center gap-3">
                    <img src="{{ asset('images/eventhub-logo.png') }}"
                        alt="{{ config('app.name', 'EventHub') }}"
                        class="h-9 w-auto object-contain brightness-0 invert transition duration-200 group-hover:opacity-90">
                    <div>
                        <span class="block text-base font-bold leading-tight tracking-tight text-white">
                            {{ config('app.name', 'EventHub') }}
                        </span>
                        <span class="mt-0.5 block max-w-[16rem] text-[11px] font-medium leading-snug text-slate-400">
                            {{ $tagline }}
                        </span>
                    </div>
                </a>
                <p class="mt-2 max-w-xs text-sm leading-snug text-slate-400">
                    {{ t([
                        'en' => 'Your trusted platform for discovering, booking, and managing events.',
                        'si' => 'ප්‍රසංග සොයා ගැනීම, වෙන්කරවා ගැනීම සහ කළමනාකරණය සඳහා ඔබේ විශ්වාසනීය වේදිකාව.',
                    ]) }}
                </p>
            </div>

            {{-- Explore --}}
            @isset($explore)
                <div>
                    <h3 class="{{ $columnHeading }}">{{ t(['en' => 'Explore', 'si' => 'ගවේෂණය']) }}</h3>
                    <ul class="mt-2.5 space-y-1.5">
                        {{ $explore }}
                    </ul>
                </div>
            @endisset

            {{-- Legal --}}
            @isset($legal)
                <div>
                    <h3 class="{{ $columnHeading }}">{{ t(['en' => 'Legal', 'si' => 'නීතිමය']) }}</h3>
                    <ul class="mt-2.5 space-y-1.5">
                        {{ $legal }}
                    </ul>
                </div>
            @endisset

            {{-- Account --}}
            @isset($account)
                <div>
                    <h3 class="{{ $columnHeading }}">{{ t(['en' => 'Account', 'si' => 'ගිණුම']) }}</h3>
                    <ul class="mt-2.5 space-y-1.5">
                        {{ $account }}
                    </ul>
                </div>
            @endisset
        </div>

        <div class="mt-6 flex flex-col items-center justify-between gap-2 border-t border-white/10 pt-4 text-sm text-slate-400 sm:flex-row">
            <p>
                &copy; {{ date('Y') }} {{ config('app.name', 'EventHub') }}.
                {{ t(['en' => 'All rights reserved.', 'si' => 'සියලු හිමිකම් ඇවිරිණි.']) }}
            </p>
            <p class="text-xs text-slate-500">
                {{ t(['en' => 'Built for organizers, attendees & support teams.', 'si' => 'සංවිධායකයන්, ප්‍රේක්ෂකයන් සහ සහාය කණ්ඩායම් සඳහා ගොඩනගන ලදී.']) }}
            </p>
        </div>
    </div>
</footer>

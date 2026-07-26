@php
    $footerLink = 'text-slate-600 transition hover:text-indigo-600';
    $footerActive = 'font-medium text-indigo-600';
@endphp

<footer class="mt-auto border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <i class="bi bi-calendar-event"></i>
            </span>
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? $footerActive : $footerLink }}">
                Dashboard
            </a>
            <a href="{{ route('organizer.events.index') }}"
                class="{{ request()->routeIs('organizer.events.*') ? $footerActive : $footerLink }}">
                Events
            </a>
            <a href="{{ route('organizer.hosts') }}"
                class="{{ request()->routeIs('organizer.hosts', 'organizer.hosts.*', 'organizer.host.*') ? $footerActive : $footerLink }}">
                Hosts
            </a>
            <a href="{{ route('organizer.reports') }}"
                class="{{ request()->routeIs('organizer.reports', 'organizer.reports.*') ? $footerActive : $footerLink }}">
                Reports
            </a>
            <a href="{{ route('about') }}"
                class="{{ request()->routeIs('about') ? $footerActive : $footerLink }}">
                About
            </a>
            <a href="{{ route('terms') }}"
                class="{{ request()->routeIs('terms') ? $footerActive : $footerLink }}">
                Terms
            </a>
            <a href="{{ route('privacy') }}"
                class="{{ request()->routeIs('privacy') ? $footerActive : $footerLink }}">
                Privacy
            </a>
        </div>
    </div>
</footer>

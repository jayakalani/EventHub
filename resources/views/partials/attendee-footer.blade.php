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
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ t(['en' => 'All rights reserved.', 'si' => 'සියලු හිමිකම් ඇවිරිණි.']) }}
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
            <a href="{{ route('attendee.dashboard') }}"
                class="{{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
            </a>
            <a href="{{ route('attendee.hosts.index') }}"
                class="{{ request()->routeIs('attendee.hosts.*') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Hosts', 'si' => 'සත්කාරක']) }}
            </a>
            <a href="{{ route('attendee.bookings.index') }}"
                class="{{ request()->routeIs('attendee.bookings.*') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
            </a>
            <a href="{{ route('attendee.support.index') }}"
                class="{{ request()->routeIs('attendee.support.*') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Support', 'si' => 'සහාය']) }}
            </a>
            <a href="{{ route('about') }}"
                class="{{ request()->routeIs('about') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'About', 'si' => 'අපි ගැන']) }}
            </a>
            <a href="{{ route('help') }}"
                class="{{ request()->routeIs('help', 'help.contact') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Help / FAQ', 'si' => 'උදව් / නිති ප්‍රශ්න']) }}
            </a>
            <a href="{{ route('terms') }}"
                class="{{ request()->routeIs('terms') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Terms', 'si' => 'කොන්දේසි']) }}
            </a>
            <a href="{{ route('privacy') }}"
                class="{{ request()->routeIs('privacy') ? $footerActive : $footerLink }}">
                {{ t(['en' => 'Privacy', 'si' => 'රහස්‍යතාව']) }}
            </a>
        </div>
    </div>
</footer>

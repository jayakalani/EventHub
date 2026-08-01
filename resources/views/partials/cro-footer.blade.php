@php
    $footerLink = 'text-slate-600 transition hover:text-indigo-600';
    $footerActive = 'font-medium text-indigo-600';
@endphp

<footer class="mt-auto border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <i class="bi bi-headset"></i>
            </span>
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
            <a href="{{ route('cro.dashboard') }}"
                class="{{ request()->routeIs('cro.dashboard') ? $footerActive : $footerLink }}">
                Dashboard
            </a>
            <a href="{{ route('cro.inquiries.index') }}"
                class="{{ request()->routeIs('cro.inquiries.*') ? $footerActive : $footerLink }}">
                Inquiries
            </a>
            <a href="{{ route('cro.complaints.index') }}"
                class="{{ request()->routeIs('cro.complaints.*') ? $footerActive : $footerLink }}">
                Complaints
            </a>
            <a href="{{ route('cro.refund-requests.index') }}"
                class="{{ request()->routeIs('cro.refund-requests.*') ? $footerActive : $footerLink }}">
                Refunds
            </a>
            <a href="{{ route('cro.reports') }}"
                class="{{ request()->routeIs('cro.reports', 'cro.reports.*') ? $footerActive : $footerLink }}">
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

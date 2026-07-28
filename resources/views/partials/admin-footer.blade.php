@php
    $footerLink = 'text-slate-600 transition hover:text-indigo-600';
    $footerActive = 'font-medium text-indigo-600';
@endphp

<footer class="mt-auto border-t border-slate-200/80 bg-white/70 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <i class="bi bi-shield-lock"></i>
            </span>
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? $footerActive : $footerLink }}">
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
                class="{{ request()->routeIs('admin.users', 'admin.user.*', 'admin.employees.*', 'admin.employee.*') ? $footerActive : $footerLink }}">
                Users
            </a>
            <a href="{{ route('admin.event-categories') }}"
                class="{{ request()->routeIs('admin.event-categories', 'admin.event-categories.*', 'admin.event.category.*') ? $footerActive : $footerLink }}">
                Categories
            </a>
            <a href="{{ route('admin.reports') }}"
                class="{{ request()->routeIs('admin.reports', 'admin.reports.*') ? $footerActive : $footerLink }}">
                Reports
            </a>
            <a href="{{ route('admin.support-reports') }}"
                class="{{ request()->routeIs('admin.support-reports', 'admin.support-reports.*') ? $footerActive : $footerLink }}">
                Support
            </a>
            <a href="{{ route('admin.audit-logs') }}"
                class="{{ request()->routeIs('admin.audit-logs', 'admin.audit-logs.*') ? $footerActive : $footerLink }}">
                Audit Logs
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

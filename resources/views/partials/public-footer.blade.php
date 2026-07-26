@php
    $eventsHref = request()->routeIs('welcome') ? '#events' : route('welcome') . '#events';
    $footerLink = 'text-slate-600 transition hover:text-primary dark:text-slate-400 dark:hover:text-primary-light';
    $footerActive = 'font-medium text-primary dark:text-primary-light';
@endphp

<footer class="relative z-10 border-t border-slate-200/80 bg-white/60 py-8 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-950/60">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <i class="bi bi-calendar-event"></i>
            </span>
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ t(['en' => 'All rights reserved.', 'si' => 'සියලු හිමිකම් ඇවිරිණි.']) }}
        </div>
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
            <a href="{{ $eventsHref }}"
                class="{{ request()->routeIs('welcome') ? $footerActive : $footerLink }}">{{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}</a>
            <a href="{{ route('about') }}"
                class="{{ request()->routeIs('about') ? $footerActive : $footerLink }}">{{ t(['en' => 'About', 'si' => 'අපි ගැන']) }}</a>
            <a href="{{ route('help') }}"
                class="{{ request()->routeIs('help', 'help.contact') ? $footerActive : $footerLink }}">{{ t(['en' => 'Help / FAQ', 'si' => 'උදව් / නිති ප්‍රශ්න']) }}</a>
            <a href="{{ route('terms') }}"
                class="{{ request()->routeIs('terms') ? $footerActive : $footerLink }}">{{ t(['en' => 'Terms', 'si' => 'කොන්දේසි']) }}</a>
            <a href="{{ route('privacy') }}"
                class="{{ request()->routeIs('privacy') ? $footerActive : $footerLink }}">{{ t(['en' => 'Privacy', 'si' => 'රහස්‍යතාව']) }}</a>
            @guest
                <a href="{{ route('login') }}"
                    class="{{ request()->routeIs('login') ? $footerActive : $footerLink }}">{{ t(['en' => 'Sign In', 'si' => 'පිවිසෙන්න']) }}</a>
                <a href="{{ route('register') }}"
                    class="{{ request()->routeIs('register') ? $footerActive : $footerLink }}">{{ t(['en' => 'Register', 'si' => 'ලියාපදිංචි වන්න']) }}</a>
            @else
                <a href="{{ route('dashboard') }}" class="{{ $footerLink }}">{{ t(['en' => 'Dashboard', 'si' => ' විස්තර පුවරුව']) }}</a>
            @endguest
        </div>
    </div>
</footer>

@props([
    'title' => null,
    'withLoginModal' => false,
])

@php
    $isAttendee = Auth::check()
        && Auth::user()?->userRole?->name_en === \App\Models\UserRole::ATTENDEE;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . config('app.name', 'EventHub') : config('app.name', 'EventHub') . ' — ' . t(['en' => 'Discover & Book Events', 'si' => 'ප්‍රසංග සොයා ගෙන වෙන්කරගන්න']) }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    @if ($withLoginModal)
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>

<body class="font-sans text-slate-900 antialiased dark:text-slate-100"
    @if ($withLoginModal)
        x-data="{
            showLoginModal: false,
            promptLogin() {
                this.showLoginModal = true;
                setTimeout(() => {
                    window.location.href = '{{ route('login') }}';
                }, 2500);
            }
        }"
    @endif>

    <div class="relative flex min-h-screen flex-col
        {{ $isAttendee
            ? 'bg-[#F0F8FF]'
            : 'bg-[#F0F8FF] dark:bg-gradient-to-br dark:from-gray-950 dark:via-gray-900 dark:to-slate-900' }}">

        @unless ($isAttendee)
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -top-40 -right-32 h-[28rem] w-[28rem] rounded-full bg-primary/10 blur-3xl dark:bg-primary/20"></div>
                <div class="absolute top-1/3 -left-40 h-80 w-80 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"></div>
                <div class="absolute -bottom-24 right-1/4 h-72 w-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
            </div>
        @endunless

        @if ($isAttendee)
            @include('layouts.navigation')
        @else
            @include('partials.public-header')
        @endif

        <main class="relative z-10 flex-1">
            {{ $slot }}
        </main>

        @if ($isAttendee)
            @include('partials.attendee-footer')
        @else
            @include('partials.public-footer')
        @endif
    </div>

    @if ($withLoginModal)
        <div x-show="showLoginModal"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <i class="bi bi-lock-fill text-2xl" aria-hidden="true"></i>
                </div>

                <h3 class="mt-4 text-center text-xl font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Login Required', 'si' => 'පිවිසීම අවශ්‍යයි']) }}</h3>
                <p class="mt-2 text-center text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t(['en' => 'Please sign in to like, save, or book events.', 'si' => 'ප්‍රසංග වලට ප්‍රතිචාර දැක්වීමට කරුණාකර පිවිසෙන්න.']) }}
                </p>
                <p class="mt-4 text-center text-xs text-slate-500">
                    {{ t(['en' => 'Redirecting you to the login page...', 'si' => 'පිවිසුම් පිටුවට යොමු කරමින්...']) }}
                </p>

                <a href="{{ route('login') }}"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-dark">
                    {{ t(['en' => 'Go to Login', 'si' => 'පිවිසුමට යන්න']) }}
                </a>
            </div>
        </div>
    @endif

    <script>
        (function() {
            const html = document.documentElement;
            const stored = localStorage.getItem('color-mode');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                html.classList.add('dark');
            }
            window.toggleColorMode = () => {
                if (html.classList.toggle('dark')) {
                    localStorage.setItem('color-mode', 'dark');
                } else {
                    localStorage.setItem('color-mode', 'light');
                }
            };
        })();
    </script>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        [x-cloak] { display: none !important; }
    </style>

    {{ $scripts ?? '' }}
</body>

</html>

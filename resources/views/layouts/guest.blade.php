<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EventHub') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Bootstrap icons (used in auth pages) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased dark:text-gray-100">
    <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 py-8 overflow-hidden
        bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50/60
        dark:from-gray-950 dark:via-gray-900 dark:to-slate-900">

        {{-- Ambient background shapes --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-primary/10 blur-3xl dark:bg-primary/20"></div>
            <div class="absolute top-1/2 -left-40 h-80 w-80 -translate-y-1/2 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"></div>
            <div class="absolute -bottom-24 right-1/4 h-72 w-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%232563eb%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-60 dark:opacity-30"></div>
        </div>

        <div class="relative z-10 mt-4 mb-6 sm:mb-8">
            <a href="/"
                class="group inline-flex items-center gap-2.5 rounded-2xl bg-white/70 px-5 py-2.5 text-xl font-bold text-primary shadow-sm ring-1 ring-gray-200/80 backdrop-blur-sm transition-all duration-300 hover:bg-white hover:shadow-md hover:ring-primary/20 dark:bg-gray-800/70 dark:text-primary-light dark:ring-gray-700/80 dark:hover:bg-gray-800 dark:hover:ring-primary-light/30">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-light text-sm text-white shadow-sm transition-transform duration-300 group-hover:scale-110">
                    <i class="bi bi-calendar-event"></i>
                </span>
                {{ config('app.name') }}
            </a>
        </div>

        <div class="relative z-10 w-full max-w-6xl mt-1 px-0 py-0 bg-transparent shadow-none overflow-visible rounded-none">
            {{ $slot }}
        </div>
    </div>

    <script>
        // color mode handling
        (function() {
            const html = document.documentElement;
            const stored = localStorage.getItem('color-mode');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                html.classList.add('dark');
            }
            // optional: expose toggle function
            window.toggleColorMode = () => {
                if (html.classList.toggle('dark')) {
                    localStorage.setItem('color-mode', 'dark');
                } else {
                    localStorage.setItem('color-mode', 'light');
                }
            };
        })();
    </script>
</body>

</html>

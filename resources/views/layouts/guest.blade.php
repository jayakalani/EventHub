<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EventHub') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-slate-900 antialiased dark:text-slate-100">
    <div class="relative flex min-h-screen flex-col
        bg-[#F0F8FF]
        dark:bg-gradient-to-br dark:from-gray-950 dark:via-gray-900 dark:to-slate-900">

        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-primary/10 blur-3xl dark:bg-primary/20"></div>
            <div class="absolute top-1/2 -left-40 h-80 w-80 -translate-y-1/2 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"></div>
            <div class="absolute -bottom-24 right-1/4 h-72 w-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
        </div>

        @include('partials.public-header')

        <div class="relative z-10 flex flex-1 flex-col items-center justify-center py-8">
            <div class="w-full max-w-6xl">
                {{ $slot }}
            </div>
        </div>

        @include('partials.public-footer')
    </div>

    <x-scroll-to-top />

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
</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap icons (used in auth pages) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased dark:text-gray-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 py-8 bg-gray-100 dark:bg-gray-900">
            <div class="mt-5 mb-5">
                <a href="/" class="text-2xl font-bold text-primary dark:text-primary-light hover:opacity-80 transition">
                    {{ config('app.name') }}
                </a>
            </div>

            <div class="w-full mt-1 px-0 py-0 bg-transparent shadow-none overflow-visible rounded-none">
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
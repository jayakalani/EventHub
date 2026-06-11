<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" :class="darkMode ? 'dark' : ''">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EventHub</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Alpine.js (for dark mode toggle) -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 dark:bg-[#0a0a0a] text-gray-800 dark:text-gray-200 min-h-screen"
    x-data="{
        showLoginModal: false,
        promptLogin() {
            this.showLoginModal = true;
            setTimeout(() => {
                window.location.href = '{{ route('login') }}';
            }, 2500);
        }
    }">

    <!-- 🌐 NAVBAR -->
    {{--
    <nav class="flex justify-between items-center px-6 lg:px-12 py-4 backdrop-blur-md bg-white/70 dark:bg-[#161615]/70 sticky top-0 z-50 border-b border-gray-200 dark:border-gray-800">
        
        <!-- Logo -->
        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            EventHub
        </h1>

        <!-- Right -->
        <div class="flex items-center gap-4">            

            @auth
                <a href="/dashboard" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium">Sign In</a>
                <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium"> Register</a>
            @endauth

        </div>
    </nav>
    --}}

    <div class="min-h-screen bg-slate-50 text-slate-900">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 py-6">
                <div>
                    <h1
                        class="text-3xl sm:text-4xl font-extrabold tracking-tight bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        EventHub</h1>
                    <p class="mt-2 text-slate-600 max-w-xl">Discover curated events with a modern event experience.</p>
                </div>

                <div class="flex gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">Login</a>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">Register</a>
                    @endauth
                </div>
            </header>

            <section
                class="relative overflow-hidden rounded-[32px] bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600 px-6 py-16 text-white shadow-2xl sm:px-12 sm:py-20">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.25),_transparent_35%)] opacity-70">
                </div>
                <div class="relative">
                    <span
                        class="inline-flex rounded-full bg-white/10 px-4 py-1 text-sm font-semibold uppercase tracking-[0.24em] text-white/90">Your
                        Gateway to Every Event</span>
                    <h2 class="mt-6 text-4xl sm:text-5xl font-semibold tracking-tight"> Enjoy a seamless event
                        experience with everything you need at your fingertips.</h2>
                    <p class="mt-4 max-w-2xl text-slate-100/90 text-lg leading-8">Register, explore, and enjoy seamless
                        event experiences all in one place.</p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-950/10 transition hover:bg-slate-100">All
                            events</a>
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-full border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">Browse
                            events</a>
                    </div>
                </div>
            </section>

            <section class="mt-12 pb-12">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Explore events</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Browse upcoming events</h3>
                    <p class="mt-2 text-slate-600">Filter by category and discover your next experience.</p>
                </div>

                @include('partials.events-browse', ['browseRouteName' => 'welcome'])
            </section>
        </div>
    </div>

    {{-- Login required modal --}}
    <div x-show="showLoginModal"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <i class="bi bi-lock-fill text-2xl" aria-hidden="true"></i>
            </div>

            <h3 class="mt-4 text-center text-xl font-bold text-slate-900">Login Required</h3>
            <p class="mt-2 text-center text-sm leading-relaxed text-slate-600">
                Please login first to perform this action.
            </p>

            <p class="mt-4 text-center text-xs text-slate-500">
                Redirecting you to the login page...
            </p>

            <a href="{{ route('login') }}"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Go to Login
            </a>
        </div>
    </div>

</body>

</html>

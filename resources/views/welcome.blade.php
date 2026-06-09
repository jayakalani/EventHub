<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" :class="darkMode ? 'dark' : ''">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EventHub</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Alpine.js (for dark mode toggle) -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 dark:bg-[#0a0a0a] text-gray-800 dark:text-gray-200 min-h-screen">

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

            <section class="mt-12 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Explore events</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Featured event categories</h3>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            class="rounded-full border border-slate-200 bg-slate-100 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">All</button>
                        <select
                            class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm outline-none focus:border-blue-500"
                            aria-label="Event category">
                            <option>Music</option>
                            <option>Sports</option>
                            <option>Workshops</option>
                            <option>Networking</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg">
                        <span
                            class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Music</span>
                        <h4 class="mt-5 text-xl font-semibold text-slate-900">Live Concert Night</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">An immersive evening with local artists, great
                            food, and premium ticketing.</p>
                        <div class="mt-6 flex flex-wrap gap-3 text-sm text-slate-500">
                            <span>📅 May 10, 2026</span>
                            <span>🕒 6:00 PM</span>
                            <span>📍 Colombo Hall</span>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg">
                        <span
                            class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Workshops</span>
                        <h4 class="mt-5 text-xl font-semibold text-slate-900">Digital Marketing Bootcamp</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Hands-on workshop designed to help event
                            organizers grow their audience.</p>
                        <div class="mt-6 flex flex-wrap gap-3 text-sm text-slate-500">
                            <span>📅 May 15, 2026</span>
                            <span>🕒 4:00 PM</span>
                            <span>📍 Nawala Grounds</span>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 transition hover:-translate-y-1 hover:shadow-lg">
                        <span
                            class="inline-flex rounded-full bg-pink-100 px-3 py-1 text-xs font-semibold text-pink-700">Sports</span>
                        <h4 class="mt-5 text-xl font-semibold text-slate-900">Charity Run Meetup</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Support local communities with a scenic run and
                            post-event networking.</p>
                        <div class="mt-6 flex flex-wrap gap-3 text-sm text-slate-500">
                            <span>📅 May 22, 2026</span>
                            <span>🕒 7:00 AM</span>
                            <span>📍 Galle Face Green</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

</body>

</html>

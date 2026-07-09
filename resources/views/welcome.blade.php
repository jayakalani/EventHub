<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'EventHub') }} — Discover & Book Events</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-slate-900 antialiased dark:text-slate-100"
    x-data="{
        showLoginModal: false,
        promptLogin() {
            this.showLoginModal = true;
            setTimeout(() => {
                window.location.href = '{{ route('login') }}';
            }, 2500);
        }
    }">

    <div class="relative min-h-screen overflow-hidden
        bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50/60
        dark:from-gray-950 dark:via-gray-900 dark:to-slate-900">

        {{-- Ambient background --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -right-32 h-[28rem] w-[28rem] rounded-full bg-primary/10 blur-3xl dark:bg-primary/20"></div>
            <div class="absolute top-1/3 -left-40 h-80 w-80 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"></div>
            <div class="absolute -bottom-24 right-1/4 h-72 w-72 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60 dark:opacity-20"></div>
        </div>

        {{-- Navbar --}}
        <nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="group flex items-center gap-3 shrink-0">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-light text-white shadow-md shadow-primary/25 transition-transform duration-300 group-hover:scale-105">
                        <i class="bi bi-calendar-event text-lg"></i>
                    </span>
                    <div class="hidden sm:block">
                        <p class="text-lg font-bold text-slate-900 dark:text-white">{{ config('app.name') }}</p>
                        <p class="-mt-0.5 text-xs text-slate-500 dark:text-slate-400">Discover & book events</p>
                    </div>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="#events"
                        class="hidden sm:inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        <i class="bi bi-grid-3x3-gap text-primary"></i>
                        Events
                    </a>

                    <button type="button" onclick="window.toggleColorMode?.()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars dark:hidden"></i>
                        <i class="bi bi-sun hidden dark:inline"></i>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition hover:bg-primary-dark">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="hidden sm:inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition hover:bg-primary-dark">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            {{-- Hero --}}
            <section class="mx-auto max-w-7xl px-4 pt-10 pb-16 sm:px-6 sm:pt-14 sm:pb-20 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div class="animate-[fadeInUp_0.7s_ease-out]">
                        <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-primary dark:border-primary/30 dark:bg-primary/10 dark:text-primary-light">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                            </span>
                            Live events near you
                        </span>

                        <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white">
                            Your gateway to
                            <span class="bg-gradient-to-r from-primary via-indigo-600 to-violet-600 bg-clip-text text-transparent">
                                unforgettable events
                            </span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600 dark:text-slate-300">
                            Browse concerts, workshops, and experiences. Book tickets in seconds, save your favorites, and never miss what matters.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a href="#events"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-dark hover:shadow-xl hover:shadow-primary/35">
                                <i class="bi bi-search"></i>
                                Browse Events
                            </a>
                            @guest
                                <a href="{{ route('register') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                                    <i class="bi bi-person-plus"></i>
                                    Create Free Account
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                                    <i class="bi bi-speedometer2"></i>
                                    Go to Dashboard
                                </a>
                            @endguest
                        </div>

                        {{-- Stats --}}
                        <dl class="mt-10 grid grid-cols-3 gap-4 sm:gap-6">
                            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/60">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Events</dt>
                                <dd class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $events->count() }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/60">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Categories</dt>
                                <dd class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $eventCategories->count() }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/60">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Booking</dt>
                                <dd class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Fast</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Hero visual --}}
                    <div class="relative hidden lg:block animate-[fadeInUp_0.9s_ease-out]">
                        <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-br from-primary/20 via-transparent to-violet-400/20 blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/80 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/80">
                            <div class="grid grid-cols-2 gap-4">
                                @forelse($events->take(4) as $index => $event)
                                    <div class="group overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800 {{ $index === 0 ? 'col-span-2' : '' }}">
                                        @if ($event->cover)
                                            <div class="{{ $index === 0 ? 'h-40' : 'h-28' }} overflow-hidden">
                                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                                    alt="{{ $event->name }}"
                                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                            </div>
                                        @else
                                            <div class="{{ $index === 0 ? 'h-40' : 'h-28' }} flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800">
                                                <i class="bi bi-image text-2xl text-slate-400"></i>
                                            </div>
                                        @endif
                                        <div class="p-3">
                                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $event->name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                                <i class="bi bi-calendar3 mr-1"></i>{{ $event->date }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-2 flex h-64 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-slate-500 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-400">
                                        <i class="bi bi-calendar-x text-4xl mb-3"></i>
                                        <p class="text-sm font-medium">Events coming soon</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Floating badges --}}
                            <div class="absolute -bottom-4 -left-4 flex items-center gap-2 rounded-2xl border border-slate-200/80 bg-white/95 px-4 py-2.5 text-sm font-medium text-slate-700 shadow-lg backdrop-blur-md dark:border-slate-700 dark:bg-slate-800/95 dark:text-slate-200">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                    <i class="bi bi-ticket-perforated-fill"></i>
                                </span>
                                Instant ticket booking
                            </div>
                            <div class="absolute -top-3 -right-3 flex items-center gap-2 rounded-2xl border border-slate-200/80 bg-white/95 px-4 py-2.5 text-sm font-medium text-slate-700 shadow-lg backdrop-blur-md dark:border-slate-700 dark:bg-slate-800/95 dark:text-slate-200">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <i class="bi bi-shield-check"></i>
                                </span>
                                Secure checkout
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features --}}
            <section class="border-y border-slate-200/80 bg-white/50 py-14 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/40">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">Why EventHub</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl dark:text-white">Everything you need in one place</h2>
                    </div>

                    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900 dark:hover:border-primary/40">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">
                                <i class="bi bi-search text-xl"></i>
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">Discover Events</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">Filter by category and find experiences tailored to your interests.</p>
                        </div>

                        <div class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900 dark:hover:border-primary/40">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 transition group-hover:bg-violet-600 group-hover:text-white">
                                <i class="bi bi-ticket-perforated text-xl"></i>
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">Book Tickets</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">Secure your spot with a fast, streamlined booking flow.</p>
                        </div>

                        <div class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900 dark:hover:border-primary/40">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                                <i class="bi bi-bookmark-heart text-xl"></i>
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">Save Favorites</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">Like and bookmark events to build your personal wishlist.</p>
                        </div>

                        <div class="group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900 dark:hover:border-primary/40">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                                <i class="bi bi-bell text-xl"></i>
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">Stay Updated</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">Get notified about new events and changes from your favorite hosts.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Events browse --}}
            <section id="events" class="scroll-mt-24 mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">Explore events</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">Upcoming events</h2>
                        <p class="mt-2 max-w-xl text-slate-600 dark:text-slate-400">
                            Filter by category and discover your next experience.
                        </p>
                    </div>
                    @guest
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <i class="bi bi-info-circle mr-1"></i>
                            Sign in to like, save, and book tickets.
                        </p>
                    @endguest
                </div>

                @include('partials.events-browse', ['browseRouteName' => 'welcome'])
            </section>

            {{-- CTA banner --}}
            @guest
                <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-slate-900 via-indigo-800 to-primary px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-14">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.15),_transparent_50%)]"></div>
                        <div class="relative">
                            <h2 class="text-2xl font-bold sm:text-3xl">Ready to book your next event?</h2>
                            <p class="mx-auto mt-3 max-w-lg text-slate-200">
                                Join EventHub today and unlock ticket booking, saved events, and personalized recommendations.
                            </p>
                            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <a href="{{ route('register') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                                    <i class="bi bi-rocket-takeoff"></i>
                                    Get Started Free
                                </a>
                                <a href="{{ route('login') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                                    I already have an account
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endguest
        </main>

        {{-- Footer --}}
        <footer class="relative z-10 border-t border-slate-200/80 bg-white/60 py-8 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-950/60">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <i class="bi bi-calendar-event"></i>
                    </span>
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
                <div class="flex items-center gap-6 text-sm">
                    <a href="#events" class="text-slate-600 transition hover:text-primary dark:text-slate-400 dark:hover:text-primary-light">Events</a>
                    @guest
                        <a href="{{ route('login') }}" class="text-slate-600 transition hover:text-primary dark:text-slate-400 dark:hover:text-primary-light">Sign In</a>
                        <a href="{{ route('register') }}" class="text-slate-600 transition hover:text-primary dark:text-slate-400 dark:hover:text-primary-light">Register</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="text-slate-600 transition hover:text-primary dark:text-slate-400 dark:hover:text-primary-light">Dashboard</a>
                    @endguest
                </div>
            </div>
        </footer>
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

        <div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                <i class="bi bi-lock-fill text-2xl" aria-hidden="true"></i>
            </div>

            <h3 class="mt-4 text-center text-xl font-bold text-slate-900 dark:text-white">Login Required</h3>
            <p class="mt-2 text-center text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                Please sign in to like, save, or book events.
            </p>

            <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-500">
                Redirecting you to the login page...
            </p>

            <a href="{{ route('login') }}"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-dark">
                Go to Login
            </a>
        </div>
    </div>

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
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</body>

</html>

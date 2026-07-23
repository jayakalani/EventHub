<x-public-layout :with-login-modal="true">
    @include('partials.events-carousel')

    {{-- Events browse --}}
    <section id="events" class="scroll-mt-24 mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'Explore events', 'si' => 'අත්දැකීම් ගවේෂණය']) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Upcoming events', 'si' => 'ඉදිරි අත්දැකීම්']) }}</h2>
            </div>
            @guest
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <i class="bi bi-info-circle mr-1"></i>
                    {{ t(['en' => 'Sign in to like, save, and book tickets.', 'si' => 'කැමති වීමට, සුරැකීමට සහ ටිකට් වෙන්කරවා ගැනීමට පිවිසෙන්න.']) }}
                </p>
            @endguest
        </div>

        @include('partials.events-browse', ['browseRouteName' => 'welcome', 'compact' => true])
    </section>

    {{-- CTA banner --}}
    @guest
        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-slate-900 via-indigo-800 to-primary px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-14">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.15),_transparent_50%)]"></div>
                <div class="relative">
                    <h2 class="text-2xl font-bold sm:text-3xl">{{ t(['en' => 'Ready to book your next event?', 'si' => 'ඔබේ ඊළඟ අත්දැකීම වෙන්කරවා ගැනීමට සූදානම්ද?']) }}</h2>
                    <p class="mx-auto mt-3 max-w-lg text-slate-200">
                        {{ t(['en' => 'Join EventHub today and unlock ticket booking, saved events, and personalized recommendations.', 'si' => 'අදම EventHub හා එක්වී ටිකට් වෙන්කිරීම, සුරකින ලද අත්දැකීම් සහ පුද්ගලායන නිර්දේශ ලබා ගන්න.']) }}
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                            <i class="bi bi-rocket-takeoff"></i>
                            {{ t(['en' => 'Get Started Free', 'si' => 'නොමිලේ ආරම්භ කරන්න']) }}
                        </a>
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                            {{ t(['en' => 'I already have an account', 'si' => 'මට දැනටමත් ගිණුමක් තිබේ']) }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endguest
</x-public-layout>

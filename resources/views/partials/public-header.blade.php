@php
    use App\Support\Locale;

    $eventsHref = request()->routeIs('welcome') ? '#events' : route('welcome') . '#events';
    $navLink = 'hidden sm:inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition';
    $navIdle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
    $navActive = 'bg-slate-100 font-semibold text-slate-900 dark:bg-slate-800 dark:text-white';
    $currentLocale = Locale::current();
@endphp

<nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('welcome') }}" class="group flex shrink-0 items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-light text-white shadow-md shadow-primary/25 transition-transform duration-300 group-hover:scale-105">
                <i class="bi bi-calendar-event text-lg"></i>
            </span>
            <div class="hidden sm:block">
                <p class="text-lg font-bold text-slate-900 dark:text-white">{{ config('app.name') }}</p>
                <p class="-mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ t(['en' => 'Discover & book events', 'si' => 'අත්දැකීම් සොයා ගෙන වෙන්කරගන්න']) }}</p>
            </div>
        </a>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ $eventsHref }}"
                class="{{ $navLink }} {{ request()->routeIs('welcome') ? $navActive : $navIdle }}">
                <i class="bi bi-grid-3x3-gap text-primary"></i>
                {{ t(['en' => 'Events', 'si' => 'අත්දැකීම්']) }}
            </a>
            <a href="{{ route('about') }}"
                class="{{ $navLink }} {{ request()->routeIs('about') ? $navActive : $navIdle }}">
                <i class="bi bi-info-circle text-primary"></i>
                {{ t(['en' => 'About', 'si' => 'අපි ගැන']) }}
            </a>

            <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 text-xs font-semibold dark:border-slate-700 dark:bg-slate-900"
                role="group"
                aria-label="{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}">
                @foreach (Locale::SUPPORTED as $locale)
                    <a href="{{ route('locale.switch', $locale) }}"
                        class="rounded-lg px-2.5 py-1.5 transition {{ $currentLocale === $locale
                            ? 'bg-primary text-white shadow-sm'
                            : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
                        hreflang="{{ $locale }}"
                        lang="{{ $locale }}">
                        {{ $locale === 'en' ? 'EN' : 'සිං' }}
                    </a>
                @endforeach
            </div>

            <button type="button" onclick="window.toggleColorMode?.()"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                aria-label="{{ t(['en' => 'Toggle dark mode', 'si' => 'අඳුරු ප්‍රකාරය මාරු කරන්න']) }}">
                <i class="bi bi-moon-stars dark:hidden"></i>
                <i class="bi bi-sun hidden dark:inline"></i>
            </button>

            @auth
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition hover:bg-primary-dark">
                    {{ t(['en' => 'Dashboard', 'si' => 'උපකරණ පුවරුව']) }}
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="hidden sm:inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 {{ request()->routeIs('login') ? 'ring-2 ring-primary/30' : '' }}">
                    {{ t(['en' => 'Sign In', 'si' => 'පිවිසෙන්න']) }}
                </a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/25 transition hover:bg-primary-dark">
                    {{ t(['en' => 'Get Started', 'si' => 'ආරම්භ කරන්න']) }}
                </a>
            @endauth
        </div>
    </div>
</nav>

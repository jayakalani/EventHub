@php
    use App\Support\Locale;

    $eventsHref = request()->routeIs('welcome') ? '#events' : route('welcome') . '#events';
    $navLink = 'hidden sm:inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-medium transition-all duration-200 ease-out';
    $navIdle = 'text-slate-600 hover:-translate-y-0.5 hover:bg-white/70 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white';
    $navActive = 'bg-white/80 font-semibold text-[#0F0363] shadow-sm shadow-[#0F0363]/10 ring-1 ring-[#0F0363]/15 dark:bg-white/10 dark:text-white dark:ring-white/20';
    $currentLocale = Locale::current();
@endphp

<nav class="sticky top-0 z-40 border-b border-white/40 bg-white shadow-lg shadow-[#0F0363]/5 backdrop-blur-2xl dark:border-white/10 dark:bg-slate-950/55 dark:shadow-black/20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">

            {{-- Brand --}}
            <a href="{{ route('welcome') }}" class="group flex shrink-0 items-center gap-3 transition-transform duration-200 hover:scale-[1.02]">
                <img src="{{ asset('images/eventhub-logo.png') }}"
                    alt="{{ config('app.name', 'EventHub') }}"
                    class="h-8 w-auto object-contain transition duration-200 group-hover:drop-shadow-md sm:h-9">
                <div class="hidden sm:block">
                    <span class="block text-base font-bold leading-tight tracking-tight text-[#0F0363] transition-colors duration-200 group-hover:text-[#1a0a8a] dark:text-white">EventHub</span>
                    <span class="block text-[11px] font-medium leading-tight text-slate-500 transition-colors duration-200 group-hover:text-[#0F0363]/70 dark:text-slate-400">
                        {{ t(['en' => 'Explore events. Book tickets. Get seats.', 'si' => 'ප්‍රසංග සොයා බලන්න. ටිකට් වෙන්කරවා ගන්න. ආසන ලබා ගන්න.']) }}
                    </span>
                </div>
            </a>

            {{-- Links + actions --}}
            <div class="flex items-center gap-1.5 sm:gap-2">
                <a href="{{ $eventsHref }}"
                    class="{{ $navLink }} {{ request()->routeIs('welcome') ? $navActive : $navIdle }}">
                    {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                </a>
                <a href="{{ route('about') }}"
                    class="{{ $navLink }} {{ request()->routeIs('about') ? $navActive : $navIdle }}">
                    {{ t(['en' => 'About', 'si' => 'අපි ගැන']) }}
                </a>

                <span class="mx-1 hidden h-5 w-px bg-slate-300/60 dark:bg-slate-600/60 sm:block" aria-hidden="true"></span>

                <div class="inline-flex items-center rounded-xl border border-white/50 bg-white/40 p-0.5 text-xs font-semibold shadow-sm backdrop-blur-md transition-all duration-200 hover:bg-white/60 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10"
                    role="group"
                    aria-label="{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}">
                    @foreach (Locale::SUPPORTED as $locale)
                        <a href="{{ route('locale.switch', $locale) }}"
                            class="rounded-lg px-2.5 py-1.5 transition-all duration-200 {{ $currentLocale === $locale
                                ? 'bg-[#0F0363] text-white shadow-md shadow-[#0F0363]/25'
                                : 'text-slate-600 hover:bg-white/80 hover:text-[#0F0363] dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white' }}"
                            hreflang="{{ $locale }}"
                            lang="{{ $locale }}">
                            {{ $locale === 'en' ? 'EN' : 'සිං' }}
                        </a>
                    @endforeach
                </div>

                <button type="button" onclick="window.toggleColorMode?.()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-slate-600 shadow-sm backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0F0363]/20 hover:bg-white/70 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                    aria-label="{{ t(['en' => 'Toggle dark mode', 'si' => 'අඳුරු ප්‍රකාරය මාරු කරන්න']) }}">
                    <i class="bi bi-moon-stars dark:hidden"></i>
                    <i class="bi bi-sun hidden dark:inline"></i>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-[#0F0363]/90 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#0F0363]/25 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#0F0363] hover:shadow-lg hover:shadow-[#0F0363]/30">
                        {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex items-center justify-center rounded-xl border border-white/60 bg-white/45 px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0F0363]/25 hover:bg-white/80 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10 {{ request()->routeIs('login') ? 'ring-2 ring-[#0F0363]/25' : '' }}">
                        {{ t(['en' => 'Sign In', 'si' => 'පිවිසෙන්න']) }}
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-[#0F0363]/90 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#0F0363]/25 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#0F0363] hover:shadow-lg hover:shadow-[#0F0363]/30">
                        {{ t(['en' => 'Get Started', 'si' => 'ලියාපදිංචි වන්න']) }}
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

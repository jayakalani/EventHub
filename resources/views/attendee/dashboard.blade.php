<x-app-layout>
    @if (session('welcome_back'))
        <div x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="fixed inset-x-0 top-20 z-[100] flex justify-center px-4 sm:top-24">
            <div class="flex max-w-md items-center gap-3 rounded-2xl border border-primary/20 bg-white px-5 py-3.5 shadow-xl shadow-slate-900/15 ring-1 ring-black/5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <i class="bi bi-emoji-smile" aria-hidden="true"></i>
                </span>
                <p class="text-sm font-semibold text-slate-900">
                    {{ t(['en' => 'Welcome back,', 'si' => 'නැවත සාදරයෙන් පිළිගනිමු,']) }}
                    <span class="text-primary">{{ Auth::user()->first_name }}</span>
                </p>
            </div>
        </div>
    @endif

    <div class="relative overflow-hidden bg-gradient-to-b from-slate-50 via-white to-blue-50/40">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(ellipse_at_top,_rgba(37,99,235,0.08),_transparent_60%)]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

            @if (! empty($selectedArtist))
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 shadow-sm shadow-primary/10 backdrop-blur-sm">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-white">
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                        </span>
                        <p class="text-sm text-slate-800">
                            {{ t(['en' => 'Showing events featuring', 'si' => 'මෙම කලාකරු සහිත ප්‍රසංග']) }}
                            <span class="font-semibold">{{ $selectedArtist->name }}</span>
                        </p>
                    </div>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-1.5 text-sm font-semibold text-primary-dark shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/5">
                        <i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                        {{ t(['en' => 'Clear filter', 'si' => 'තේරීම ඉවත් කරන්න']) }}
                    </a>
                </div>
            @endif

            {{-- Search toolbar --}}
            
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                <div
                    class="relative flex flex-col gap-4 overflow-hidden border-b border-[#0F0363]/50 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);">
                    <div
                        class="pointer-events-none absolute inset-0"
                        style="background:
                            radial-gradient(ellipse 90% 70% at 100% -10%, rgba(42, 21, 133, 0.45) 0%, transparent 55%),
                            radial-gradient(ellipse 70% 60% at 0% 110%, rgba(2, 3, 31, 0.75) 0%, transparent 50%),
                            linear-gradient(160deg, transparent 25%, rgba(15, 3, 99, 0.35) 55%, transparent 80%);"
                        aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -right-10 top-0 h-40 w-40 rounded-full bg-[#2A1585]/50 blur-3xl" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -left-8 bottom-0 h-32 w-32 rounded-full bg-[#02031F]/80 blur-2xl" aria-hidden="true"></div>
                    <div class="relative min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-200/90">
                            {{ t(['en' => 'Explore', 'si' => 'ගවේෂණය']) }}
                        </p>
                        <h2 class="mt-0.5 text-lg font-semibold tracking-tight sm:text-xl">
                            {{ t(['en' => 'Discover Amazing Events', 'si' => 'විශිෂ්ට ප්‍රසංග සොයන්න']) }}
                        </h2>

                        <p class="mt-0.5 text-sm text-violet-100/90">
                            {{ t(['en' => 'Explore concerts, workshops, conferences, sports events and more.', 'si' => 'සංගීත ප්‍රසංග, වැඩමුළු, සම්මන්ත්‍රණ, ක්‍රීඩා ප්‍රසංග සහ තවත් බොහෝ දේ ගවේෂණය කරන්න.']) }}
                        </p>
                    </div>

                    <form action="{{ route('attendee.dashboard') }}" method="GET" class="relative w-full lg:max-w-xl lg:shrink-0">

                        <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]">

                            @if (request('artist'))
                                <input type="hidden" name="artist" value="{{ request('artist') }}">
                            @endif

                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ t(['en' => 'Event Name', 'si' => 'ප්‍රසංගයේ නම']) }}" class="rounded-xl border-0 px-3.5 py-2 text-sm text-slate-800 shadow">

                            <input type="date" name="date" value="{{ request('date') }}"
                                class="rounded-xl border-0 px-3.5 py-2 text-sm text-slate-800 shadow">

                            <button type="submit"
                                class="rounded-xl bg-white px-5 py-2 text-sm font-semibold text-[#0F0363] shadow hover:bg-violet-50 transition">
                                {{ t(['en' => 'Search', 'si' => 'සොයන්න']) }}
                            </button>

                            <a href="{{ route('attendee.dashboard', array_filter(['artist' => request('artist')])) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-white/40 bg-white/10 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20">
                                {{ t(['en' => 'Reset', 'si' => 'යළි සකසන්න']) }}
                            </a>

                        </div>

                    </form>
                </div>
            </section>

            @include('attendee.partials.upcoming-this-week')

            @include('attendee.partials.rating-nudge')

            {{-- Browse events --}}
            <section>
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                            {{ t(['en' => 'Browse', 'si' => 'බ්‍රවුස්']) }}
                        </p>
                        <h2 class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                            {{ t(['en' => 'Upcoming events', 'si' => 'ඉදිරි ප්‍රසංග']) }}
                        </h2>
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ t(['en' => 'Filter by category, then open an event to book.', 'si' => 'වර්ගය අනුව පෙරහන් කර වෙන්කිරීමට ප්‍රසංගයක් විවෘත කරන්න.']) }}
                    </p>
                </div>

                @include('partials.events-browse', ['compact' => true])
            </section>

            @if ($pastEvents->isNotEmpty())
                <section class="pt-2">
                    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                {{ t(['en' => 'History', 'si' => 'ඉතිහාසය']) }}
                            </p>
                            <h2 class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                                {{ t(['en' => 'Past Events', 'si' => 'අතීත ප්‍රසංග']) }}
                            </h2>
                        </div>
                        <p class="text-sm text-slate-600">
                            {{ t(['en' => 'Completed events you can still revisit for details, tickets, and feedback.', 'si' => 'අවසන් වූ ප්‍රසංග විස්තර, ටිකට් සහ ප්‍රතිචාර නැවත බැලිය හැකිය.']) }}
                        </p>
                    </div>

                    @include('partials.events-browse', [
                        'events' => $pastEvents,
                        'browseSection' => 'past',
                        'compact' => true,
                    ])
                </section>
            @endif

        </div>
    </div>

</x-app-layout>

<x-app-layout>
    <div class="relative overflow-hidden bg-gradient-to-b from-slate-50 via-white to-amber-50/30">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-[radial-gradient(ellipse_at_top,_rgba(217,119,6,0.08),_transparent_60%)]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <section
                class="overflow-hidden rounded-2xl px-5 py-5 text-white shadow-lg sm:px-6"
                style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-200/90">
                            {{ t(['en' => 'Wishlist', 'si' => 'ප්‍රියතම ලැයිස්තුව']) }}
                        </p>
                        <h1 class="mt-0.5 text-xl font-bold tracking-tight sm:text-2xl">
                            {{ t(['en' => 'Saved Events', 'si' => 'සුරකින ලද ප්‍රසංග']) }}
                        </h1>
                        <p class="mt-1 text-sm text-violet-100/90">
                            {{ t(['en' => 'Events you bookmarked — jump back in when you\'re ready to book.', 'si' => 'ඔබ සුරකින ලද ප්‍රසංග — වෙන්කිරීමට සූදානම් වූ විට නැවත පැමිණෙන්න.']) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="rounded-xl bg-white/15 px-3.5 py-2 ring-1 ring-white/10 backdrop-blur-md">
                            <div class="text-[10px] uppercase tracking-wider text-violet-100/90">
                                {{ t(['en' => 'Saved', 'si' => 'සුරකින ලද']) }}
                            </div>
                            <div class="text-lg font-bold leading-tight">
                                {{ $events->total() }}
                            </div>
                        </div>

                        <a href="{{ route('attendee.dashboard') }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[#0F0363] shadow-sm transition hover:bg-violet-50">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            {{ t(['en' => 'Browse events', 'si' => 'ප්‍රසංග බලන්න']) }}
                        </a>
                    </div>
                </div>
            </section>

            @if ($events->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <i class="bi bi-bookmark-heart text-3xl" aria-hidden="true"></i>
                    </div>

                    <h2 class="mt-5 text-xl font-bold text-slate-900 sm:text-2xl">
                        {{ t(['en' => 'No saved events yet', 'si' => 'තවම සුරකින ලද ප්‍රසංග නැත']) }}
                    </h2>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        {{ t(['en' => 'Tap the bookmark on any event to keep it here. We\'ll also notify you when a saved event is published or ticket sales open.', 'si' => 'ප්‍රසංගයක පොත්සලකුණ තෝරා මෙහි තබා ගන්න. සුරකින ලද ප්‍රසංගයක් ප්‍රකාශයට පත් වූ විට හෝ ටිකට් විකිණීම ආරම්භ වූ විට අපි දැනුම් දෙන්නෙමු.']) }}
                    </p>

                    <a href="{{ route('attendee.dashboard') }}"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark">
                        <i class="bi bi-compass" aria-hidden="true"></i>
                        {{ t(['en' => 'Explore Events', 'si' => 'ප්‍රසංග ගවේෂණය කරන්න']) }}
                    </a>
                </div>
            @else
                @php
                    $selectedCategory = null;
                    $eventCategories = collect();
                    $browseRouteName = 'attendee.saved.index';
                    $compact = false;
                    $emptyMessage = t(['en' => 'No saved events yet.', 'si' => 'තවම සුරකින ලද ප්‍රසංග නැත.']);
                    $hideCategoryBar = true;
                @endphp

                @include('partials.events-browse', [
                    'events' => $events,
                    'eventCategories' => $eventCategories,
                    'selectedCategory' => $selectedCategory,
                    'browseRouteName' => $browseRouteName,
                    'compact' => $compact,
                    'emptyMessage' => $emptyMessage,
                    'hideCategoryBar' => $hideCategoryBar,
                ])

                @if ($events->hasPages())
                    <div class="mt-2">
                        {{ $events->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

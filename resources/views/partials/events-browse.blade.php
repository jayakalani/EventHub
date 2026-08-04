@php
    $browseRouteName = $browseRouteName ?? 'attendee.dashboard';
    $browseSection = $browseSection ?? 'active';
    $compact = $compact ?? false;
    $browseQuery = array_filter([
        'host' => request('host'),
        'search' => request('search'),
        'date' => request('date'),
    ]);
@endphp

<div class="{{ $compact ? 'rounded-2xl p-3 mb-4' : 'rounded-3xl p-6 mb-8' }} border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
    @unless ($compact)
        <h3 class="mb-3 text-lg font-semibold text-slate-900 dark:text-white">
            {{ t(['en' => 'Event Categories', 'si' => 'ප්‍රසංග වර්ග']) }}
        </h3>
    @endunless

    <div class="flex flex-wrap {{ $compact ? 'gap-1.5' : 'gap-2 rounded-2xl bg-slate-100 p-2' }}">
        <a href="{{ route($browseRouteName, $browseQuery) }}"
            class="{{ $compact ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm' }} rounded-xl font-semibold transition
            {{ ! $selectedCategory
                ? 'bg-primary text-white shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-white hover:text-primary dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
            {{ t(['en' => 'All Categories', 'si' => 'සියලු ප්‍රසංග']) }}
        </a>

        @foreach ($eventCategories as $category)
            <a href="{{ route($browseRouteName, array_filter(array_merge($browseQuery, ['category' => $category->id]))) }}"
                class="{{ $compact ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm' }} rounded-xl font-medium transition
                {{ $selectedCategory == $category->id
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-slate-100 text-slate-600 hover:bg-white hover:text-primary dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>

<div class="grid {{ $compact ? 'gap-4' : 'gap-6' }} sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse($events as $event)
        <div class="group {{ $compact ? 'rounded-2xl' : 'rounded-3xl' }} overflow-hidden border border-slate-200 bg-white shadow-sm transition hover:shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="relative {{ $compact ? 'h-36' : 'h-52' }} overflow-hidden">
                @if ($event->cover)
                    <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                        alt="{{ $event->name }}"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-400 dark:bg-slate-800">
                        {{ t(['en' => 'No Image Available', 'si' => 'රූපයක් නැත']) }}
                    </div>
                @endif

                <div
                    class="absolute bottom-3 right-3 z-10 flex items-center gap-2 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    @auth
                        @unless ($event->isCancelled() || $event->isCompleted())
                            <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_liked ? t(['en' => 'Unlike event', 'si' => 'කැමති ඉවත් කරන්න']) : t(['en' => 'Like event', 'si' => 'කැමති වන්න']) }}"
                                    title="{{ $event->is_liked ? t(['en' => 'Unlike', 'si' => 'කැමති නැත']) : t(['en' => 'Like', 'si' => 'කැමතියි']) }}"
                                    class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-white/70 shadow-sm backdrop-blur-md transition hover:bg-white/90
                                    {{ $event->is_liked ? 'text-[#1877F2]' : 'text-slate-600 hover:text-[#1877F2]' }}">
                                    <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>

                            <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_saved ? t(['en' => 'Unsave event', 'si' => 'සුරැකීම ඉවත් කරන්න']) : t(['en' => 'Save event', 'si' => 'ප්‍රසංග සුරකින්න']) }}"
                                    title="{{ $event->is_saved ? t(['en' => 'Unsave', 'si' => 'සුරැකීම ඉවත්']) : t(['en' => 'Save', 'si' => 'සුරකින්න']) }}"
                                    class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-white/70 shadow-sm backdrop-blur-md transition hover:bg-white/90
                                    {{ $event->is_saved ? 'text-amber-600' : 'text-slate-600 hover:text-amber-600' }}">
                                    <i class="bi {{ $event->is_saved ? 'bi-bookmark-fill' : 'bi-bookmark' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>
                        @endunless
                    @else
                        <button type="button"
                            @click="promptLogin()"
                            aria-label="{{ t(['en' => 'Like event', 'si' => 'කැමති වන්න']) }}"
                            class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-white/70 text-slate-600 shadow-sm backdrop-blur-md transition hover:bg-white/90 hover:text-[#1877F2]">
                            <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                        </button>

                        <button type="button"
                            @click="promptLogin()"
                            aria-label="{{ t(['en' => 'Save event', 'si' => 'ප්‍රසංග සුරකින්න']) }}"
                            class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-white/70 text-slate-600 shadow-sm backdrop-blur-md transition hover:bg-white/90 hover:text-amber-600">
                            <i class="bi bi-bookmark" aria-hidden="true"></i>
                        </button>
                    @endauth
                </div>
            </div>

            <div class="{{ $compact ? 'p-3.5' : 'p-5' }}">
                @if ($event->isCancelled())
                    <div class="{{ $compact ? 'mb-2 px-3 py-2' : 'mb-4 px-4 py-3' }} rounded-2xl border border-rose-200 bg-rose-50">
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-700">{{ t(['en' => 'Event Cancelled', 'si' => 'ප්‍රසංගය අවලංගුයි']) }}</p>
                        @if ($event->cancellation_reason && ! $compact)
                            <p class="mt-2 text-sm leading-relaxed text-rose-800">{{ $event->cancellation_reason }}</p>
                        @endif
                    </div>
                @elseif ($event->isCompleted())
                    <div class="{{ $compact ? 'mb-2 px-3 py-2' : 'mb-4 px-4 py-3' }} rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-800">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">{{ t(['en' => 'Event Completed', 'si' => 'ප්‍රසංගය අවසන්']) }}</p>
                        @unless ($compact)
                            <p class="mt-1 text-sm text-slate-600">{{ t(['en' => 'tails, tickets, and feedback from this past event.', 'si' => 'මෙම අතීත ප්‍රසංගයේ විස්තර, ටිකට් සහ ප්‍රතිචාර බලන්න.']) }}</p>
                        @endunless
                    </div>
                @endif

                <h3 class="{{ $compact ? 'text-base' : 'text-lg' }} line-clamp-1 font-semibold text-slate-900 dark:text-white">
                    {{ $event->name }}
                </h3>

                <p class="{{ $compact ? 'mt-1 text-xs' : 'mt-2 text-sm' }} text-slate-500 dark:text-slate-400">
                    <i class="bi bi-calendar3 mr-1"></i>
                    @if ($event->hasDateYetToBeScheduled())
                        {{ t(['en' => 'Date & time not chosen yet — we\'ll inform you when scheduled', 'si' => 'දිනය සහ වේලාව තවම තෝරා නැත — නියම වූ විට දැනුම් දෙන්නෙමු']) }}
                    @else
                        {{ $event->formattedScheduleDate('Y-m-d') ?? $event->date }}
                    @endif
                </p>
                <p class="{{ $compact ? 'mt-0.5 text-xs' : 'mt-1 text-sm' }} text-slate-500 dark:text-slate-400">
                    <i class="bi bi-geo-alt mr-1"></i>{{ $event->displayPlace() }}
                </p>

                @auth
                    <a href="{{ route('attendee.events.show', $event->id) }}"
                        class="{{ $compact ? 'mt-3 py-2' : 'mt-5 py-3' }} block rounded-xl px-4 text-center text-sm font-semibold text-white transition {{ $event->isCancelled() || $event->isCompleted() ? 'bg-slate-600 hover:bg-slate-700' : 'bg-primary hover:bg-primary-dark' }}">
                        @if ($event->isCancelled())
                            {{ t(['en' => 'View Cancelled Event', 'si' => 'අවලංගු ප්‍රසංග බලන්න']) }}
                        @elseif ($event->isCompleted())
                            {{ t(['en' => 'View Past Event', 'si' => 'අතීත ප්‍රසංග බලන්න']) }}
                        @else
                            {{ t(['en' => 'View Details', 'si' => 'විස්තර බලන්න']) }}
                        @endif
                    </a>
                @else
                    <button type="button"
                        @click="promptLogin()"
                        class="{{ $compact ? 'mt-3 py-2' : 'mt-5 py-3' }} block w-full rounded-xl bg-primary px-4 text-center text-sm font-semibold text-white transition hover:bg-primary-dark">
                        {{ t(['en' => 'View Details', 'si' => 'විස්තර බලන්න']) }}
                    </button>
                @endauth
            </div>
        </div>
    @empty
        <div class="col-span-full {{ $compact ? 'rounded-2xl p-6' : 'rounded-3xl p-10' }} border bg-white text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
            {{ ($browseSection ?? 'active') === 'past'
                ? t(['en' => 'No past events yet.', 'si' => 'තවම අතීත ප්‍රසංග නැත.'])
                : t(['en' => 'No active events available.', 'si' => 'සක්‍රීය ප්‍රසංග නැත.']) }}
        </div>
    @endforelse
</div>

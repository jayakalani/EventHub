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
            Event Categories
        </h3>
    @endunless

    <div class="flex flex-wrap {{ $compact ? 'gap-1.5' : 'gap-2 rounded-2xl bg-slate-100 p-2' }}">
        <a href="{{ route($browseRouteName, $browseQuery) }}"
            class="{{ $compact ? 'px-3.5 py-1.5 text-xs' : 'px-5 py-2.5 text-sm' }} rounded-xl font-semibold transition
            {{ ! $selectedCategory
                ? 'bg-primary text-white shadow-sm'
                : 'bg-slate-100 text-slate-600 hover:bg-white hover:text-primary dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
            All Categories
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
            @if ($event->cover)
                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                    alt="{{ $event->name }}"
                    class="{{ $compact ? 'h-36' : 'h-52' }} w-full object-cover transition duration-300 group-hover:scale-105">
            @else
                <div class="{{ $compact ? 'h-36' : 'h-52' }} flex items-center justify-center bg-slate-100 text-slate-400 dark:bg-slate-800">
                    No Image Available
                </div>
            @endif

            <div class="{{ $compact ? 'p-3.5' : 'p-5' }}">
                @if ($event->isCancelled())
                    <div class="{{ $compact ? 'mb-2 px-3 py-2' : 'mb-4 px-4 py-3' }} rounded-2xl border border-rose-200 bg-rose-50">
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Event Cancelled</p>
                        @if ($event->cancellation_reason && ! $compact)
                            <p class="mt-2 text-sm leading-relaxed text-rose-800">{{ $event->cancellation_reason }}</p>
                        @endif
                    </div>
                @elseif ($event->isCompleted())
                    <div class="{{ $compact ? 'mb-2 px-3 py-2' : 'mb-4 px-4 py-3' }} rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-800">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">Event Completed</p>
                        @unless ($compact)
                            <p class="mt-1 text-sm text-slate-600">View details, tickets, and feedback from this past event.</p>
                        @endunless
                    </div>
                @endif

                <h3 class="{{ $compact ? 'text-base' : 'text-lg' }} line-clamp-1 font-semibold text-slate-900 dark:text-white">
                    {{ $event->name }}
                </h3>

                <p class="{{ $compact ? 'mt-1 text-xs' : 'mt-2 text-sm' }} text-slate-500 dark:text-slate-400">
                    <i class="bi bi-calendar3 mr-1"></i>{{ $event->date }}
                </p>
                <p class="{{ $compact ? 'mt-0.5 text-xs' : 'mt-1 text-sm' }} text-slate-500 dark:text-slate-400">
                    <i class="bi bi-geo-alt mr-1"></i>{{ $event->place }}
                </p>

                <div class="{{ $compact ? 'mt-2.5' : 'mt-4' }} flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        @auth
                            @unless ($event->isCancelled() || $event->isCompleted())
                            <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_liked ? __('Unlike event') : __('Like event') }}"
                                    title="{{ $event->is_liked ? __('Unlike') : __('Like') }}"
                                    class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} transition
                                    {{ $event->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700' }}">
                                    <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>

                            <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_saved ? __('Unsave event') : __('Save event') }}"
                                    title="{{ $event->is_saved ? __('Unsave') : __('Save') }}"
                                    class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} transition
                                    {{ $event->is_saved ? 'bg-amber-100 text-amber-600 hover:bg-amber-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700' }}">
                                    <i class="bi {{ $event->is_saved ? 'bi-bookmark-fill' : 'bi-bookmark' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>
                            @endunless
                        @else
                            <button type="button"
                                @click="promptLogin()"
                                aria-label="{{ __('Like event') }}"
                                class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-slate-100 text-slate-500 transition hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                            </button>

                            <button type="button"
                                @click="promptLogin()"
                                aria-label="{{ __('Save event') }}"
                                class="inline-flex items-center justify-center rounded-full {{ $compact ? 'p-2 text-base' : 'p-2.5 text-xl' }} bg-slate-100 text-slate-500 transition hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                                <i class="bi bi-bookmark" aria-hidden="true"></i>
                            </button>
                        @endauth
                    </div>
                </div>

                @auth
                    <a href="{{ route('attendee.events.show', $event->id) }}"
                        class="{{ $compact ? 'mt-3 py-2' : 'mt-5 py-3' }} block rounded-xl px-4 text-center text-sm font-semibold text-white transition {{ $event->isCancelled() || $event->isCompleted() ? 'bg-slate-600 hover:bg-slate-700' : 'bg-primary hover:bg-primary-dark' }}">
                        @if ($event->isCancelled())
                            View Cancelled Event
                        @elseif ($event->isCompleted())
                            View Past Event
                        @else
                            View Details
                        @endif
                    </a>
                @else
                    <button type="button"
                        @click="promptLogin()"
                        class="{{ $compact ? 'mt-3 py-2' : 'mt-5 py-3' }} block w-full rounded-xl bg-primary px-4 text-center text-sm font-semibold text-white transition hover:bg-primary-dark">
                        View Details
                    </button>
                @endauth
            </div>
        </div>
    @empty
        <div class="col-span-full {{ $compact ? 'rounded-2xl p-6' : 'rounded-3xl p-10' }} border bg-white text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
            {{ ($browseSection ?? 'active') === 'past' ? 'No past events yet.' : 'No active events available.' }}
        </div>
    @endforelse
</div>

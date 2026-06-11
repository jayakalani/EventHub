@php
    $browseRouteName = $browseRouteName ?? 'attendee.dashboard';
    $browseSection = $browseSection ?? 'active';
    $browseQuery = array_filter([
        'host' => request('host'),
        'search' => request('search'),
        'date' => request('date'),
    ]);
@endphp

<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm mb-8">
    <h3 class="text-lg font-semibold text-slate-900 mb-3">
        Event Categories
    </h3>

    <div class="flex flex-wrap gap-2 bg-slate-100 p-2 rounded-2xl">
        <a href="{{ route($browseRouteName, $browseQuery) }}"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold transition
            {{ ! $selectedCategory
                ? 'bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 text-white shadow'
                : 'text-slate-600 hover:bg-white hover:text-indigo-600' }}">
            All Categories
        </a>

        @foreach ($eventCategories as $category)
            <a href="{{ route($browseRouteName, array_filter(array_merge($browseQuery, ['category' => $category->id]))) }}"
                class="px-5 py-2.5 rounded-xl text-sm font-medium transition
                {{ $selectedCategory == $category->id
                    ? 'bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 text-white shadow scale-105'
                    : 'text-slate-600 hover:bg-white hover:text-indigo-600 hover:shadow-sm' }}">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse($events as $event)
        <div class="group rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-xl transition overflow-hidden">
            @if ($event->cover)
                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                    alt="{{ $event->name }}"
                    class="h-52 w-full object-cover group-hover:scale-105 transition duration-300">
            @else
                <div class="h-52 bg-slate-100 flex items-center justify-center text-slate-400">
                    No Image Available
                </div>
            @endif

            <div class="p-5">
                @if ($event->isCancelled())
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Event Cancelled</p>
                        @if ($event->cancellation_reason)
                            <p class="mt-2 text-sm leading-relaxed text-rose-800">{{ $event->cancellation_reason }}</p>
                        @endif
                    </div>
                @elseif ($event->isCompleted())
                    <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Event Completed</p>
                        <p class="mt-1 text-sm text-slate-600">View details, tickets, and feedback from this past event.</p>
                    </div>
                @endif

                <h3 class="font-semibold text-lg text-slate-900 line-clamp-1">
                    {{ $event->name }}
                </h3>

                <p class="mt-2 text-sm text-slate-500">📅 {{ $event->date }}</p>
                <p class="mt-1 text-sm text-slate-500">📍 {{ $event->place }}</p>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        @auth
                            @unless ($event->isCancelled() || $event->isCompleted())
                            <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_liked ? __('Unlike event') : __('Like event') }}"
                                    title="{{ $event->is_liked ? __('Unlike') : __('Like') }}"
                                    class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                    {{ $event->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                    <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>

                            <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    aria-label="{{ $event->is_saved ? __('Unsave event') : __('Save event') }}"
                                    title="{{ $event->is_saved ? __('Unsave') : __('Save') }}"
                                    class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                    {{ $event->is_saved ? 'bg-amber-100 text-amber-600 hover:bg-amber-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                    <i class="bi {{ $event->is_saved ? 'bi-bookmark-fill' : 'bi-bookmark' }}"
                                        aria-hidden="true"></i>
                                </button>
                            </form>
                            @endunless
                        @else
                            <button type="button"
                                @click="promptLogin()"
                                aria-label="{{ __('Like event') }}"
                                class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition bg-slate-100 text-slate-500 hover:bg-slate-200">
                                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                            </button>

                            <button type="button"
                                @click="promptLogin()"
                                aria-label="{{ __('Save event') }}"
                                class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition bg-slate-100 text-slate-500 hover:bg-slate-200">
                                <i class="bi bi-bookmark" aria-hidden="true"></i>
                            </button>
                        @endauth
                    </div>
                </div>

                @auth
                    <a href="{{ route('attendee.events.show', $event->id) }}"
                        class="block mt-5 text-center rounded-xl {{ $event->isCancelled() ? 'bg-slate-600 hover:bg-slate-700' : ($event->isCompleted() ? 'bg-slate-600 hover:bg-slate-700' : 'bg-indigo-600 hover:bg-indigo-700') }} px-4 py-3 text-sm font-semibold text-white transition">
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
                        class="block mt-5 w-full text-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        View Details
                    </button>
                @endauth
            </div>
        </div>
    @empty
        <div class="col-span-full text-center p-10 rounded-3xl border bg-white text-slate-500">
            {{ ($browseSection ?? 'active') === 'past' ? 'No past events yet.' : 'No active events available.' }}
        </div>
    @endforelse
</div>

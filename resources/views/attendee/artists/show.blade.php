<x-app-layout>

    <x-slot name="header">
        <div class="flex min-w-0 flex-wrap items-center gap-x-2.5 gap-y-0.5 sm:gap-x-3">
            <a href="{{ route('attendee.artists.index') }}"
                class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800 sm:text-sm">
                &larr; {{ t(['en' => 'Back to Artists', 'si' => 'කලාකරුවන් වෙත ආපසු']) }}
            </a>
            <span class="hidden h-3.5 w-px shrink-0 bg-slate-200 sm:block" aria-hidden="true"></span>
            <p class="min-w-0 truncate text-xs text-slate-500 sm:text-sm">
                <span class="text-slate-400">{{ t(['en' => 'Artist details and events', 'si' => 'කලාකරු විස්තර සහ ප්‍රසංග']) }}</span>
                <span class="mx-1.5 text-slate-300" aria-hidden="true">·</span>
                <span class="font-medium text-slate-700">{{ $artist->name }}</span>
            </p>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Artist profile header --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div
                    class="relative px-5 py-6 sm:px-6 sm:py-7"
                    style="background: linear-gradient(115deg, #02031F 0%, #030638 30%, #0F0363 65%, #2A1585 100%);"
                >
                    <div class="pointer-events-none absolute inset-0 opacity-30"
                        style="background: radial-gradient(ellipse at 20% 40%, rgba(96,165,250,0.35), transparent 55%);"></div>

                    <div class="relative z-10 flex flex-col items-center gap-5 sm:flex-row sm:items-center sm:gap-6">
                        <div class="relative shrink-0">
                            @if ($artist->cover)
                                <div class="absolute -inset-3 overflow-hidden rounded-full opacity-60">
                                    <img
                                        src="{{ asset('uploads/covers/artists/' . $artist->cover) }}"
                                        alt=""
                                        aria-hidden="true"
                                        class="h-full w-full scale-150 object-cover blur-xl"
                                    >
                                </div>
                                <img
                                    src="{{ asset('uploads/covers/artists/' . $artist->cover) }}"
                                    alt="{{ $artist->name }}"
                                    class="relative z-10 h-28 w-28 rounded-full border-4 border-blue-500 object-cover object-top shadow-lg transition duration-300 hover:scale-105 hover:shadow-xl sm:h-32 sm:w-32"
                                >
                            @else
                                <div class="relative z-10 flex h-28 w-28 items-center justify-center rounded-full border-4 border-blue-500 bg-white/10 text-xs font-medium text-violet-100 shadow-lg transition duration-300 hover:scale-105 hover:shadow-xl sm:h-32 sm:w-32">
                                    {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 w-full">
                            <div class="rounded-2xl border border-white/20 bg-white/15 px-4 py-4 shadow-sm backdrop-blur-md sm:px-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0 text-center sm:text-left">
                                        <h3 class="truncate text-xl font-bold tracking-tight text-white sm:text-2xl">
                                            {{ $artist->name }}
                                        </h3>
                                        <div class="mt-2.5 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                                                <span class="h-1.5 w-1.5 rounded-full bg-white/90"></span>
                                                {{ t(['en' => 'Active', 'si' => 'සක්‍රීය']) }}
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-violet-100 ring-1 ring-white/20">
                                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                                {{ $events->count() }} {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-center gap-3 sm:justify-end">
                                        <div class="rounded-xl bg-white/15 px-3.5 py-2 ring-1 ring-white/20 backdrop-blur-sm">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-200">
                                                {{ t(['en' => 'Followers', 'si' => 'අනුගාමිකයන්']) }}
                                            </p>
                                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-lg font-bold text-white">
                                                <i class="bi bi-person-check text-indigo-200" aria-hidden="true"></i>
                                                {{ $artist->artist_follows_count ?? 0 }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white/15 px-3.5 py-2 ring-1 ring-white/20 backdrop-blur-sm">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-200">
                                                {{ t(['en' => 'Likes', 'si' => 'කැමති']) }}
                                            </p>
                                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-lg font-bold text-white">
                                                <i class="bi bi-hand-thumbs-up text-[#60A5FA]" aria-hidden="true"></i>
                                                {{ $artist->artist_likes_count ?? 0 }}
                                            </p>
                                        </div>

                                        <form action="{{ route('attendee.artists.follow', $artist) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                aria-label="{{ $artist->is_followed ? t(['en' => 'Unfollow artist', 'si' => 'අනුගමනය නවත්වන්න']) : t(['en' => 'Follow artist', 'si' => 'අනුගමනය කරන්න']) }}"
                                                class="inline-flex h-12 items-center justify-center gap-1.5 rounded-full px-4 text-sm font-semibold shadow-md transition
                                                {{ $artist->is_followed
                                                    ? 'bg-indigo-500 text-white hover:bg-indigo-600'
                                                    : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                                                <i class="bi {{ $artist->is_followed ? 'bi-person-check-fill' : 'bi-person-plus' }}"
                                                    aria-hidden="true"></i>
                                                {{ $artist->is_followed
                                                    ? t(['en' => 'Following', 'si' => 'අනුගමනය'])
                                                    : t(['en' => 'Follow', 'si' => 'අනුගමනය']) }}
                                            </button>
                                        </form>

                                        <form action="{{ route('attendee.artists.like', $artist) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                aria-label="{{ $artist->is_liked ? t(['en' => 'Unlike artist', 'si' => 'කැමති නැත']) : t(['en' => 'Like artist', 'si' => 'කැමතියි']) }}"
                                                class="inline-flex h-12 w-12 items-center justify-center rounded-full text-xl shadow-md transition
                                                {{ $artist->is_liked
                                                    ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]'
                                                    : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                                                <i class="bi {{ $artist->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                    aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ t(['en' => 'Events by this artist', 'si' => 'මෙම කලාකරුගේ ප්‍රසංග']) }}
                    </h3>
                    <a href="{{ route('attendee.dashboard', ['artist' => $artist->id]) }}"
                        class="text-xs font-semibold text-[#0F0363] hover:opacity-80 sm:text-sm">
                        {{ t(['en' => 'Browse in Events', 'si' => 'ප්‍රසංග තුළ බලන්න']) }}
                    </a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                    @forelse($events as $event)
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-lg">
                            @if ($event->cover)
                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    class="h-32 w-full object-cover">
                            @else
                                <div class="flex h-32 items-center justify-center bg-slate-100 text-sm text-slate-400">
                                    {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                </div>
                            @endif

                            <div class="p-3.5">
                                <h4 class="line-clamp-1 text-base font-semibold text-slate-900">{{ $event->name }}</h4>
                                <p class="mt-1 text-xs text-slate-500">
                                    📅
                                    @if ($event->hasDateYetToBeScheduled())
                                        {{ t(['en' => 'Date & time not chosen yet', 'si' => 'දිනය සහ වේලාව තවම තෝරා නැත']) }}
                                    @else
                                        {{ $event->formattedScheduleDate('Y-m-d') ?? $event->date }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">📍 {{ $event->place }}</p>

                                <div class="mt-2.5 flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                        <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                        {{ $event->likes_count ?? 0 }}
                                    </span>

                                    <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            aria-label="{{ $event->is_liked ? t(['en' => 'Unlike event', 'si' => 'කැමති නැත']) : t(['en' => 'Like event', 'si' => 'කැමතියි']) }}"
                                            class="inline-flex items-center justify-center rounded-full p-2 text-lg transition
                                            {{ $event->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>

                                <a href="{{ route('attendee.events.show', $event) }}"
                                    class="mt-3 block rounded-xl bg-primary px-3 py-2 text-center text-xs font-semibold text-white transition hover:bg-primary-dark sm:text-sm">
                                    {{ t(['en' => 'View Event', 'si' => 'ප්‍රසංග බලන්න']) }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                            {{ t(['en' => 'This artist has no events yet.', 'si' => 'මෙම කලාකරුවාට තවම ප්‍රසංග නැත.']) }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

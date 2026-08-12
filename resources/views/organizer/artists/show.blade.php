<x-app-layout>

    <x-slot name="header">
        <div class="flex min-w-0 flex-wrap items-center gap-x-2.5 gap-y-0.5 sm:gap-x-3">
            <a href="{{ route('organizer.artists') }}"
                class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800 sm:text-sm">
                &larr; Back to Artists
            </a>
            <span class="hidden h-3.5 w-px shrink-0 bg-slate-200 sm:block" aria-hidden="true"></span>
            <p class="min-w-0 truncate text-xs text-slate-500 sm:text-sm">
                <span class="text-slate-400">Artist details and events</span>
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
                                    No Image
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
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white shadow-sm
                                                {{ $artist->is_active ? 'bg-emerald-500' : 'bg-slate-500' }}">
                                                <span class="h-1.5 w-1.5 rounded-full bg-white/90"></span>
                                                {{ $artist->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-violet-100 ring-1 ring-white/20">
                                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                                {{ $events->count() }} {{ $events->count() === 1 ? 'Event' : 'Events' }}
                                            </span>
                                        </div>
                                        <div class="mt-3 space-y-1 text-sm text-violet-100">
                                            <p class="inline-flex items-center justify-center gap-1.5 sm:justify-start">
                                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                                <span class="truncate">{{ $artist->email }}</span>
                                            </p>
                                            <p class="inline-flex items-center justify-center gap-1.5 sm:justify-start">
                                                <i class="bi bi-telephone" aria-hidden="true"></i>
                                                {{ $artist->contact_number }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-center gap-3 sm:justify-end">
                                        <div class="rounded-xl bg-white/15 px-3.5 py-2 ring-1 ring-white/20 backdrop-blur-sm">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-200">
                                                Followers
                                            </p>
                                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-lg font-bold text-white">
                                                <i class="bi bi-person-check text-indigo-200" aria-hidden="true"></i>
                                                {{ $artist->artist_follows_count ?? 0 }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white/15 px-3.5 py-2 ring-1 ring-white/20 backdrop-blur-sm">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-200">
                                                Likes
                                            </p>
                                            <p class="mt-0.5 inline-flex items-center gap-1.5 text-lg font-bold text-white">
                                                <i class="bi bi-hand-thumbs-up text-[#60A5FA]" aria-hidden="true"></i>
                                                {{ $artist->artist_likes_count ?? 0 }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Events by this artist
                    </h3>
                    <a href="{{ route('organizer.events.index', ['search' => $artist->name]) }}"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        Browse in Events
                    </a>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($events as $event)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-xl transition overflow-hidden">
                            @if ($event->cover)
                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    alt="{{ $event->name }}" class="h-40 w-full object-cover">
                            @else
                                <div class="h-40 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                                    No Image
                                </div>
                            @endif

                            <div class="p-4">
                                <h4 class="font-semibold text-base text-gray-900 line-clamp-1">{{ $event->name }}</h4>
                                <p class="mt-2 text-xs text-gray-600">📅 {{ $event->date }}</p>
                                <p class="mt-1 text-xs text-gray-600">📍 {{ $event->place }}</p>

                                @if ($event->eventCategory)
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $event->eventCategory->name }}
                                    </p>
                                @endif

                                <span
                                    class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold
                                    {{ $event->status === 'upcoming' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $event->status === 'ongoing' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $event->status === 'completed' ? 'bg-gray-100 text-gray-700' : '' }}
                                    {{ $event->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $event->status === 'unpublished' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $event->status === 'postponed' ? 'bg-orange-100 text-orange-800' : '' }}">
                                    {{ ucfirst($event->status) }}
                                </span>

                                <a href="{{ route('organizer.events.show', $event) }}"
                                    class="mt-4 block text-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                    View Event
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                            This artist has no events yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

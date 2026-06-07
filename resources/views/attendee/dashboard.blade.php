<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="mt-1 text-sm text-gray-500">
                    Discover upcoming events and book your next experience.
                </p>
            </div>

            <!-- Settings Dropdown -->
            <div x-data="{ open: false }" class="relative inline-block text-left mb-8">

                <button @click="open = !open"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">
                    Settings
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false"
                    class="absolute right-0 mt-2 w-80 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 p-6 z-50">

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm mb-4">
                        <p class="text-sm font-medium text-slate-500">Available Events</p>
                        <h3 class="mt-2 text-2xl font-semibold text-blue-600">{{ $events->count() }}</h3>
                        <p class="text-sm text-slate-500">Currently active events</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">My Bookings</p>
                        <h3 class="mt-2 text-2xl font-semibold text-green-600">{{ $myBookings ?? 0 }}</h3>
                        <p class="text-sm text-slate-500">Tickets booked</p>
                    </div>

                </div>
            </div>

            <div class="text-sm text-gray-600">
                Welcome,
                <span class="font-semibold">{{ Auth::user()->first_name }}</span>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- HERO -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 p-8 mb-8 text-white shadow">

                <div class="max-w-2xl">

                    <h1 class="text-3xl font-bold">
                        Discover Amazing Events
                    </h1>

                    <p class="mt-3 text-indigo-100">
                        Explore concerts, workshops, conferences, sports events and more.
                    </p>

                    <form action="{{ route('attendee.dashboard') }}" method="GET" class="mt-6">

                        <div class="grid gap-3 md:grid-cols-4">

                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Event Name" class="rounded-2xl border-0 px-4 py-3 text-slate-800 shadow">

                            <input type="date" name="date" value="{{ request('date') }}"
                                class="rounded-2xl border-0 px-4 py-3 text-slate-800 shadow">

                            <button type="submit"
                                class="rounded-2xl bg-white px-6 py-3 font-semibold text-indigo-600 shadow hover:bg-slate-100 transition">
                                Search
                            </button>

                        </div>

                    </form>

                </div>
            </div>

            <!-- CATEGORIES -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm mb-8">

                <h3 class="text-lg font-semibold text-slate-900 mb-3">
                    Event Categories
                </h3>

                <div class="flex flex-wrap gap-2 bg-slate-100 p-2 rounded-2xl">

                    <!-- ALL -->
                    <a href="{{ route('attendee.dashboard') }}"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold transition
                       {{ !$selectedCategory
                           ? 'bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 text-white shadow'
                           : 'text-slate-600 hover:bg-white hover:text-indigo-600' }}">
                        All Categories
                    </a>

                    <!-- CATEGORY LIST -->
                    @foreach ($eventCategories as $category)
                        <a href="{{ route('attendee.dashboard', ['category' => $category->id]) }}"
                            class="px-5 py-2.5 rounded-xl text-sm font-medium transition
                           {{ $selectedCategory == $category->id
                               ? 'bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 text-white shadow scale-105'
                               : 'text-slate-600 hover:bg-white hover:text-indigo-600 hover:shadow-sm' }}">
                            {{ $category->name }}
                        </a>
                    @endforeach

                </div>
            </div>

            <!-- EVENTS -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                @forelse($events as $event)
                    <div
                        class="group rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-xl transition overflow-hidden">

                        @if ($event->cover)
                            <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                class="h-52 w-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                            <div class="h-52 bg-slate-100 flex items-center justify-center text-slate-400">
                                No Image Available
                            </div>
                        @endif

                        <div class="p-5">

                            <h3 class="font-semibold text-lg text-slate-900 line-clamp-1">
                                {{ $event->name }}
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">📅 {{ $event->date }}</p>
                            <p class="mt-1 text-sm text-slate-500">📍 {{ $event->place }}</p>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                

                                <div class="flex items-center gap-2">
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
                                </div>
                            </div>

                            <a href="{{ route('attendee.events.show', $event->id) }}"
                                class="block mt-5 text-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                View Details
                            </a>

                        </div>

                    </div>

                @empty

                    <div class="col-span-full text-center p-10 rounded-3xl border bg-white text-slate-500">
                        No active events available.
                    </div>
                @endforelse

            </div>

        </div>
    </div>

</x-app-layout>

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

            @if (! empty($selectedHost))
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4">
                    <p class="text-sm text-indigo-900">
                        {{ __('Showing events hosted by') }}
                        <span class="font-semibold">{{ $selectedHost->name }}</span>
                    </p>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                        {{ __('Clear host filter') }}
                    </a>
                </div>
            @endif

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

                            @if (request('host'))
                                <input type="hidden" name="host" value="{{ request('host') }}">
                            @endif

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

            @include('partials.events-browse')

            @if ($pastEvents->isNotEmpty())
                <div class="mt-12">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-900">Past Events</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Completed events you can still revisit for details, tickets, and feedback.
                        </p>
                    </div>

                    @include('partials.events-browse', [
                        'events' => $pastEvents,
                        'browseSection' => 'past',
                    ])
                </div>
            @endif

        </div>
    </div>

</x-app-layout>

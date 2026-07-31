@php
    $totalCategorytickets = $event->total_tickets;
    $totalAvailable = $ticketCategories->sum('no_of_available_tickets');
    $totalBooked = max(0, $totalCategorytickets - $totalAvailable);

    $statusStyles = [
        'unpublished' => 'bg-amber-400/20 text-white ring-amber-100/40',
        'upcoming' => 'bg-indigo-400/20 text-white ring-indigo-100/40',
        'ongoing' => 'bg-emerald-400/20 text-white ring-emerald-100/40',
        'completed' => 'bg-white/15 text-white ring-white/25',
        'cancelled' => 'bg-rose-400/20 text-white ring-rose-100/40',
    ];
    $statusClass = $statusStyles[$event->status ?? 'upcoming'] ?? $statusStyles['upcoming'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('Event Management') }}
                </p>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">
                    {{ __('Event Details') }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('organizer.events.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ __('Back to Events') }}
                </a>

                <a href="{{ route('organizer.ticket-categories.create', $event->id) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add ticket Category') }}
                </a>

                <a href="{{ route('organizer.events.exportPdf', $event->id) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $organizerEventShowConfig = [
            'cancelModal' => [
                'open' => $errors->has('cancellation_reason'),
                'eventId' => $event->id,
                'action' => route('organizer.events.cancel', $event->id),
                'name' => $event->name,
                'date' => $event->date,
                'time' => $event->time,
                'place' => $event->place,
            ],
            'eventsBaseUrl' => url('organizer/events'),
        ];
    @endphp

    <script type="application/json" id="organizer-event-show-config">@json($organizerEventShowConfig)</script>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-6" x-data="JSON.parse(document.getElementById('organizer-event-show-config').textContent)">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($errors->any())
                <div class="overflow-hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-red-800">{{ __('Something went wrong') }}</h3>
                            <ul class="mt-1 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="overflow-hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-red-800">{{ __('Error') }}</h3>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                    class="overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-emerald-800">{{ __('Success') }}</h3>
                                <p class="text-sm text-emerald-700">{{ session('success') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-emerald-600 hover:text-emerald-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Hero Card --}}
            <div
                class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-lg shadow-indigo-100/40 backdrop-blur">
                @if ($event->cover)
                    <div class="relative h-40 sm:h-52">
                        <img src="{{ asset('uploads/covers/events/' . $event->cover) }}" alt="{{ $event->name }}"
                            class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-indigo-950/80 via-indigo-900/30 to-transparent">
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-5">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-indigo-200">
                                        {{ __('Event') }} #{{ $event->id }}
                                    </p>
                                    <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                                        {{ $event->name }}
                                    </h1>
                                    <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-indigo-100">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $event->place }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ $event->date }} &middot; {{ $event->time }}
                                        </span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                        {{ ucfirst($event->status ?? 'upcoming') }}
                                    </span>
                                    @if ($event->eventCategory)
                                        <span
                                            class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                            {{ $event->eventCategory->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-5 py-5 text-white sm:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-lg font-bold uppercase shadow-inner ring-1 ring-white/25">
                                    {{ strtoupper(substr($event->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-indigo-200">
                                        {{ __('Event') }} #{{ $event->id }}
                                    </p>
                                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl">
                                        {{ $event->name }}
                                    </h1>
                                    <p class="mt-0.5 text-sm text-indigo-50">
                                        {{ $event->place }} &middot; {{ $event->date }} {{ $event->time }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                    {{ ucfirst($event->status ?? 'upcoming') }}
                                </span>
                                @if ($event->eventCategory)
                                    <span
                                        class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                        {{ $event->eventCategory->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Stats Row --}}
                <div class="grid grid-cols-2 gap-px bg-gray-100 sm:grid-cols-8">
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Total tickets') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">{{ number_format($event->total_tickets) }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Categories') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-indigo-600">{{ $ticketCategories->count() }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Available') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-emerald-600">{{ $totalAvailable }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Booked') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-amber-600">{{ $totalBooked }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Likes') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-rose-600">{{ $event->likes_count ?? 0 }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Saves') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-amber-600">{{ $event->saves_count ?? 0 }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Comments') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-indigo-600">{{ $event->comments_count ?? 0 }}</p>
                    </div>
                    <div class="bg-white px-4 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Rating') }}</p>
                        <p class="mt-0.5 text-xl font-bold text-yellow-600">
                            @if (($event->ratings_count ?? 0) > 0)
                                {{ number_format($event->ratings_avg_score, 1) }}
                            @else
                                —
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">{{ $event->ratings_count ?? 0 }} {{ __('reviews') }}</p>
                    </div>
                </div>
            </div>

            @if ($event->isCompleted() && $postEventAnalytics)
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-md shadow-emerald-100/30">
                    <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-4 sm:px-6">
                        <h3 class="text-base font-semibold text-white">{{ __('Post-Event Report') }}</h3>
                        <p class="mt-0.5 text-sm text-emerald-50">
                            {{ __('Analytics unlocked now that this event is completed.') }}
                        </p>
                    </div>

                    <div class="grid gap-px bg-emerald-100 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="bg-white px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Revenue') }}</p>
                            <p class="mt-0.5 text-xl font-bold text-emerald-600">
                                LKR {{ number_format($postEventAnalytics['revenue'], 0) }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('Total ticket sales collected') }}</p>
                        </div>
                        <div class="bg-white px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Likes') }}</p>
                            <p class="mt-0.5 text-xl font-bold text-rose-600">{{ number_format($postEventAnalytics['likes']) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('Attendee likes received') }}</p>
                        </div>
                        <div class="bg-white px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Comments') }}</p>
                            <p class="mt-0.5 text-xl font-bold text-indigo-600">{{ number_format($postEventAnalytics['comments']) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('Feedback submitted') }}</p>
                        </div>
                        <div class="bg-white px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Ratings') }}</p>
                            <p class="mt-0.5 text-xl font-bold text-yellow-600">
                                @if ($postEventAnalytics['ratings_count'] > 0)
                                    {{ number_format($postEventAnalytics['average_rating'], 1) }}/5
                                @else
                                    —
                                @endif
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $postEventAnalytics['ratings_count'] }} {{ __('reviews') }}</p>
                        </div>
                        <div class="bg-white px-4 py-3.5 sm:col-span-2 lg:col-span-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Tickets Sold') }}</p>
                            <p class="mt-0.5 text-xl font-bold text-amber-600">{{ number_format($totalBooked) }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('Across all categories') }}</p>
                        </div>
                    </div>

                    <div class="border-t border-emerald-100 px-5 py-4 sm:px-6">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Ticket Sales by Category') }}</h4>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Category') }}</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Sold') }}</th>
                                        <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($postEventAnalytics['ticket_sales'] as $sale)
                                        <tr>
                                            <td class="px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $sale['name'] }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-700">{{ number_format($sale['sold']) }}</td>
                                            <td class="px-3 py-2.5 text-sm font-semibold text-emerald-700">LKR {{ number_format($sale['revenue'], 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">{{ __('No ticket sales recorded.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid gap-4 lg:grid-cols-[1fr_300px]">
                {{-- Main Content --}}
                <div class="space-y-4">
                    {{-- Event Information --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 p-5 shadow-md shadow-indigo-100/25 backdrop-blur sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Event Information') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ __('Overview of hosts, category, and contact details.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('Hosted By') }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900">
                                            {{ $event->host->name ?? __('N/A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('Category') }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900">
                                            {{ $event->eventCategory->name ?? __('N/A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('Contact Person') }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900">
                                            {{ $event->contactPerson?->name ?? __('N/A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {{ __('Capacity') }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900">
                                            {{ number_format($event->total_tickets) }} {{ __('tickets') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($event->description)
                            <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ __('Description') }}
                                </p>
                                <p class="mt-1.5 text-sm leading-6 text-gray-700">
                                    {{ $event->description }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Attendee Ratings --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 p-5 shadow-md shadow-indigo-100/25 backdrop-blur sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Attendee Ratings') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ __('Ratings submitted by attendees for this event.') }}
                            </p>
                        </div>

                        <div class="mb-4 rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-1">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <svg class="h-4 w-4 {{ ($event->ratings_avg_score ?? 0) >= $star ? 'text-yellow-400' : 'text-gray-300' }}"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                    @endfor
                                </div>
                                <div>
                                    <p class="text-base font-bold text-gray-900">
                                        @if (($event->ratings_count ?? 0) > 0)
                                            {{ number_format($event->ratings_avg_score, 1) }} {{ __('out of 5') }}
                                        @else
                                            {{ __('No ratings yet') }}
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $event->ratings_count ?? 0 }} {{ __('total ratings') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse ($event->ratings->sortByDesc('created_at') as $rating)
                                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-100 text-xs font-bold text-yellow-700">
                                            {{ strtoupper(substr($rating->user->first_name, 0, 1)) }}{{ strtoupper(substr($rating->user->last_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-900">{{ $rating->user->full_name }}</p>
                                                <span class="text-xs text-gray-500">{{ $rating->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="mt-1.5 flex items-center gap-1">
                                                @for ($star = 1; $star <= 5; $star++)
                                                    <svg class="h-3.5 w-3.5 {{ $star <= $rating->score ? 'text-yellow-400' : 'text-gray-300' }}"
                                                        fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                    </svg>
                                                @endfor
                                                <span class="ml-1 text-sm font-semibold text-gray-700">{{ $rating->score }}/5</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-6 text-center text-sm text-gray-500">
                                    {{ __('No ratings yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Attendee Comments --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 p-5 shadow-md shadow-indigo-100/25 backdrop-blur sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Attendee Comments') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ __('Feedback and discussion from attendees.') }}
                            </p>
                        </div>

                        <div class="space-y-3">
                            @forelse ($event->comments->sortByDesc('created_at') as $comment)
                                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                            {{ strtoupper(substr($comment->user->first_name, 0, 1)) }}{{ strtoupper(substr($comment->user->last_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-900">{{ $comment->user->full_name }}</p>
                                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mt-1.5 text-sm leading-5 text-gray-700 whitespace-pre-wrap">{{ $comment->body }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-200 bg-white px-5 py-6 text-center text-sm text-gray-500">
                                    {{ __('No comments yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- ticket Categories --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-md shadow-indigo-100/25 backdrop-blur">
                        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">{{ __('ticket Categories') }}</h3>
                                <p class="mt-0.5 text-sm text-gray-500">
                                    {{ __('Manage ticket categories, pricing and availability.') }}
                                </p>
                            </div>
                            <a href="{{ route('organizer.ticket-categories.create', $event->id) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ __('Add Category') }}
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/80">
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Category') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('tickets') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Available') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Price') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Status') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Booking') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($ticketCategories as $category)
                                        <tr class="transition hover:bg-indigo-50/30">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="inline-block h-3 w-3 shrink-0 rounded-full ring-2 ring-white shadow-sm"
                                                        style="background-color: {{ $category->ticket_color }}"></span>
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">{{ $category->name }}</p>
                                                        @if ($category->description)
                                                            <p class="mt-0.5 max-w-xs truncate text-xs text-gray-500">
                                                                {{ $category->description }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $category->no_of_tickets }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                    {{ $category->no_of_available_tickets }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                LKR {{ number_format($category->ticket_price, 0) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($category->is_active)
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                        {{ __('Active') }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-gray-200">
                                                        {{ __('Inactive') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                @if ($category->booking_start && $category->booking_end)
                                                    <div>{{ \Carbon\Carbon::parse($category->booking_start)->format('d M Y') }}
                                                    </div>
                                                    <div class="text-xs text-gray-400">{{ __('to') }}</div>
                                                    <div>{{ \Carbon\Carbon::parse($category->booking_end)->format('d M Y') }}
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('organizer.ticket-categories.edit', [$event, $category]) }}"
                                                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                                        {{ __('Edit') }}
                                                    </a>
                                                    @if ($category->ticket_bookings_count > 0)
                                                        <span
                                                            title="{{ __('Tickets have been sold from this category and it cannot be deleted.') }}"
                                                            class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed">
                                                            {{ __('Delete') }}
                                                        </span>
                                                    @else
                                                        <form
                                                            action="{{ route('organizer.ticket-categories.destroy', [$event->id, $category->id]) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('{{ __('Are you sure you want to delete this ticket category?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50">
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-10 text-center">
                                                <div
                                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                                    </svg>
                                                </div>
                                                <p class="mt-3 font-semibold text-gray-900">
                                                    {{ __('No ticket categories yet') }}
                                                </p>
                                                <p class="mt-1 text-sm text-gray-500">
                                                    {{ __('Create your first ticket tier to start selling tickets.') }}
                                                </p>
                                                <a href="{{ route('organizer.ticket-categories.create', $event->id) }}"
                                                    class="mt-4 inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                                    {{ __('Add First Category') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-3">
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ __('Quick Actions') }}
                        </h4>
                        <div class="mt-3 space-y-2">
                            <a href="{{ route('organizer.events.edit', $event->id) }}"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                                </svg>
                                {{ __('Edit Event') }}
                            </a>
                            <a href="{{ route('organizer.events.exportPdf', $event->id) }}"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ __('Download PDF') }}
                            </a>
                            <form action="{{ route('organizer.events.destroy', $event->id) }}" method="POST"
                                onsubmit="return confirm('{{ __('Delete this event? This action cannot be undone.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M19 7H5m5 4v6m4-6v6m-7-10l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12M10 7V4a1 1 0 011-1h2a1 1 0 011 1v3" />
                                    </svg>
                                    {{ __('Delete Event') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ __('Event Status') }}
                        </h4>
                        @if ($errors->has('status'))
                            <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                {{ $errors->first('status') }}
                            </p>
                        @endif

                        @if ($event->isCancelled())
                            <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5">
                                <p class="text-sm font-semibold text-rose-800">{{ __('Event Cancelled') }}</p>
                                @if ($event->cancellation_reason)
                                    <p class="mt-1.5 text-sm leading-relaxed text-rose-700">{{ $event->cancellation_reason }}</p>
                                @endif
                                @if ($event->cancelled_at)
                                    <p class="mt-1.5 text-xs text-rose-600">
                                        {{ __('Cancelled on') }} {{ $event->cancelled_at->format('M j, Y g:i A') }}
                                    </p>
                                @endif
                            </div>
                        @elseif ($event->isCompleted())
                            <div class="mt-3 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5">
                                <p class="text-sm font-semibold text-slate-800">{{ __('Event Completed') }}</p>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                    {{ __('This event has ended. Status, cancellation, and publication settings are locked. Post-event analytics are available above.') }}
                                </p>
                            </div>
                        @else
                            <form action="{{ route('organizer.events.updateStatus', $event->id) }}" method="POST"
                                class="mt-3">
                                @csrf
                                @method('PATCH')
                                <select name="status"
                                    data-event-id="{{ $event->id }}"
                                    data-event-name="{{ $event->name }}"
                                    data-event-date="{{ $event->date }}"
                                    data-event-time="{{ $event->time }}"
                                    data-event-place="{{ $event->place }}"
                                    data-current-status="{{ $event->status }}"
                                    onchange="window.organizerHandleEventStatusChange(this)"
                                    class="block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="unpublished" {{ ($event->status ?? '') == 'unpublished' ? 'selected' : '' }}
                                        @if ($event->ticket_bookings_count > 0) disabled title="{{ __('Cannot unpublish: tickets have been sold') }}" @endif>
                                        {{ __('Unpublished') }}
                                    </option>
                                    <option value="upcoming" {{ ($event->status ?? '') == 'upcoming' ? 'selected' : '' }}>
                                        {{ __('Upcoming') }}
                                    </option>
                                    <option value="ongoing" {{ ($event->status ?? '') == 'ongoing' ? 'selected' : '' }}>
                                        {{ __('Ongoing') }}
                                    </option>
                                    <option value="cancelled">{{ __('Cancel Event') }}</option>
                                </select>
                                <p class="mt-2 text-xs text-gray-500">
                                    @if ($event->ticket_bookings_count > 0)
                                        {{ __('This event has sold tickets and cannot be unpublished.') }}
                                    @else
                                        {{ __('Unpublished events are hidden from attendees. Set to Upcoming to publish.') }}
                                    @endif
                                </p>
                            </form>
                        @endif
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ __('Event Summary') }}
                        </h4>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Event ID') }}</dt>
                                <dd class="font-semibold text-gray-900">#{{ $event->id }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Date') }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $event->date }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Time') }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $event->time }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-gray-500">{{ __('Created') }}</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ $event->created_at?->format('M d, Y') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <div class="flex gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-indigo-950">{{ __('ticket category tip') }}</h4>
                                <p class="mt-0.5 text-sm leading-5 text-indigo-800">
                                    {{ __('Each category can have its own price, color, and booking window for flexible ticketing.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        @include('organizer.events.partials.cancel-event-modal')
    </div>

    @push('scripts')
        @include('organizer.events.partials.status-change-script')
    @endpush
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="min-w-0 flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">
                <h2 class="shrink-0 text-lg font-bold leading-tight text-slate-900 sm:text-xl">
                    Event Calendar
                </h2>
                <p class="text-xs text-slate-500 sm:text-sm">
                    All of your events — click any event to manage details.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('organizer.events.create') }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:text-sm">
                    <i class="bi bi-plus-lg"></i>
                    New Event
                </a>
                <a href="{{ route('organizer.events.index') }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:text-sm">
                    <i class="bi bi-list-ul"></i>
                    All Events
                </a>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
        <style>
            #organizer-calendar .fc-event { cursor: pointer; border-radius: 8px; font-size: 0.8rem; }
            #organizer-calendar .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; color: #0f172a; }
            #organizer-calendar .fc-button { border-radius: 10px !important; font-weight: 600; }
        </style>
    @endpush

    <div class="py-5">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            {{-- Legend --}}
            <div class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    <p class="text-xs font-semibold text-slate-700">Event status</p>
                    @foreach([
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'postponed' => 'Postponed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'unpublished' => 'Unpublished',
                    ] as $key => $label)
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColors[$key] }}"></span>
                            <span class="text-xs text-slate-600">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                {{-- Calendar --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 xl:col-span-2">
                    <div id="organizer-calendar"></div>
                </div>

                {{-- Sidebar lists --}}
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">Upcoming</h3>
                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                {{ $upcomingEvents->count() }}
                            </span>
                        </div>

                        <div class="max-h-72 space-y-3 overflow-y-auto pr-1">
                            @forelse($upcomingEvents as $event)
                                <a href="{{ route('organizer.events.show', $event) }}"
                                    class="block rounded-2xl border border-slate-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="line-clamp-1 font-semibold text-slate-900">{{ $event->name }}</p>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                            style="background-color: {{ $statusColors[$event->status] ?? $statusColors['upcoming'] }}">
                                            {{ ucfirst($event->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        <i class="bi bi-calendar3"></i> {{ $event->date }}
                                        @if($event->time) at {{ $event->time }} @endif
                                    </p>
                                    <p class="mt-1 line-clamp-1 text-sm text-slate-500">
                                        <i class="bi bi-geo-alt"></i> {{ $event->place }}
                                    </p>
                                    <p class="mt-2 text-xs font-medium text-indigo-600">
                                        {{ number_format($event->tickets_sold) }}
                                        / {{ number_format($event->total_tickets) }} tickets sold
                                    </p>
                                </a>
                            @empty
                                <p class="text-sm text-slate-500">No upcoming events.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">Drafts</h3>
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                {{ $draftEvents->count() }}
                            </span>
                        </div>

                        <div class="max-h-48 space-y-3 overflow-y-auto pr-1">
                            @forelse($draftEvents as $event)
                                <a href="{{ route('organizer.events.show', $event) }}"
                                    class="block rounded-2xl border border-slate-100 p-4 transition hover:border-amber-200 hover:bg-amber-50/40">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="line-clamp-1 font-semibold text-slate-900">{{ $event->name }}</p>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                            style="background-color: {{ $statusColors['unpublished'] }}">
                                            Unpublished
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        <i class="bi bi-calendar3"></i> {{ $event->date ?? 'Date TBD' }}
                                    </p>
                                </a>
                            @empty
                                <p class="text-sm text-slate-500">No unpublished drafts.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">Past & Cancelled</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ $pastEvents->count() }}
                            </span>
                        </div>

                        <div class="max-h-72 space-y-3 overflow-y-auto pr-1">
                            @forelse($pastEvents as $event)
                                <a href="{{ route('organizer.events.show', $event) }}"
                                    class="block rounded-2xl border border-slate-100 p-4 transition hover:border-slate-300 hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="line-clamp-1 font-semibold text-slate-900">{{ $event->name }}</p>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                            style="background-color: {{ $statusColors[$event->status] ?? $statusColors['completed'] }}">
                                            {{ ucfirst($event->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        <i class="bi bi-calendar3"></i> {{ $event->date }}
                                    </p>
                                    <p class="mt-2 text-xs font-medium text-slate-600">
                                        {{ number_format($event->tickets_sold) }} tickets sold · View event
                                    </p>
                                </a>
                            @empty
                                <p class="text-sm text-slate-500">No past events yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('organizer-calendar');
                const events = @json($calendarEvents);

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    height: 'auto',
                    events: events,
                    eventClick: function (info) {
                        info.jsEvent.preventDefault();
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },
                    eventDidMount: function (info) {
                        const status = info.event.extendedProps.statusLabel;
                        const place = info.event.extendedProps.place || 'No venue';
                        const tickets = info.event.extendedProps.ticketCount;
                        const capacity = info.event.extendedProps.capacity;
                        info.el.title = `${info.event.title} (${status}) — ${place} — ${tickets}/${capacity} tickets`;
                    },
                });

                calendar.render();
            });
        </script>
    @endpush
</x-app-layout>

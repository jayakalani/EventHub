<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">My Event Calendar</h2>
                <p class="mt-1 text-slate-500">Events you have tickets for — click any event to view details.</p>
            </div>
            <a href="{{ route('attendee.bookings.index') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                <i class="bi bi-ticket-perforated"></i>
                My Tickets
            </a>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
        <style>
            #attendee-calendar .fc-event { cursor: pointer; border-radius: 8px; font-size: 0.8rem; }
            #attendee-calendar .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; color: #0f172a; }
            #attendee-calendar .fc-button { border-radius: 10px !important; font-weight: 600; }
        </style>
    @endpush

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Legend --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-700 mb-3">Event status</p>
                <div class="flex flex-wrap gap-4">
                    @foreach([
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ] as $key => $label)
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $statusColors[$key] }}"></span>
                            <span class="text-sm text-slate-600">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-8 xl:grid-cols-3">
                {{-- Calendar --}}
                <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                    <div id="attendee-calendar"></div>
                </div>

                {{-- Sidebar lists --}}
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">Upcoming Events</h3>
                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                {{ $upcomingEvents->count() }}
                            </span>
                        </div>

                        @forelse($upcomingEvents as $event)
                            @php $status = $event->calendarDisplayStatus(); @endphp
                            <a href="{{ route('attendee.events.show', $event) }}"
                                class="block mb-3 rounded-2xl border border-slate-100 p-4 hover:border-indigo-200 hover:bg-indigo-50/40 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-slate-900 line-clamp-1">{{ $event->name }}</p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                        style="background-color: {{ $statusColors[$status] }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    <i class="bi bi-calendar3"></i> {{ $event->date }} at {{ $event->time }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 line-clamp-1">
                                    <i class="bi bi-geo-alt"></i> {{ $event->place }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-indigo-600">
                                    {{ $event->user_ticket_count }} ticket(s) · View event
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No upcoming events with purchased tickets.</p>
                        @endforelse
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">Past & Attended</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                {{ $pastEvents->count() }}
                            </span>
                        </div>

                        @forelse($pastEvents as $event)
                            @php $status = $event->calendarDisplayStatus(); @endphp
                            <a href="{{ route('attendee.events.show', $event) }}"
                                class="block mb-3 rounded-2xl border border-slate-100 p-4 hover:border-slate-300 hover:bg-slate-50 transition">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-slate-900 line-clamp-1">{{ $event->name }}</p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                        style="background-color: {{ $statusColors[$status] }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    <i class="bi bi-calendar3"></i> {{ $event->date }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-600">
                                    {{ $event->user_ticket_count }} ticket(s) · View event
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

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('attendee-calendar');
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
                        const place = info.event.extendedProps.place;
                        const tickets = info.event.extendedProps.ticketCount;
                        info.el.title = `${info.event.title} (${status}) — ${place} — ${tickets} ticket(s)`;
                    },
                });

                calendar.render();
            });
        </script>
    @endpush

</x-app-layout>

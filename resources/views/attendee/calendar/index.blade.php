<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="min-w-0 flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">
                <h2 class="text-lg font-bold leading-tight text-slate-900 sm:text-xl shrink-0">{{ t(['en' => 'My Event Calendar', 'si' => 'මගේ ප්‍රසංග දින දර්ශනය']) }}</h2>
                <p class="text-xs text-slate-500 sm:text-sm">{{ t(['en' => 'Events you have tickets for — click any event to view details.', 'si' => 'ඔබට ටිකට් ඇති ප්‍රසංග විස්තර බැලීමට , ඕනෑම ප්‍රසංගයක් ක්ලික් කරන්න.']) }}</p>
            </div>
            <a href="{{ route('attendee.bookings.index') }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-dark transition sm:text-sm">
                <i class="bi bi-ticket-perforated"></i>
                {{ t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']) }}
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

    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Legend --}}
            <div class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    <p class="text-xs font-semibold text-slate-700">{{ t(['en' => 'Event status', 'si' => 'ප්‍රසංග තත්ත්වය']) }}</p>
                    @foreach([
                        'upcoming' => t(['en' => 'Upcoming', 'si' => 'ඉදිරියට']),
                        'ongoing' => t(['en' => 'Ongoing', 'si' => 'පවතින']),
                        'completed' => t(['en' => 'Completed', 'si' => 'අවසන්']),
                        'cancelled' => t(['en' => 'Cancelled', 'si' => 'අවලංගු']),
                    ] as $key => $label)
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $statusColors[$key] }}"></span>
                            <span class="text-xs text-slate-600">{{ $label }}</span>
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
                            <h3 class="text-lg font-bold text-slate-900">{{ t(['en' => 'Upcoming Events', 'si' => 'ඉදිරි ප්‍රසංග']) }}</h3>
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
                                    <i class="bi bi-calendar3"></i> {{ $event->date }} {{ t(['en' => 'at', 'si' => 'දී']) }} {{ $event->time }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 line-clamp-1">
                                    <i class="bi bi-geo-alt"></i> {{ $event->place }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-indigo-600">
                                    {{ $event->user_ticket_count }} {{ t(['en' => 'ticket(s) · View event', 'si' => 'ටිකට් ·  ප්‍රසංගය බලන්න']) }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ t(['en' => 'No upcoming events with purchased tickets.', 'si' => 'මිලදී ගත් ටිකට් සදහා ඉදිරි ප්‍රසංග නැත.']) }}</p>
                        @endforelse
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">{{ t(['en' => 'Past & Attended', 'si' => 'අතීත සහ සහභාගී වූ']) }}</h3>
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
                                    {{ $event->user_ticket_count }} {{ t(['en' => 'ticket(s) · View event', 'si' => 'ටිකට් ·  ප්‍රසංගය බලන්න']) }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ t(['en' => 'No past events yet.', 'si' => 'තවම අතීත ප්‍රසංග නැත.']) }}</p>
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
                    buttonText: {
                        today: @js(t(['en' => 'today', 'si' => 'අද'])),
                        month: @js(t(['en' => 'month', 'si' => 'මාසය'])),
                        week: @js(t(['en' => 'week', 'si' => 'සතිය'])),
                        list: @js(t(['en' => 'list', 'si' => 'ලැයිස්තුව'])),
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
                        info.el.title = `${info.event.title} (${status}) — ${place} — ${tickets} {{ t(['en' => 'ticket(s)', 'si' => 'ටිකට්']) }}`;
                    },
                });

                calendar.render();
            });
        </script>
    @endpush

</x-app-layout>

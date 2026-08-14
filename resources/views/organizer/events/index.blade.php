<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Events
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Manage your events, schedules, and publication status.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('organizer.events.create') }}"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    + New Event
                </a>

                <a href="{{ route('organizer.events.export.csv', request()->only(['search', 'status', 'from_date', 'to_date'])) }}"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Export CSV
                </a>

                <a href="{{ route('organizer.events.export.pdf', request()->only(['search', 'status', 'from_date', 'to_date'])) }}"
                    class="inline-flex items-center rounded-xl bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $organizerEventsPageConfig = [
            'cancelModal' => [
                'open' => $errors->has('cancellation_reason'),
                'eventId' => old('_cancel_event_id'),
                'action' => old('_cancel_event_id') ? route('organizer.events.cancel', old('_cancel_event_id')) : '',
                'name' => old('_cancel_event_name', ''),
                'date' => old('_cancel_event_date', ''),
                'time' => old('_cancel_event_time', ''),
                'place' => old('_cancel_event_place', ''),
            ],
            'postponeModal' => [
                'open' => $errors->has('postponement_reason') || ($errors->has('new_date') && old('_postpone_event_id')) || ($errors->has('new_time') && old('_postpone_event_id')),
                'eventId' => old('_postpone_event_id'),
                'action' => old('_postpone_event_id') ? route('organizer.events.postpone', old('_postpone_event_id')) : '',
                'name' => old('_postpone_event_name', ''),
                'date' => old('_postpone_event_date', ''),
                'time' => old('_postpone_event_time', ''),
                'place' => old('_postpone_event_place', ''),
            ],
            'scheduleModal' => [
                'open' => $errors->has('schedule_date') || $errors->has('schedule_time') || $errors->has('schedule_place'),
                'mode' => 'postponed',
                'eventId' => old('_schedule_event_id'),
                'action' => old('_schedule_event_id') ? route('organizer.events.postponed-schedule', old('_schedule_event_id')) : '',
                'name' => old('_schedule_event_name', ''),
            ],
            'eventsBaseUrl' => url('organizer/events'),
        ];
    @endphp

    <script type="application/json" id="organizer-events-page-config">@json($organizerEventsPageConfig)</script>

    <div class="py-5" x-data="JSON.parse(document.getElementById('organizer-events-page-config').textContent)">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Total Events</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $events->total() }}
                    </h3>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Upcoming</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ $events->where('status', 'upcoming')->count() }}
                    </h3>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Unpublished</p>
                    <h3 class="mt-1 text-2xl font-bold text-amber-600">
                        {{ $events->where('status', 'unpublished')->count() }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <form method="GET" action="{{ route('organizer.events.index') }}"
                    class="grid grid-cols-1 gap-2.5 md:grid-cols-6">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search event..."
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <select name="status"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="unpublished" {{ request('status') == 'unpublished' ? 'selected' : '' }}>Unpublished
                        </option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming
                        </option>
                        <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing
                        </option>
                        <option value="postponed" {{ request('status') == 'postponed' ? 'selected' : '' }}>Postponed
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived
                        </option>
                    </select>

                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="rounded-xl border-slate-300 text-sm">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="rounded-xl border-slate-300 text-sm">

                    <button type="submit"
                        class="rounded-xl bg-indigo-600 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Apply
                    </button>

                    <a href="{{ route('organizer.events.index') }}"
                        class="flex items-center justify-center rounded-xl bg-slate-100 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                        Reset
                    </a>
                </form>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->has('status'))
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700">
                    {{ $errors->first('status') }}
                </div>
            @endif

            {{-- Table --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-base font-semibold text-slate-900">
                        Event Directory
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    ID
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Event Name
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Host
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Category
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Place
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Tickets
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($events as $event)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-slate-900">
                                            #{{ $event->id }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $event->name }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $event->time }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $event->host->name ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $event->eventCategory->name ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        @if ($event->hasDateYetToBeScheduled())
                                            <span class="font-medium text-amber-700">Not decided yet</span>
                                        @elseif ($event->isPostponed())
                                            <span class="font-medium text-amber-700">{{ $event->date }}</span>
                                            <span class="mt-0.5 block text-[11px] text-amber-600">Rescheduled</span>
                                        @else
                                            {{ $event->date }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $event->displayPlace() }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ number_format($event->total_tickets) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($event->trashed())
                                            <span class="inline-flex rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                Archived
                                            </span>
                                        @elseif ($event->status === 'cancelled')
                                            <span class="inline-flex rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                Cancelled
                                            </span>
                                        @elseif ($event->status === 'completed')
                                            <span class="inline-flex rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                Completed
                                            </span>
                                        @elseif ($event->status === 'postponed')
                                            <div class="space-y-1.5">
                                                <button type="button"
                                                    class="inline-flex rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 transition hover:bg-amber-200"
                                                    title="Set new date and time"
                                                    @click="scheduleModal = {
                                                        open: true,
                                                        mode: 'postponed',
                                                        eventId: {{ $event->id }},
                                                        action: '{{ route('organizer.events.postponed-schedule', $event->id) }}',
                                                        name: @js($event->name),
                                                    }">
                                                    POSTPONED
                                                </button>
                                                <p class="text-[11px] leading-snug text-amber-700">
                                                    {{ $event->postponementScheduleLabel() }}
                                                </p>
                                                <p class="text-[10px] text-slate-400">Click POSTPONED to set place/date/time</p>
                                                <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status"
                                                        data-event-id="{{ $event->id }}"
                                                        data-event-name="{{ $event->name }}"
                                                        data-event-date="{{ $event->date }}"
                                                        data-event-time="{{ $event->time }}"
                                                        data-event-place="{{ $event->place }}"
                                                        data-date-tba="{{ $event->hasDateYetToBeScheduled() ? '1' : '0' }}"
                                                        data-current-status="{{ $event->status }}"
                                                        onchange="window.organizerHandleEventStatusChange(this)"
                                                        class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="postponed" selected>Postponed</option>
                                                        <option value="ongoing">Ongoing</option>
                                                        <option value="cancelled">Cancel Event</option>
                                                    </select>
                                                </form>
                                                <p class="text-[10px] text-slate-400">Set schedule if needed, then mark Ongoing when the event is running. Cancel only if it will not happen.</p>
                                            </div>
                                        @elseif ($event->status === 'upcoming' && $event->hasDateYetToBeScheduled())
                                            <div class="space-y-1.5">
                                                <button type="button"
                                                    class="inline-flex rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 transition hover:bg-sky-200"
                                                    title="Confirm place, date and time"
                                                    @click="scheduleModal = {
                                                        open: true,
                                                        mode: 'upcoming',
                                                        eventId: {{ $event->id }},
                                                        action: '{{ route('organizer.events.postponed-schedule', $event->id) }}',
                                                        name: @js($event->name),
                                                    }">
                                                    UPCOMING · TBA
                                                </button>
                                                <p class="text-[11px] leading-snug text-sky-700">
                                                    Place, date &amp; time not decided yet
                                                </p>
                                                <p class="text-[10px] text-slate-400">Click to confirm schedule</p>
                                                <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                    method="POST">
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
                                                        class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="upcoming" selected>Upcoming</option>
                                                        <option value="postponed">Postpone Event</option>
                                                        <option value="cancelled">Cancel Event</option>
                                                    </select>
                                                </form>
                                            </div>
                                        @elseif ($event->status === 'ongoing')
                                            <div class="space-y-1.5">
                                                <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status"
                                                        data-event-id="{{ $event->id }}"
                                                        data-event-name="{{ $event->name }}"
                                                        data-event-date="{{ $event->date }}"
                                                        data-event-time="{{ $event->time }}"
                                                        data-event-place="{{ $event->place }}"
                                                        data-current-status="{{ $event->status }}"
                                                        data-has-passed="{{ $event->hasPassed() ? '1' : '0' }}"
                                                        onchange="window.organizerHandleEventStatusChange(this)"
                                                        class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="ongoing" selected>Ongoing</option>
                                                        <option value="completed"
                                                            @disabled(! $event->hasPassed())
                                                            title="{{ $event->hasPassed() ? 'Mark event as completed' : 'Available after the event date has passed' }}">
                                                            Completed
                                                        </option>
                                                    </select>
                                                </form>
                                                @unless ($event->hasPassed())
                                                    <p class="text-[10px] text-slate-400">Completed unlocks after the event date has passed.</p>
                                                @endunless
                                            </div>
                                        @else
                                            <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                method="POST">
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
                                                    class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="unpublished"
                                                        {{ $event->status == 'unpublished' ? 'selected' : '' }}
                                                        @if ($event->ticket_bookings_count > 0) disabled
                                                            title="Cannot unpublish: tickets have been sold" @endif>
                                                        Unpublished
                                                    </option>
                                                    <option value="upcoming"
                                                        {{ $event->status == 'upcoming' ? 'selected' : '' }}>Upcoming
                                                    </option>
                                                    @if ($event->status !== 'unpublished')
                                                        <option value="ongoing"
                                                            {{ $event->status == 'ongoing' ? 'selected' : '' }}>Ongoing
                                                        </option>
                                                    @endif
                                                    @if ($event->status === 'upcoming')
                                                        <option value="postponed">Postpone Event</option>
                                                    @endif
                                                    <option value="cancelled">Cancel Event</option>
                                                </select>
                                            </form>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5">
                                            <a href="{{ route('organizer.events.show', $event->id) }}"
                                                class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200">
                                                View
                                            </a>

                                            @if ($event->trashed())
                                                @can('restore', $event)
                                                    <form action="{{ route('organizer.events.restore', $event->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                            onclick="return confirm('Restore this event to your active list?')"
                                                            class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                                                            Undo Archive
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('update', $event)
                                                    <a href="{{ route('organizer.events.edit', $event->id) }}"
                                                        class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-600 transition hover:bg-blue-100">
                                                        Edit
                                                    </a>
                                                @endcan

                                                @can('archive', $event)
                                                    <form action="{{ route('organizer.events.destroy', $event->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            onclick="return confirm('Archive this event? Booking history will be preserved.')"
                                                            class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-100">
                                                            Archive
                                                        </button>
                                                    </form>
                                                @elsecan('delete', $event)
                                                    <form action="{{ route('organizer.events.destroy', $event->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button onclick="return confirm('Delete this event?')"
                                                            class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-100">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-sm text-slate-500">
                                        No events found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">
                    {{ $events->links() }}
                </div>
            </div>

            {{-- Archived events --}}
            @unless (request('status') === 'archived')
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                x-data="{ archivedOpen: {{ request()->boolean('archived') ? 'true' : 'false' }} }">
                <button type="button"
                    class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left transition hover:bg-slate-50"
                    @click="archivedOpen = !archivedOpen"
                    :aria-expanded="archivedOpen.toString()">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Archived events</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Completed events you archived. Attendees still see these as past events.</p>
                    </div>
                    <span class="inline-flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                            {{ $archivedEvents->count() }}
                        </span>
                        <i class="bi text-slate-400" :class="archivedOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </span>
                </button>

                <div x-show="archivedOpen" x-cloak class="border-t border-slate-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Host</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Place</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tickets</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($archivedEvents as $event)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">#{{ $event->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-semibold text-slate-900">{{ $event->name }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500">{{ $event->time }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $event->host->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $event->eventCategory->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $event->date }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $event->displayPlace() }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ number_format($event->total_tickets) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                Archived
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1.5">
                                                <a href="{{ route('organizer.events.show', $event->id) }}"
                                                    class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200">
                                                    View
                                                </a>
                                                @can('restore', $event)
                                                    <form action="{{ route('organizer.events.restore', $event->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        <button type="submit"
                                                            onclick="return confirm('Restore this event to your active list?')"
                                                            class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                                                            Undo Archive
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-10 text-center text-sm text-slate-500">
                                            No archived events yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endunless
        </div>

        @include('organizer.events.partials.cancel-event-modal')
        @include('organizer.events.partials.postpone-event-modal')
        @include('organizer.events.partials.postponed-schedule-modal')
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        @include('organizer.events.partials.status-change-script')
    @endpush
</x-app-layout>

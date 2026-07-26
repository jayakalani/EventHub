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

                <a href="{{ route('organizer.events.export.csv') }}"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Export CSV
                </a>

                <a href="{{ route('organizer.events.export.pdf') }}"
                    class="inline-flex items-center rounded-xl bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-5" x-data="{
        cancelModal: {
            open: {{ $errors->has('cancellation_reason') ? 'true' : 'false' }},
            eventId: @js(old('_cancel_event_id')),
            action: @js(old('_cancel_event_id') ? route('organizer.events.cancel', old('_cancel_event_id')) : ''),
            name: @js(old('_cancel_event_name', '')),
            date: @js(old('_cancel_event_date', '')),
            time: @js(old('_cancel_event_time', '')),
            place: @js(old('_cancel_event_place', '')),
        },
        handleStatusChange(select, eventId, eventName, eventDate, eventTime, eventPlace, currentStatus) {
            if (select.value === 'cancelled' && currentStatus !== 'cancelled') {
                select.value = currentStatus;
                this.cancelModal = {
                    open: true,
                    eventId,
                    action: @js(url('organizer/events')) + '/' + eventId + '/cancel',
                    name: eventName,
                    date: eventDate,
                    time: eventTime,
                    place: eventPlace,
                };
                return;
            }

            if (currentStatus !== 'cancelled') {
                select.form.submit();
            }
        }
    }">
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
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
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
                                        {{ $event->date }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $event->place }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ number_format($event->total_tickets) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($event->status === 'cancelled')
                                            <span class="inline-flex rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                Cancelled
                                            </span>
                                        @elseif ($event->status === 'completed')
                                            <span class="inline-flex rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                Completed
                                            </span>
                                        @else
                                            <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')

                                                <select name="status"
                                                    @change="handleStatusChange($event.target, {{ $event->id }}, @js($event->name), @js($event->date), @js($event->time), @js($event->place), @js($event->status))"
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
                                                    <option value="ongoing"
                                                        {{ $event->status == 'ongoing' ? 'selected' : '' }}>Ongoing
                                                    </option>
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

                                            <a href="{{ route('organizer.events.edit', $event->id) }}"
                                                class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-600 transition hover:bg-blue-100">
                                                Edit
                                            </a>

                                            <form action="{{ route('organizer.events.destroy', $event->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')

                                                <button onclick="return confirm('Delete this event?')"
                                                    class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-100">
                                                    Delete
                                                </button>
                                            </form>
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
        </div>

        @include('organizer.events.partials.cancel-event-modal')
    </div>
</x-app-layout>

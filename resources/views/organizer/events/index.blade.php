<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Events
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Manage your events, schedules, and publication status.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('organizer.events.create') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 transition">
                    + New Event
                </a>

                <a href="{{ route('organizer.events.export.csv') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 transition">
                    Export CSV
                </a>

                <a href="{{ route('organizer.events.export.pdf') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-rose-600 text-white text-sm font-semibold shadow hover:bg-rose-700 transition">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Events</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2">
                        {{ $events->total() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Upcoming</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">
                        {{ $events->where('status', 'upcoming')->count() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Unpublished</p>
                    <h3 class="text-3xl font-bold text-amber-600 mt-2">
                        {{ $events->where('status', 'unpublished')->count() }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
                <form method="GET" action="{{ route('organizer.events.index') }}"
                    class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search event..."
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                    <select name="status"
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
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
                        class="rounded-xl border-slate-300">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="rounded-xl border-slate-300">

                    <button type="submit"
                        class="rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
                        Apply
                    </button>

                    <a href="{{ route('organizer.events.index') }}"
                        class="flex items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition">
                        Reset
                    </a>
                </form>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('status'))
                <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3">
                    {{ $errors->first('status') }}
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">
                        Event Directory
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    ID
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Event Name
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Host
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Category
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Place
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Tickets
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($events as $event)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-900">
                                            #{{ $event->id }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            {{ $event->name }}
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            {{ $event->time }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $event->host->name ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $event->eventCategory->name ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $event->date }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $event->place }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ number_format($event->total_tickets) }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($event->status === 'cancelled')
                                            <span class="inline-flex rounded-xl bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700">
                                                Cancelled
                                            </span>
                                        @elseif ($event->status === 'completed')
                                            <span class="inline-flex rounded-xl bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                                Completed
                                            </span>
                                        @else
                                            <form action="{{ route('organizer.events.updateStatus', $event->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')

                                                <select name="status"
                                                    @change="handleStatusChange($event.target, {{ $event->id }}, @js($event->name), @js($event->date), @js($event->time), @js($event->place), @js($event->status))"
                                                    class="rounded-xl border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
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

                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('organizer.events.show', $event->id) }}"
                                                class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition">
                                                View
                                            </a>

                                            <a href="{{ route('organizer.events.edit', $event->id) }}"
                                                class="px-3 py-2 rounded-xl bg-blue-50 text-blue-600 font-medium hover:bg-blue-100 transition">
                                                Edit
                                            </a>

                                            <form action="{{ route('organizer.events.destroy', $event->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')

                                                <button onclick="return confirm('Delete this event?')"
                                                    class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 font-medium hover:bg-rose-100 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-16 text-center text-slate-500">
                                        No events found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-6 py-4 bg-slate-50">
                    {{ $events->links() }}
                </div>
            </div>
        </div>

        @include('organizer.events.partials.cancel-event-modal')
    </div>
</x-app-layout>

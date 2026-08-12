<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Guest List
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Guests, bookings, and event-day check-in across your events.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($hasOngoingEvents)
                    <a href="{{ route('organizer.bookings.scan', request()->only('event_id')) }}"
                        class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        Scan / Check-in
                    </a>
                @else
                    <span
                        title="Set an event to Ongoing on the Events page to enable check-in."
                        class="inline-flex cursor-not-allowed items-center rounded-xl bg-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-500">
                        Scan / Check-in
                    </span>
                @endif
                <a href="{{ route('organizer.bookings.export.csv', request()->query()) }}"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

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

            {{-- Statistics --}}
            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Total Tickets</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ number_format($stats['total']) }}
                    </h3>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Valid Entry</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ number_format($stats['confirmed']) }}
                    </h3>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Checked In</p>
                    <h3 class="mt-1 text-2xl font-bold text-sky-600">
                        {{ number_format($stats['checked_in']) }}
                    </h3>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Awaiting Check-in</p>
                    <h3 class="mt-1 text-2xl font-bold text-amber-600">
                        {{ number_format($stats['awaiting_check_in']) }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <form method="GET" action="{{ route('organizer.bookings.index') }}"
                    class="grid grid-cols-1 gap-2.5 md:grid-cols-3 lg:grid-cols-8">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search guest, email, ticket..."
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2">

                    <select name="event_id"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected(($filters['event_id'] ?? null) == $event->id)>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>

                    <select name="check_in"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Check-in</option>
                        <option value="checked_in" @selected(($filters['check_in'] ?? null) === 'checked_in')>
                            Checked In
                        </option>
                        <option value="not_checked_in" @selected(($filters['check_in'] ?? null) === 'not_checked_in')>
                            Not Checked In
                        </option>
                    </select>

                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 rounded-xl bg-indigo-600 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Apply
                        </button>
                        <a href="{{ route('organizer.bookings.index') }}"
                            class="flex flex-1 items-center justify-center rounded-xl bg-slate-100 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-base font-semibold text-slate-900">
                        Bookings
                        <span class="ml-2 text-sm font-normal text-slate-500">
                            {{ number_format($bookings->total()) }} results
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Guest
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Event
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ticket
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Check-in
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($bookings as $booking)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $booking->user?->full_name ?? 'Unknown' }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $booking->user?->email ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($booking->event)
                                            <a href="{{ route('organizer.events.show', $booking->event) }}"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                                {{ $booking->event->name }}
                                            </a>
                                        @else
                                            <span class="text-sm text-slate-500">—</span>
                                        @endif
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $booking->ticketCategory?->name ?? 'General' }}
                                            · LKR {{ number_format((float) $booking->ticket_price, 2) }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs font-medium text-slate-800">
                                            {{ $booking->ticket_number }}
                                        </span>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $booking->created_at?->format('M d, Y H:i') ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $booking->displayStatusBadgeClasses() }}">
                                            {{ $booking->displayStatusLabel() }}
                                        </span>
                                        @if ($booking->refundRequest)
                                            <div class="mt-1 text-[11px] capitalize text-slate-500">
                                                Refund: {{ str_replace('_', ' ', $booking->refundRequest->status->value) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($booking->isCheckedIn())
                                            <span
                                                class="inline-flex rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800">
                                                Checked In
                                            </span>
                                            <div class="mt-1 text-[11px] text-slate-500">
                                                {{ $booking->checked_in_at?->format('M d, H:i') }}
                                            </div>
                                        @else
                                            <span
                                                class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                Waiting
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('organizer.bookings.show', $booking) }}"
                                                class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200">
                                                View
                                            </a>

                                            @if ($booking->canCheckIn())
                                                <form action="{{ route('organizer.bookings.check-in', $booking) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                                                        Check In
                                                    </button>
                                                </form>
                                            @elseif ($booking->canUndoCheckIn())
                                                <form action="{{ route('organizer.bookings.undo-check-in', $booking) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Undo check-in for this guest?')"
                                                        class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800 transition hover:bg-amber-100">
                                                        Undo
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <h3 class="text-base font-semibold text-slate-800">
                                                No bookings found
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                No tickets match your filters. Try adjusting search or status.
                                            </p>
                                            <a href="{{ route('organizer.bookings.index') }}"
                                                class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                                Clear filters
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

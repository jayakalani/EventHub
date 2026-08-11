<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Sales
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Ticket-by-ticket sales list · for charts &amp; trends open
                    <a href="{{ route('organizer.reports', array_filter([
                        'event_id' => $filters['event_id'] ?? null,
                        'from' => $filters['from_date'] ?? null,
                        'to' => $filters['to_date'] ?? null,
                    ], fn ($value) => filled($value))) }}"
                        class="font-semibold text-indigo-600 hover:text-indigo-700">Reports</a>.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('organizer.reports', array_filter([
                        'event_id' => $filters['event_id'] ?? null,
                        'from' => $filters['from_date'] ?? null,
                        'to' => $filters['to_date'] ?? null,
                    ], fn ($value) => filled($value))) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-800 shadow-sm transition hover:bg-indigo-100">
                    <i class="bi bi-bar-chart-line"></i>
                    Open analytics
                </a>
                <a href="{{ route('organizer.sales.export.csv', request()->query()) }}"
                    class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Export CSV
                </a>
                <a href="{{ route('organizer.sales.export.pdf', request()->query()) }}"
                    class="inline-flex items-center rounded-xl bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Purchases</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ number_format($stats['purchases']) }}
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Tickets Sold</p>
                    <h3 class="mt-1 text-2xl font-bold text-sky-600">
                        {{ number_format($stats['tickets']) }}
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Unique Buyers</p>
                    <h3 class="mt-1 text-2xl font-bold text-indigo-600">
                        {{ number_format($stats['unique_buyers']) }}
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Revenue</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600">
                        LKR {{ number_format($stats['revenue'], 2) }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <form method="GET" action="{{ route('organizer.sales.index') }}"
                    class="grid grid-cols-1 gap-2.5 md:grid-cols-3 lg:grid-cols-6">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search ticket #, event, category..."
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

                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 rounded-xl bg-indigo-600 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Apply
                        </button>
                        <a href="{{ route('organizer.sales.index') }}"
                            class="flex flex-1 items-center justify-center rounded-xl bg-slate-100 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Ticket list --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-base font-semibold text-slate-900">
                        Tickets
                        <span class="ml-2 text-sm font-normal text-slate-500">
                            {{ number_format($sales->total()) }} {{ Str::plural('ticket', $sales->total()) }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ticket number
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Event
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ticket category
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    When
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Check-in
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ticket status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sales as $ticket)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ $ticket['url'] }}"
                                            class="font-mono text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                            {{ $ticket['ticket_number'] }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        <a href="{{ $ticket['event_url'] }}"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $ticket['event'] }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-black/5"
                                            style="background-color: {{ ($ticket['category_color'] ?? '#6366f1') }}18;">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                style="background-color: {{ $ticket['category_color'] ?? '#6366f1' }}"></span>
                                            {{ $ticket['category'] }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                        LKR {{ number_format($ticket['amount'], 2) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-700">
                                            {{ $ticket['booked_at_formatted'] }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $ticket['booked_at'] }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $ticket['check_in_badge_classes'] }}">
                                            {{ $ticket['check_in_status'] }}
                                        </span>
                                        @if (! empty($ticket['checked_in_at']))
                                            <div class="mt-1 text-[11px] text-slate-500">
                                                {{ $ticket['checked_in_at'] }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $ticket['status_badge_classes'] }}">
                                            {{ $ticket['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <h3 class="text-base font-semibold text-slate-800">
                                                No tickets found
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                No confirmed ticket sales match your filters.
                                            </p>
                                            <a href="{{ route('organizer.sales.index') }}"
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
                {{ $sales->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

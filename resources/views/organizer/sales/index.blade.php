<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Sales
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Transaction list of ticket purchases · for charts &amp; trends open
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
                        placeholder="Search buyer, email, event..."
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

            {{-- Feed --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-base font-semibold text-slate-900">
                        Activity feed
                        <span class="ml-2 text-sm font-normal text-slate-500">
                            {{ number_format($sales->total()) }} purchases
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Buyer
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Event
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Tickets
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Qty
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    When
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($sales as $purchase)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700">
                                                {{ strtoupper(substr($purchase['buyer'], 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-slate-900">
                                                    {{ $purchase['buyer'] }}
                                                </div>
                                                <div class="mt-0.5 truncate text-xs text-slate-500">
                                                    {{ $purchase['email'] }}
                                                </div>
                                                @if (! empty($purchase['payment_reference']))
                                                    <div class="mt-0.5 font-mono text-[11px] text-slate-400">
                                                        {{ $purchase['payment_reference'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <a href="{{ $purchase['event_url'] }}"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $purchase['event'] }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($purchase['category_badges'] ?? [] as $badge)
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-black/5"
                                                    style="background-color: {{ ($badge['color'] ?? '#6366f1') }}18;">
                                                    <span class="h-1.5 w-1.5 rounded-full"
                                                        style="background-color: {{ $badge['color'] ?? '#6366f1' }}"></span>
                                                    {{ $badge['label'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-slate-800">
                                        {{ number_format($purchase['quantity']) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                        LKR {{ number_format($purchase['amount'], 2) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm text-slate-700">
                                            {{ $purchase['booked_at_formatted'] }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $purchase['booked_at'] }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ $purchase['event_url'] }}"
                                                class="inline-flex rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                                Event
                                            </a>
                                            <a href="{{ $purchase['guests_url'] }}"
                                                class="inline-flex rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                                Buyer tickets
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <h3 class="text-base font-semibold text-slate-800">
                                                No sales found
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                No confirmed purchases match your filters.
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

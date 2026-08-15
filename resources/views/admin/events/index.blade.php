<x-app-layout>
    @php
        $statusLabels = [
            'unpublished' => 'Unpublished',
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'postponed' => 'Postponed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'archived' => 'Archived',
        ];

        $filterScope = $hasActiveFilters ? 'Within current filters' : 'All platform events';

        $kpis = [
            [
                'label' => 'Matched',
                'value' => $stats['matched'],
                'sub' => $hasActiveFilters ? 'After active filters' : 'All platform events',
                'icon' => 'bi-calendar-event',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Unpublished',
                'value' => $stats['unpublished'],
                'sub' => $filterScope,
                'icon' => 'bi-eye-slash',
                'accent' => 'amber',
            ],
            [
                'label' => 'Postponed',
                'value' => $stats['postponed'],
                'sub' => $filterScope,
                'icon' => 'bi-clock-history',
                'accent' => 'cyan',
            ],
            [
                'label' => 'Cancelled',
                'value' => $stats['cancelled'],
                'sub' => $filterScope,
                'icon' => 'bi-x-octagon',
                'accent' => 'rose',
            ],
        ];

        $organizerName = optional($organizers->firstWhere('id', (int) request('organizer')))->full_name;
        $categoryName = optional($categories->firstWhere('id', (int) request('category')))->name;

        $activeFilterChips = array_filter([
            'search' => request('search') ? 'Search: '.request('search') : null,
            'organizer' => request('organizer') ? 'Organizer: '.($organizerName ?: '#'.request('organizer')) : null,
            'category' => request('category') ? 'Category: '.($categoryName ?: '#'.request('category')) : null,
            'status' => request('status') ? 'Status: '.($statusLabels[request('status')] ?? request('status')) : null,
            'from_date' => request('from_date') ? 'From: '.request('from_date') : null,
            'to_date' => request('to_date') ? 'To: '.request('to_date') : null,
        ]);
    @endphp

    <div class="admin-events relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10">
                                    <i class="bi bi-calendar-event text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Platform catalog</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Events
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Review every organizer event by status, category, and schedule ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.event-categories.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-tags"></i>
                                Categories
                            </a>
                            <a href="{{ route('admin.events.export.csv', request()->query()) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-filetype-csv"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('admin.events.export.pdf', request()->query()) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Export PDF
                            </a>
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Event snapshot</h2>
                    <p class="text-xs text-slate-500">Counts for the current filter set.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700'],
                                'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600', 'value' => 'text-amber-700'],
                                'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600', 'value' => 'text-rose-700'],
                                default => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600', 'value' => 'text-cyan-700'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $accent['value'] }}">
                                        {{ number_format($kpi['value']) }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                                </div>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }}">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Filter events</h2>
                        <p class="text-xs text-slate-500">Search by name, organizer, category, status, or date.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('admin.events.index') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-rose-700 hover:text-rose-800">
                            <i class="bi bi-x-circle"></i>
                            Clear filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.events.index') }}"
                    class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label for="event_search" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <input id="event_search" type="text" name="search" value="{{ request('search') }}"
                            placeholder="Event, organizer, place…"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="xl:col-span-2">
                        <label for="event_organizer" class="mb-1.5 block text-xs font-semibold text-slate-600">Organizer</label>
                        <select id="event_organizer" name="organizer"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700">
                            <option value="">All organizers</option>
                            @foreach ($organizers as $organizer)
                                <option value="{{ $organizer->id }}" @selected((int) request('organizer') === $organizer->id)>
                                    {{ $organizer->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="event_category" class="mb-1.5 block text-xs font-semibold text-slate-600">Category</label>
                        <select id="event_category" name="category"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="event_status" class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <select id="event_status" name="status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700">
                            <option value="">All status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-1">
                        <label for="event_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="event_from" type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-2 text-sm text-slate-700">
                    </div>
                    <div class="xl:col-span-1">
                        <label for="event_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                        <input id="event_to" type="date" name="to_date" value="{{ request('to_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-2 text-sm text-slate-700">
                    </div>
                    <div class="flex items-end xl:col-span-1">
                        <button type="submit"
                            class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/60 pt-4">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</span>
                        @foreach ($activeFilterChips as $chip)
                            <span class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $chip }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                <div class="flex flex-col gap-2 border-b border-white/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Event directory</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $events->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $events->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($events->total()) }}</span>
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 sm:px-6">Event</th>
                                <th class="px-4 py-3">Organizer</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Schedule</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($events as $event)
                                @php
                                    $status = $event->trashed() ? 'archived' : $event->status;
                                    $statusBadge = match ($status) {
                                        'unpublished' => 'bg-slate-100 text-slate-700 ring-slate-200/70',
                                        'upcoming' => 'bg-sky-100 text-sky-700 ring-sky-200/70',
                                        'ongoing' => 'bg-emerald-100 text-emerald-700 ring-emerald-200/70',
                                        'postponed' => 'bg-amber-100 text-amber-800 ring-amber-200/70',
                                        'completed' => 'bg-indigo-100 text-indigo-700 ring-indigo-200/70',
                                        'cancelled' => 'bg-rose-100 text-rose-700 ring-rose-200/70',
                                        default => 'bg-slate-100 text-slate-600 ring-slate-200/70',
                                    };
                                    $ticketCount = (int) ($event->ticket_categories_sum_no_of_tickets ?: $event->no_of_tickets);
                                @endphp
                                <tr class="btn-smooth align-middle hover:bg-white/45">
                                    <td class="px-4 py-4 sm:px-6">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $event->name }}</p>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">#{{ $event->id }} · {{ $event->displayPlace() }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">{{ number_format($ticketCount) }} tickets</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="truncate text-sm font-medium text-slate-800">{{ $event->organizer?->full_name ?? '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $event->eventCategory?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        @if ($event->hasDateYetToBeScheduled())
                                            <span class="font-medium text-amber-700">Not decided yet</span>
                                        @else
                                            {{ $event->formattedScheduleDate() ?: '—' }}
                                            @if ($event->time)
                                                <span class="mt-0.5 block text-[11px] text-slate-400">{{ $event->time }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusBadge }}">
                                            {{ $statusLabels[$status] ?? ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex justify-end">
                                            <a href="{{ route('admin.events.show', $event->id) }}"
                                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-indigo-100 bg-indigo-50/80 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                <i class="bi bi-eye"></i>
                                                Review
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <p class="text-sm font-semibold text-slate-700">No events found</p>
                                        <p class="mt-1 text-xs text-slate-500">Try another organizer, status, or date range.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($events->hasPages())
                    <div class="border-t border-white/60 px-4 py-3 sm:px-6">
                        {{ $events->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

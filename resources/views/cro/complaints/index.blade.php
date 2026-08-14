@php
    $filters = $filters ?? ['status' => null, 'q' => null, 'event' => null, 'from' => null, 'to' => null];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'q' => $filters['q'] ?? null,
        'event' => $filters['event'] ?? null,
        'from' => $filters['from'] ?? null,
        'to' => $filters['to'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $hasActiveFilters = count($filterQuery) > 0;
    $selectedEventName = collect($events ?? [])->firstWhere('id', $filters['event'] ?? null)?->filterLabel();
    $selectedStatusLabel = collect($statuses ?? [])->first(fn ($s) => $s->value === ($filters['status'] ?? null))?->label();
    $activeFilterChips = array_filter([
        filled($filters['q'] ?? null) ? 'Search: '.$filters['q'] : null,
        $selectedStatusLabel ? 'Status: '.$selectedStatusLabel : null,
        $selectedEventName ? 'Event: '.$selectedEventName : null,
        filled($filters['from'] ?? null) ? 'From: '.$filters['from'] : null,
        filled($filters['to'] ?? null) ? 'To: '.$filters['to'] : null,
    ]);
    $statusAccents = [
        'open' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600', 'value' => 'text-rose-700', 'icon' => 'bi-exclamation-triangle'],
        'in_progress' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700', 'icon' => 'bi-hourglass-split'],
        'resolved' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600', 'value' => 'text-emerald-700', 'icon' => 'bi-check-circle'],
        'closed' => ['top' => 'border-t-slate-400', 'iconBg' => 'bg-slate-100/80', 'iconText' => 'text-slate-500', 'value' => 'text-slate-700', 'icon' => 'bi-archive'],
    ];
@endphp

<x-app-layout>
    <div class="relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-rose-50/30 to-indigo-50/45"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-rose-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-indigo-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-rose-200/30 blur-2xl"></div>
                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Support queue</p>
                            <h1 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Complaints</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Event complaints stay with you. General complaints can be claimed by any CRO.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('cro.complaints.export.csv', $filterQuery) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-emerald-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-filetype-csv text-emerald-600"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('cro.complaints.export.pdf', $filterQuery) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-rose-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-file-earmark-pdf text-rose-500"></i>
                                Export PDF
                            </a>
                            <a href="{{ route('cro.dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="glass-panel !rounded-2xl border-emerald-200/80 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-emerald-800">
                    <div class="flex items-center gap-2"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="glass-panel !rounded-2xl border-rose-200/80 bg-rose-50/70 px-4 py-3 text-sm font-medium text-rose-800">
                    <div class="flex items-center gap-2"><i class="bi bi-exclamation-circle-fill"></i>{{ $errors->first() }}</div>
                </div>
            @endif

            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Queue snapshot</h2>
                    <p class="text-xs text-slate-500">Tap a status to filter the list.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($statuses as $s)
                        @php $accent = $statusAccents[$s->value] ?? $statusAccents['closed']; @endphp
                        <a href="{{ route('cro.complaints.index', array_filter(array_merge($filterQuery, ['status' => $s->value]))) }}"
                            class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5 {{ ($filters['status'] ?? '') === $s->value ? 'ring-1 ring-rose-300/70 bg-white/80' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $s->label() }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $accent['value'] }}">
                                        {{ number_format($counts[$s->value] ?? 0) }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $s->description() }}</p>
                                </div>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $accent['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Filter complaints</h2>
                        <p class="text-xs text-slate-500">Search, status, event, or date range.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('cro.complaints.index') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            <i class="bi bi-x-circle"></i>
                            Clear all filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('cro.complaints.index') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label for="complaint_q" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="complaint_q" type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Attendee, email, subject…"
                                class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="complaint_status" class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <select id="complaint_status" name="status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected(($filters['status'] ?? null) === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="complaint_event" class="mb-1.5 block text-xs font-semibold text-slate-600">Event</label>
                        <select id="complaint_event" name="event"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All assigned events</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" @selected(($filters['event'] ?? null) === $event->id)>{{ $event->filterLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="complaint_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="complaint_from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end gap-2 xl:col-span-2">
                        <div class="min-w-0 flex-1">
                            <label for="complaint_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                            <input id="complaint_to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                                class="w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <button type="submit"
                            class="btn-smooth inline-flex h-[38px] shrink-0 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            <i class="bi bi-funnel"></i>
                            <span class="hidden sm:inline">Apply</span>
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

            <section class="glass-card overflow-hidden !rounded-2xl !p-0 hover:!translate-y-0">
                <div class="flex flex-col gap-2 border-b border-white/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Complaint list</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $complaints->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $complaints->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($complaints->total()) }}</span>
                        </p>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Page {{ $complaints->currentPage() }} of {{ max(1, $complaints->lastPage()) }}
                    </p>
                </div>

                @if ($complaints->isEmpty())
                    <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
                        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200/80">
                            <i class="bi bi-exclamation-triangle text-lg"></i>
                        </span>
                        <p class="text-sm font-semibold text-slate-800">No complaints</p>
                        <p class="mt-1 max-w-sm text-xs text-slate-500">
                            No complaints match the selected filters for your assigned events.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-white/50">
                        @foreach ($complaints as $complaint)
                            <a href="{{ route('cro.complaints.show', $complaint) }}"
                                class="btn-smooth group flex flex-col gap-3 px-4 py-4 hover:bg-white/55 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50/90 text-rose-600 ring-1 ring-rose-100/80">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                    <div class="min-w-0 space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-bold text-slate-900">{{ $complaint->subject }}</h3>
                                            @include('partials.cro-sla-badges', ['ticket' => $complaint])
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            {{ $complaint->created_at->format('d M Y, H:i') }}
                                            · {{ $complaint->user->full_name }}
                                            · {{ $complaint->event?->name ?? 'General' }}
                                            · {{ $complaint->assignee?->full_name ?? 'Unassigned' }}
                                            @if ($complaint->attachments->isNotEmpty())
                                                · {{ $complaint->attachments->count() }} attachment{{ $complaint->attachments->count() === 1 ? '' : 's' }}
                                            @endif
                                        </p>
                                        <p class="line-clamp-2 text-sm text-slate-600">{{ $complaint->message }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-3 self-end sm:self-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $complaint->status->badgeClass() }}">
                                        {{ $complaint->status->label() }}
                                    </span>
                                    <span class="text-xs font-semibold text-indigo-600 opacity-80 transition group-hover:translate-x-0.5 group-hover:opacity-100">
                                        Open <i class="bi bi-arrow-right text-[10px]"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if ($complaints->hasPages())
                        <div class="border-t border-white/60 px-4 py-4 sm:px-6">
                            {{ $complaints->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

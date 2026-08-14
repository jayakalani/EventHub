@php
    $filters = $filters ?? ['status' => null, 'event' => null, 'from' => null, 'to' => null];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'event' => $filters['event'] ?? null,
        'from' => $filters['from'] ?? null,
        'to' => $filters['to'] ?? null,
        'q' => $filters['q'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $hasActiveFilters = count($filterQuery) > 0;
    $selectedEventName = collect($events ?? [])->firstWhere('id', $filters['event'] ?? null)?->filterLabel();
    $statusChipLabels = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'declined' => 'Declined',
        'processed' => 'Processed (history)',
        'auto_declined' => 'Auto declined',
    ];
    $activeFilterChips = array_filter([
        filled($filters['q'] ?? null) ? 'Search: '.$filters['q'] : null,
        filled($filters['status'] ?? null) ? 'Status: '.($statusChipLabels[$filters['status']] ?? $filters['status']) : null,
        $selectedEventName ? 'Event: '.$selectedEventName : null,
        filled($filters['from'] ?? null) ? 'From: '.$filters['from'] : null,
        filled($filters['to'] ?? null) ? 'To: '.$filters['to'] : null,
    ]);
    $kpiCards = [
        [
            'key' => 'pending',
            'label' => 'Pending',
            'value' => $counts['pending'] ?? 0,
            'sub' => 'Awaiting review',
            'icon' => 'bi-clock-history',
            'top' => 'border-t-amber-500',
            'iconBg' => 'bg-amber-100/70',
            'iconText' => 'text-amber-600',
            'valueClass' => 'text-amber-700',
            'ring' => 'ring-amber-300/70',
        ],
        [
            'key' => 'approved',
            'label' => 'Approved',
            'value' => $counts['approved'] ?? 0,
            'sub' => 'Refunds granted',
            'icon' => 'bi-check-circle',
            'top' => 'border-t-emerald-500',
            'iconBg' => 'bg-emerald-100/70',
            'iconText' => 'text-emerald-600',
            'valueClass' => 'text-emerald-700',
            'ring' => 'ring-emerald-300/70',
        ],
        [
            'key' => 'declined',
            'label' => 'Declined',
            'value' => $counts['declined'] ?? 0,
            'sub' => 'Not granted',
            'icon' => 'bi-x-circle',
            'top' => 'border-t-rose-500',
            'iconBg' => 'bg-rose-100/70',
            'iconText' => 'text-rose-600',
            'valueClass' => 'text-rose-700',
            'ring' => 'ring-rose-300/70',
        ],
        [
            'key' => 'processed',
            'label' => 'Processed',
            'value' => $counts['processed'] ?? 0,
            'sub' => 'History only',
            'icon' => 'bi-archive',
            'top' => 'border-t-indigo-500',
            'iconBg' => 'bg-indigo-100/70',
            'iconText' => 'text-indigo-600',
            'valueClass' => 'text-indigo-700',
            'ring' => 'ring-indigo-300/70',
        ],
    ];
@endphp

<x-app-layout>
    <div class="relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-amber-50/30 to-indigo-50/45"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-amber-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-indigo-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-emerald-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-amber-200/30 blur-2xl"></div>
                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Support queue</p>
                            <h1 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Refund Requests</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Assigned events only · review pending refunds and browse processed history.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('cro.refund-requests.export.csv', $filterQuery) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-emerald-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-filetype-csv text-emerald-600"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('cro.refund-requests.export.pdf', $filterQuery) }}"
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
                    @foreach ($kpiCards as $kpi)
                        <a href="{{ route('cro.refund-requests.index', array_filter(array_merge($filterQuery, ['status' => $kpi['key']]))) }}"
                            class="glass-card kpi-lift group border-t-4 {{ $kpi['top'] }} p-4 sm:p-5 {{ ($filters['status'] ?? null) === $kpi['key'] ? 'ring-1 '.$kpi['ring'].' bg-white/80' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $kpi['valueClass'] }}">
                                        {{ number_format($kpi['value']) }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                                </div>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kpi['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $kpi['iconText'] }}"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Filter refunds</h2>
                        <p class="text-xs text-slate-500">Search, status, event, or date range.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('cro.refund-requests.index') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            <i class="bi bi-x-circle"></i>
                            Clear all filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('cro.refund-requests.index') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label for="refund_q" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="refund_q" type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Attendee, email, ticket…"
                                class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="refund_status" class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <select id="refund_status" name="status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" @selected(empty($filters['status']))>All statuses</option>
                            <option value="processed" @selected(($filters['status'] ?? null) === 'processed')>Processed (history)</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-3">
                        <label for="refund_event" class="mb-1.5 block text-xs font-semibold text-slate-600">Event</label>
                        <select id="refund_event" name="event"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All assigned events</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" @selected(($filters['event'] ?? null) === $event->id)>
                                    {{ $event->filterLabel() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label for="refund_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="refund_from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end gap-2 xl:col-span-2">
                        <div class="min-w-0 flex-1">
                            <label for="refund_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                            <input id="refund_to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"
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
                        <h2 class="text-sm font-semibold text-slate-900">Refund list</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $refundRequests->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $refundRequests->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($refundRequests->total()) }}</span>
                        </p>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Page {{ $refundRequests->currentPage() }} of {{ max(1, $refundRequests->lastPage()) }}
                    </p>
                </div>

                @if ($refundRequests->isEmpty())
                    <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
                        <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200/80">
                            <i class="bi bi-arrow-counterclockwise text-lg"></i>
                        </span>
                        <p class="text-sm font-semibold text-slate-800">No refund requests</p>
                        <p class="mt-1 max-w-sm text-xs text-slate-500">
                            @if ($hasActiveFilters)
                                No refunds match the selected filters for your assigned events.
                            @else
                                No refunds on your assigned events.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="px-4 py-3 sm:px-6">Event / Ticket</th>
                                    <th class="px-4 py-3 sm:px-6">Attendee</th>
                                    <th class="px-4 py-3 sm:px-6">Amount</th>
                                    <th class="px-4 py-3 sm:px-6">Status</th>
                                    <th class="px-4 py-3 sm:px-6">Reviewed by</th>
                                    <th class="px-4 py-3 sm:px-6">Requested</th>
                                    <th class="px-4 py-3 text-right sm:px-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100/80">
                                @foreach ($refundRequests as $request)
                                    @php
                                        $booking = $request->ticketBooking;
                                        $event = $booking?->event;
                                        $status = $request->status;
                                    @endphp
                                    <tr class="btn-smooth align-top hover:bg-white/45">
                                        <td class="px-4 py-4 sm:px-6">
                                            <p class="font-semibold text-slate-900">{{ $event?->name ?? '—' }}</p>
                                            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking?->ticket_number ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-4 sm:px-6">
                                            <p class="font-medium text-slate-800">{{ $request->user?->full_name ?? '—' }}</p>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ $request->user?->email }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 sm:px-6">
                                            <p class="font-semibold text-emerald-700">Rs {{ number_format((float) $request->refund_amount, 2) }}</p>
                                            <p class="text-xs text-slate-400">{{ $request->refund_percentage }}%</p>
                                        </td>
                                        <td class="px-4 py-4 sm:px-6">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $status->badgeClass() }}">
                                                {{ $status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 sm:px-6">
                                            @if ($status === \App\Enums\RefundRequestStatusEnum::Pending)
                                                <span class="text-slate-400">Awaiting review</span>
                                            @elseif ($request->reviewer)
                                                <p class="font-medium text-slate-800">{{ $request->reviewer->full_name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    {{ $request->reviewed_at?->format('d M Y, H:i') ?? '—' }}
                                                </p>
                                            @elseif ($status->isProcessed())
                                                <p class="font-medium text-slate-600">System</p>
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    {{ $request->reviewed_at?->format('d M Y, H:i') ?? 'Auto-processed' }}
                                                </p>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-slate-500 sm:px-6">
                                            {{ $request->created_at?->format('d M Y') }}
                                            <span class="block text-xs text-slate-400">{{ $request->created_at?->format('H:i') }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-right sm:px-6">
                                            <a href="{{ route('cro.refund-requests.show', $request) }}"
                                                class="btn-smooth inline-flex items-center gap-1 rounded-lg border border-white/70 bg-white/60 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm backdrop-blur hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50">
                                                {{ $status === \App\Enums\RefundRequestStatusEnum::Pending ? 'Review' : 'View' }}
                                                <i class="bi bi-arrow-right text-[10px]"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($refundRequests->hasPages())
                        <div class="border-t border-white/60 px-4 py-4 sm:px-6">
                            {{ $refundRequests->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

@php
    $queueScope = $queueScope ?? 'mine';
    $filters = $filters ?? ['status' => null, 'event' => null, 'from' => null, 'to' => null];
    $scopeQuery = $queueScope === 'all' ? ['scope' => 'all'] : [];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'event' => $filters['event'] ?? null,
        'from' => $filters['from'] ?? null,
        'to' => $filters['to'] ?? null,
        'q' => $filters['q'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $hasActiveFilters = count($filterQuery) > 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Refund Requests</h2>
                <p class="mt-1 text-slate-500">Review pending refunds and browse processed history.</p>
            </div>
            <a href="{{ route('cro.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-6">

            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">{{ $errors->first() }}</div>
            @endif

            @include('partials.cro-queue-scope', [
                'routeName' => 'cro.refund-requests.index',
                'queueScope' => $queueScope,
                'mineLabel' => 'My events',
                'allLabel' => 'All events',
                'mineHint' => 'Showing refunds for events where you are the assigned CRO.',
                'extraQuery' => $filterQuery,
            ])

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('cro.refund-requests.index', array_merge($scopeQuery, array_filter(['event' => $filters['event'], 'from' => $filters['from'], 'to' => $filters['to'], 'status' => 'pending']))) }}"
                    class="rounded-3xl border p-5 transition {{ ($filters['status'] ?? null) === 'pending' ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <p class="text-sm font-medium text-slate-500">Pending</p>
                    <p class="mt-1 text-3xl font-bold text-amber-500">{{ number_format($counts['pending']) }}</p>
                </a>
                <a href="{{ route('cro.refund-requests.index', array_merge($scopeQuery, array_filter(['event' => $filters['event'], 'from' => $filters['from'], 'to' => $filters['to'], 'status' => 'approved']))) }}"
                    class="rounded-3xl border p-5 transition {{ ($filters['status'] ?? null) === 'approved' ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <p class="text-sm font-medium text-slate-500">Approved</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">{{ number_format($counts['approved']) }}</p>
                </a>
                <a href="{{ route('cro.refund-requests.index', array_merge($scopeQuery, array_filter(['event' => $filters['event'], 'from' => $filters['from'], 'to' => $filters['to'], 'status' => 'declined']))) }}"
                    class="rounded-3xl border p-5 transition {{ ($filters['status'] ?? null) === 'declined' ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <p class="text-sm font-medium text-slate-500">Declined</p>
                    <p class="mt-1 text-3xl font-bold text-rose-600">{{ number_format($counts['declined']) }}</p>
                </a>
                <a href="{{ route('cro.refund-requests.index', array_merge($scopeQuery, array_filter(['event' => $filters['event'], 'from' => $filters['from'], 'to' => $filters['to'], 'status' => 'processed']))) }}"
                    class="rounded-3xl border p-5 transition {{ ($filters['status'] ?? null) === 'processed' ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <p class="text-sm font-medium text-slate-500">Processed</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($counts['processed']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">History only</p>
                </a>
            </div>

            <form method="GET" action="{{ route('cro.refund-requests.index') }}"
                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                @if ($queueScope === 'all')
                    <input type="hidden" name="scope" value="all">
                @endif

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <div class="xl:col-span-2">
                        <label for="refund_q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input type="text" id="refund_q" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Attendee, email, ticket, event…"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="refund_status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="refund_status" name="status"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" @selected(empty($filters['status']))>All statuses</option>
                            <option value="processed" @selected(($filters['status'] ?? null) === 'processed')>Processed (history)</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="refund_event" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Event</label>
                        <select id="refund_event" name="event"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All events</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" @selected(($filters['event'] ?? null) === $event->id)>
                                    {{ $event->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="refund_from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" id="refund_from" name="from" value="{{ $filters['from'] ?? '' }}"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="refund_to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" id="refund_to" name="to" value="{{ $filters['to'] ?? '' }}"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Apply
                    </button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('cro.refund-requests.index', $scopeQuery) }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            @if ($refundRequests->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-16 text-center">
                    <h3 class="text-2xl font-bold text-slate-800">No Refund Requests</h3>
                    <p class="mt-2 text-slate-500">
                        @if ($hasActiveFilters)
                            No refunds match the selected filters.
                        @elseif ($queueScope === 'mine')
                            No refunds on your assigned events.
                            <a href="{{ route('cro.refund-requests.index', ['scope' => 'all']) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">View all events</a>
                        @else
                            No refund requests yet.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Event / Ticket</th>
                                    <th class="px-5 py-3">Attendee</th>
                                    <th class="px-5 py-3">Amount</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Reviewed by</th>
                                    <th class="px-5 py-3">Requested</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($refundRequests as $request)
                                    @php
                                        $booking = $request->ticketBooking;
                                        $event = $booking?->event;
                                        $status = $request->status;
                                    @endphp
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-slate-900">{{ $event?->name ?? '—' }}</p>
                                            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $booking?->ticket_number ?? '—' }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-medium text-slate-800">{{ $request->user?->full_name ?? '—' }}</p>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ $request->user?->email }}</p>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <p class="font-semibold text-emerald-700">Rs {{ number_format((float) $request->refund_amount, 2) }}</p>
                                            <p class="text-xs text-slate-400">{{ $request->refund_percentage }}%</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $status->badgeClass() }}">
                                                {{ $status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
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
                                        <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                                            {{ $request->created_at?->format('d M Y') }}
                                            <span class="block text-xs text-slate-400">{{ $request->created_at?->format('H:i') }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('cro.refund-requests.show', $request) }}"
                                                class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                {{ $status === \App\Enums\RefundRequestStatusEnum::Pending ? 'Review' : 'View' }} →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($refundRequests->hasPages())
                        <div class="border-t border-slate-100 px-5 py-4">
                            {{ $refundRequests->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

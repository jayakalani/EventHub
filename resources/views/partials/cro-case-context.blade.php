@php
    /** @var array $caseContext */
    $attendee = $caseContext['attendee'] ?? ['name' => 'Unknown', 'email' => null, 'phone' => null];
    $event = $caseContext['event'] ?? null;
    $focusBooking = $caseContext['focusBooking'] ?? null;
    $priorTickets = $caseContext['priorTickets'] ?? [];
@endphp

<aside class="space-y-4">
    {{-- Attendee --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
            <h3 class="text-sm font-bold text-slate-900">Attendee</h3>
        </div>
        <div class="space-y-3 px-5 py-4 text-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</p>
                <p class="mt-0.5 font-semibold text-slate-900">{{ $attendee['name'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</p>
                @if ($attendee['email'])
                    <a href="mailto:{{ $attendee['email'] }}" class="mt-0.5 block break-all font-medium text-indigo-600 hover:text-indigo-700">
                        {{ $attendee['email'] }}
                    </a>
                @else
                    <p class="mt-0.5 text-slate-500">—</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</p>
                @if ($attendee['phone'])
                    <a href="tel:{{ $attendee['phone'] }}" class="mt-0.5 block font-medium text-indigo-600 hover:text-indigo-700">
                        {{ $attendee['phone'] }}
                    </a>
                @else
                    <p class="mt-0.5 text-slate-500">—</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Event --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
            <h3 class="text-sm font-bold text-slate-900">Event</h3>
        </div>
        @if ($event)
            <div class="space-y-3 px-5 py-4 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-semibold text-slate-900">{{ $event['name'] }}</p>
                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $event['statusClass'] }}">
                        {{ $event['statusLabel'] }}
                    </span>
                </div>
                <div class="grid gap-2 text-slate-600">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Date</span>
                        <span class="text-right font-medium text-slate-800">
                            {{ $event['date'] }}
                            @if ($event['time'])
                                · {{ $event['time'] }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Place</span>
                        <span class="text-right font-medium text-slate-800">{{ $event['place'] }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Schedule</span>
                        <span class="text-right font-medium text-slate-800">{{ $event['scheduleLabel'] }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Refunds</span>
                        <span class="text-right font-medium {{ $event['refundsAllowed'] ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $event['refundsAllowed'] ? 'Allowed' : 'Not allowed' }}
                        </span>
                    </div>
                </div>
                @if ($event['reason'])
                    <div class="rounded-2xl bg-amber-50 px-3 py-2.5 text-xs leading-relaxed text-amber-900">
                        <span class="font-semibold">Reason:</span> {{ $event['reason'] }}
                    </div>
                @endif
            </div>
        @else
            <div class="px-5 py-4 text-sm text-slate-500">No event linked to this case.</div>
        @endif
    </section>

    {{-- Focus booking / payment --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
            <h3 class="text-sm font-bold text-slate-900">Booking & payment</h3>
        </div>
        @if ($focusBooking)
            <div class="space-y-3 px-5 py-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-xs font-semibold text-slate-800">{{ $focusBooking['ticketNumber'] }}</span>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $focusBooking['statusClass'] }}">
                        {{ $focusBooking['statusLabel'] }}
                    </span>
                </div>
                <div class="grid gap-2 text-slate-600">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Category</span>
                        <span class="font-medium text-slate-800">{{ $focusBooking['category'] }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Price</span>
                        <span class="font-medium text-slate-800">Rs {{ $focusBooking['price'] }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Payment</span>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $focusBooking['paymentStatusClass'] }}">
                            {{ $focusBooking['paymentStatus'] }}
                        </span>
                    </div>
                    @if ($focusBooking['paymentMethod'])
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400">Method</span>
                            <span class="font-medium text-slate-800">{{ $focusBooking['paymentMethod'] }}</span>
                        </div>
                    @endif
                    @if ($focusBooking['paymentReference'])
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400">Ref</span>
                            <span class="font-mono text-xs font-medium text-slate-800">{{ $focusBooking['paymentReference'] }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-400">Purchased</span>
                        <span class="font-medium text-slate-800">{{ $focusBooking['purchasedAt'] ?? '—' }}</span>
                    </div>
                </div>
                @if ($focusBooking['checkedIn'] || $focusBooking['refundPending'])
                    <div class="flex flex-wrap gap-2">
                        @if ($focusBooking['checkedIn'])
                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-700">Checked in</span>
                        @endif
                        @if ($focusBooking['refundPending'])
                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Refund pending</span>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="px-5 py-4 text-sm text-slate-500">No booking found for this attendee{{ $event ? ' on this event' : '' }}.</div>
        @endif
    </section>

    {{-- Prior tickets --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
            <h3 class="text-sm font-bold text-slate-900">Prior tickets</h3>
            <p class="mt-0.5 text-xs text-slate-500">Recent bookings from this attendee</p>
        </div>
        @if (count($priorTickets))
            <ul class="divide-y divide-slate-100">
                @foreach ($priorTickets as $ticket)
                    <li class="px-5 py-3 {{ ! empty($ticket['isFocus']) ? 'bg-indigo-50/50' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $ticket['eventName'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $ticket['ticketNumber'] }}
                                    · {{ $ticket['eventDate'] }}
                                </p>
                            </div>
                            <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $ticket['statusClass'] }}">
                                {{ $ticket['statusLabel'] }}
                            </span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                            <span>Rs {{ $ticket['price'] }}</span>
                            <span class="inline-flex rounded-full px-1.5 py-0.5 font-semibold {{ $ticket['paymentStatusClass'] }}">
                                {{ $ticket['paymentStatus'] }}
                            </span>
                            @if (! empty($ticket['isFocus']))
                                <span class="font-semibold text-indigo-600">This case</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="px-5 py-4 text-sm text-slate-500">No prior tickets for this attendee.</div>
        @endif
    </section>
</aside>

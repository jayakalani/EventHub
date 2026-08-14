<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Booking Detail
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Attendee contact, ticket, payment, and refund status in one place.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('organizer.bookings.index', array_filter(['event_id' => $ticketBooking->event_id])) }}"
                    class="inline-flex items-center rounded-xl bg-slate-100 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    Back to Guest List
                </a>
                @if ($ticketBooking->event?->isOngoing())
                    <a href="{{ route('organizer.bookings.scan', ['event_id' => $ticketBooking->event_id]) }}"
                        class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        Scan Tickets
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

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

            {{-- Status strip --}}
            <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <span class="font-mono text-sm font-bold text-slate-900">{{ $ticketBooking->ticket_number }}</span>
                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $ticketBooking->displayStatusBadgeClasses() }}">
                    {{ $ticketBooking->displayStatusLabel() }}
                </span>
                @if ($ticketBooking->isCheckedIn())
                    <span class="inline-flex rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800">
                        Checked In
                    </span>
                @else
                    <span class="inline-flex rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                        Not Checked In
                    </span>
                @endif
                @if ($ticketBooking->refundRequest)
                    <span class="inline-flex rounded-lg bg-violet-100 px-2.5 py-1 text-xs font-semibold capitalize text-violet-800">
                        Refund: {{ str_replace('_', ' ', $ticketBooking->refundRequest->status->value) }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">

                    {{-- Attendee contact --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Attendee Contact</h3>
                        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Full Name</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $ticketBooking->user?->full_name ?? 'Unknown' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Email</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    @if ($ticketBooking->user?->email)
                                        <a href="mailto:{{ $ticketBooking->user->email }}"
                                            class="text-indigo-600 hover:text-indigo-800">
                                            {{ $ticketBooking->user->email }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Phone</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    @if ($ticketBooking->user?->contact_number)
                                        <a href="tel:{{ $ticketBooking->user->contact_number }}"
                                            class="text-indigo-600 hover:text-indigo-800">
                                            {{ $ticketBooking->user->contact_number }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">NIC</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $ticketBooking->user?->nic ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Date of Birth</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    @if ($ticketBooking->user?->date_of_birth)
                                        {{ \Carbon\Carbon::parse($ticketBooking->user->date_of_birth)->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Gender</dt>
                                <dd class="mt-1 text-sm capitalize text-slate-900">
                                    {{ $ticketBooking->user?->gender?->value ?? '—' }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-slate-500">Address</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $ticketBooking->user?->address ?? '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Ticket --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Ticket</h3>
                                <p class="mt-1 font-mono text-lg font-bold text-slate-900">
                                    {{ $ticketBooking->ticket_number }}
                                </p>
                            </div>
                            @if (! empty($qrSvg))
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-2">
                                    {!! $qrSvg !!}
                                </div>
                            @endif
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Event</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">
                                    @if ($ticketBooking->event)
                                        <a href="{{ route('organizer.events.show', $ticketBooking->event) }}"
                                            class="text-indigo-600 hover:text-indigo-800">
                                            {{ $ticketBooking->event->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </dd>
                                <dd class="text-sm text-slate-500">
                                    {{ $ticketBooking->event?->place ?? '—' }}
                                    @if ($ticketBooking->event?->date)
                                        · {{ \Carbon\Carbon::parse($ticketBooking->event->date)->format('M d, Y') }}
                                        @if ($ticketBooking->event->time)
                                            {{ \Carbon\Carbon::parse($ticketBooking->event->time)->format('H:i') }}
                                        @endif
                                    @endif
                                </dd>
                                @if ($ticketBooking->event?->host)
                                    <dd class="text-xs text-slate-500">Host: {{ $ticketBooking->event->host->name }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Ticket Type</dt>
                                <dd class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-900">
                                    @if ($ticketBooking->ticketCategory?->ticket_color)
                                        <span class="h-2.5 w-2.5 rounded-full"
                                            style="background-color: {{ $ticketBooking->ticketCategory->ticket_color }}"></span>
                                    @endif
                                    {{ $ticketBooking->ticketCategory?->name ?? 'General' }}
                                </dd>
                                @if ($ticketBooking->ticketCategory?->description)
                                    <dd class="mt-1 text-xs text-slate-500">{{ $ticketBooking->ticketCategory->description }}</dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Ticket Price</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">
                                    LKR {{ number_format((float) $ticketBooking->ticket_price, 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Purchased</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ $ticketBooking->created_at?->format('M d, Y H:i') ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">Booking Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $ticketBooking->displayStatusBadgeClasses() }}">
                                        {{ $ticketBooking->displayStatusLabel() }}
                                    </span>
                                </dd>
                            </div>
                            @if ($ticketBooking->postponement_kept_for)
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Kept After Postponement</dt>
                                    <dd class="mt-1 text-sm text-slate-900">
                                        {{ $ticketBooking->postponement_kept_for->format('M d, Y H:i') }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </section>

                    {{-- Payment --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Payment</h3>

                        @if ($ticketBooking->payment)
                            @php $payment = $ticketBooking->payment; @endphp
                            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Reference</dt>
                                    <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">
                                        {{ $payment->reference ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Status</dt>
                                    <dd class="mt-1 text-sm capitalize text-slate-900">
                                        {{ $payment->status?->value ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Method</dt>
                                    <dd class="mt-1 text-sm capitalize text-slate-900">
                                        @if ($payment->payment_method?->value == 'stripe')
                                            Card
                                        @else
                                            {{ $payment->payment_method?->value == 'wallet' ? 'Wallet' : '—' }}
                                        @endif
                                        
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Amount Charged</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ strtoupper($payment->currency ?? 'LKR') }}
                                        {{ number_format((float) $payment->amount, 2) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Purpose</dt>
                                    <dd class="mt-1 text-sm capitalize text-slate-900">
                                        {{ $payment->purpose ? str_replace('_', ' ', $payment->purpose) : '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Paid At</dt>
                                    <dd class="mt-1 text-sm text-slate-900">
                                        {{ $payment->created_at?->format('M d, Y H:i') ?? '—' }}
                                    </dd>
                                </div>
                                @if ($payment->stripe_payment_intent_id)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-slate-500">Card Payment Intent</dt>
                                        <dd class="mt-1 break-all font-mono text-xs text-slate-600">
                                            {{ $payment->stripe_payment_intent_id }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            @if ($relatedTickets->isNotEmpty())
                                <div class="mt-5 border-t border-slate-100 pt-4">
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Other Tickets In This Purchase
                                    </h4>
                                    <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-100">
                                        @foreach ($relatedTickets as $related)
                                            <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                                                <div>
                                                    <a href="{{ route('organizer.bookings.show', $related) }}"
                                                        class="font-mono text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                                        {{ $related->ticket_number }}
                                                    </a>
                                                    <p class="text-xs text-slate-500">
                                                        {{ $related->ticketCategory?->name ?? 'General' }}
                                                        · {{ $related->event?->name ?? '—' }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-slate-900">
                                                        LKR {{ number_format((float) $related->ticket_price, 2) }}
                                                    </p>
                                                    <span class="inline-flex rounded-lg px-2 py-0.5 text-[11px] font-semibold {{ $related->displayStatusBadgeClasses() }}">
                                                        {{ $related->displayStatusLabel() }}
                                                    </span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-slate-600">No payment record is linked to this ticket.</p>
                        @endif
                    </section>

                    {{-- Refund status --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Refund Status</h3>

                        @if ($ticketBooking->refundRequest)
                            @php $refund = $ticketBooking->refundRequest; @endphp
                            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Status</dt>
                                    <dd class="mt-1 text-sm font-semibold capitalize text-slate-900">
                                        {{ str_replace('_', ' ', $refund->status->value) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Refund Amount</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $refund->refund_percentage }}%
                                        · LKR {{ number_format((float) $refund->refund_amount, 2) }}
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-slate-500">Reason</dt>
                                    <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $refund->reason }}</dd>
                                </div>
                                @if ($refund->cro_notes)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-slate-500">CRO Notes</dt>
                                        <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $refund->cro_notes }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Submitted</dt>
                                    <dd class="mt-1 text-sm text-slate-700">
                                        {{ $refund->created_at?->format('M d, Y H:i') ?? '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Reviewed</dt>
                                    <dd class="mt-1 text-sm text-slate-700">
                                        {{ $refund->reviewed_at?->format('M d, Y H:i') ?? '—' }}
                                        @if ($refund->reviewer)
                                            · {{ $refund->reviewer->full_name }}
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            @if ($refund->isPending())
                                <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Pending CRO review. Check-in is blocked until this request is approved or declined.
                                </p>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-slate-600">
                                No refund request on this ticket.
                            </p>
                        @endif
                    </section>
                </div>

                {{-- Sidebar actions --}}
                <div class="space-y-4">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Check-in</h3>

                        @if ($ticketBooking->isCheckedIn())
                            <p class="mt-2 text-sm text-slate-600">
                                Checked in
                                {{ $ticketBooking->checked_in_at?->format('M d, Y H:i') }}
                                @if ($ticketBooking->checkedInBy)
                                    by {{ $ticketBooking->checkedInBy->full_name }}
                                @endif
                            </p>
                            @if ($ticketBooking->canUndoCheckIn())
                                <form action="{{ route('organizer.bookings.undo-check-in', $ticketBooking) }}" method="POST"
                                    class="mt-4">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Undo check-in for this guest?')"
                                        class="w-full rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm transition hover:bg-amber-200">
                                        Undo Check-in
                                    </button>
                                </form>
                            @else
                                <p class="mt-3 text-xs text-slate-500">
                                    Check-in can only be undone while the event is ongoing.
                                </p>
                            @endif
                        @elseif ($ticketBooking->canCheckIn())
                            <p class="mt-2 text-sm text-slate-600">
                                This ticket is valid for entry.
                            </p>
                            <form action="{{ route('organizer.bookings.check-in', $ticketBooking) }}" method="POST"
                                class="mt-4">
                                @csrf
                                <button type="submit"
                                    class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    Mark Attended
                                </button>
                            </form>
                        @else
                            <p class="mt-2 text-sm text-rose-600">
                                {{ $ticketBooking->checkInIneligibilityReason() ?? 'Not eligible for check-in.' }}
                            </p>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-sm font-semibold text-slate-900">Quick Links</h3>
                        <div class="mt-3 space-y-2">
                            @if ($ticketBooking->event)
                                <a href="{{ route('organizer.events.show', $ticketBooking->event) }}"
                                    class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                    View Event
                                </a>
                                <a href="{{ route('organizer.bookings.index', ['event_id' => $ticketBooking->event_id]) }}"
                                    class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                    Event Guest List
                                </a>
                            @endif
                            @if ($ticketBooking->user?->email)
                                <a href="{{ route('organizer.bookings.index', ['search' => $ticketBooking->user->email]) }}"
                                    class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                    All Tickets For Guest
                                </a>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    My Tickets
                </h2>

                <p class="mt-1 text-slate-500">
                    Manage, download and access all your purchased event tickets.
                </p>
            </div>

            <a href="{{ route('attendee.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">

                Browse Events

            </a>
        </div>
    </x-slot>

    <div class="py-8">

        <div class="max-w-7xl mx-auto px-6 space-y-8">

            {{-- Success Message --}}
            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Statistics --}}
            <div class="grid gap-5 md:grid-cols-3">

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Tickets
                    </p>

                    <h3 class="mt-2 text-4xl font-bold text-indigo-600">
                        {{ number_format($bookingCount) }}
                    </h3>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Active Events
                    </p>

                    <h3 class="mt-2 text-4xl font-bold text-emerald-600">
                        {{ $bookings->count() }}
                    </h3>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Ready To Use
                    </p>

                    <h3 class="mt-2 text-4xl font-bold text-amber-500">
                        {{ $bookingCount }}
                    </h3>
                </div>

            </div>

            {{-- Empty State --}}
            @if($bookings->isEmpty())

                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-16 text-center">

                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100">
                        🎟️
                    </div>

                    <h3 class="mt-6 text-2xl font-bold text-slate-800">
                        No Tickets Yet
                    </h3>

                    <p class="mt-2 text-slate-500">
                        Purchase tickets from upcoming events to see them here.
                    </p>

                    <a href="{{ route('attendee.dashboard') }}"
                        class="mt-6 inline-flex rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">

                        Explore Events

                    </a>

                </div>

            @else

                @foreach($bookings as $eventId => $eventBookings)

                    @php
                        $event = $eventBookings->first()->event;
                    @endphp

                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                        {{-- Event Header --}}
                        <div class="relative">

                            @if(!empty($event->cover))

                                <img
                                    src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    alt="{{ $event->name }}"
                                    class="h-52 w-full object-cover">

                            @else

                                <div class="h-52 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500"></div>

                            @endif

                            <div class="absolute inset-0 bg-black/50"></div>

                            <div class="absolute bottom-0 left-0 right-0 p-8">

                                <h3 class="text-3xl font-bold text-white">
                                    {{ $event->name }}
                                </h3>

                                <p class="mt-2 text-white/90">

                                    {{ $event->date }}

                                    @if($event->time)
                                        • {{ $event->time }}
                                    @endif

                                    • {{ $event->place }}

                                </p>

                                @if($event->host)
                                    <p class="mt-1 text-sm text-white/80">
                                        Hosted by {{ $event->host->name }}
                                    </p>
                                @endif

                            </div>

                        </div>

                        {{-- Tickets Grid --}}
                        <div class="p-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">

                            @foreach($eventBookings as $booking)

                                <div
                                    class="group rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-5 shadow-sm hover:shadow-xl transition duration-300">

                                    {{-- Top --}}
                                    <div class="flex items-start justify-between">

                                        <div>

                                            <div class="flex items-center gap-2">

                                                <span
                                                    class="h-3 w-3 rounded-full"
                                                    style="background-color: {{ $booking->ticketCategory->ticket_color }}">
                                                </span>

                                                <span class="font-bold text-slate-900">
                                                    {{ $booking->ticketCategory->name }}
                                                </span>

                                            </div>

                                            <p class="mt-2 text-xs text-slate-500 uppercase">
                                                Ticket Number
                                            </p>

                                            <p class="font-mono text-sm font-bold text-indigo-700">
                                                {{ $booking->ticket_number }}
                                            </p>

                                        </div>

                                        <span
                                            class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">

                                            {{ ucfirst(str_replace('_', ' ', $booking->status->value)) }}

                                        </span>

                                    </div>

                                    {{-- QR --}}
                                    <div
                                        class="mt-5 rounded-2xl border border-slate-100 bg-white p-4 flex justify-center">

                                        {!! $booking->qr_code_svg !!}

                                    </div>

                                    {{-- Details --}}
                                    <div class="mt-5 space-y-3">

                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Payment ID</span>
                                            <span class="font-mono text-slate-700">
                                                {{ $booking->payment->reference }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Price</span>
                                            <span class="font-semibold text-indigo-600">
                                                Rs {{ number_format($booking->ticket_price, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Purchased</span>
                                            <span class="font-medium text-slate-700">
                                                {{ $booking->created_at->format('d M Y') }}
                                            </span>
                                        </div>

                                    </div>

                                    {{-- Actions --}}
                                    <div class="mt-6 space-y-3">
                                        <a href="{{ route('attendee.bookings.download', $booking) }}"
                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">

                                            Download Ticket PDF

                                        </a>

                                        @if($booking->isCancellable())
                                            <a href="{{ route('attendee.bookings.refund.create', $booking) }}"
                                                class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 transition">

                                                Cancel Booking

                                            </a>
                                        @elseif($booking->refundRequest)
                                            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-xs font-medium text-slate-600">
                                                Refund request: {{ ucfirst(str_replace('_', ' ', $booking->refundRequest->status->value)) }}
                                            </div>
                                        @elseif($booking->isExpired())
                                            <div class="rounded-2xl bg-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-600">
                                                Ticket Expired
                                            </div>
                                        @endif
                                    </div>

                                    @if($booking->refundRequest?->status === \App\Enums\RefundRequestStatusEnum::Declined && $booking->refundRequest->cro_notes)
                                        <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">
                                                Refund Declined
                                            </p>
                                            <p class="mt-2 text-sm leading-relaxed text-red-800">
                                                {{ $booking->refundRequest->cro_notes }}
                                            </p>
                                        </div>
                                    @endif

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            @endif

        </div>

    </div>

</x-app-layout>


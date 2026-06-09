<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">My Tickets</h2>
                <p class="text-slate-500 mt-1">Confirmed ticket bookings grouped by event.</p>
            </div>
            <a href="{{ route('attendee.cart.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Go To Cart 🛒
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-6 space-y-6">

            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
                <p class="text-sm text-slate-500">Total confirmed tickets</p>
                <p class="text-3xl font-bold text-emerald-600">{{ number_format($bookingCount) }}</p>
            </div>

            @if ($bookings->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-slate-500">No confirmed tickets yet. Reserve tickets and pay from your cart.</p>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="mt-4 inline-flex rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Browse Events
                    </a>
                </div>
            @else
                @foreach ($bookings as $eventId => $eventBookings)
                    @php $event = $eventBookings->first()->event; @endphp
                    <div class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                            <h3 class="text-lg font-bold text-slate-900">{{ $event->name }}</h3>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ $event->date }} · {{ $event->place }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="px-6 py-3">Reference</th>
                                        <th class="px-6 py-3">Category</th>
                                        <th class="px-6 py-3">Qty</th>
                                        <th class="px-6 py-3">Unit Price</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($eventBookings as $booking)
                                        <tr>
                                            <td class="px-6 py-4 font-mono text-sm text-slate-600">{{ $booking->reference }}</td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-3 w-3 rounded-full"
                                                        style="background-color: {{ $booking->ticketCategory->ticket_color }}"></span>
                                                    <span class="font-medium text-slate-900">{{ $booking->ticketCategory->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-semibold">{{ $booking->quantity }}</td>
                                            <td class="px-6 py-4">Rs {{ number_format($booking->unit_price, 2) }}</td>
                                            <td class="px-6 py-4 font-semibold text-indigo-600">Rs {{ number_format($booking->total_amount, 2) }}</td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    {{ ucfirst($booking->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>

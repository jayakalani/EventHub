<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Refund Requests
                </h2>
                <p class="mt-1 text-slate-500">
                    Review and process attendee cancellation requests.
                </p>
            </div>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900 transition">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-6 space-y-8">

            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Pending Requests</p>
                    <h3 class="mt-2 text-4xl font-bold text-amber-500">{{ $pendingRequests->count() }}</h3>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Processed (All Time)</p>
                    <h3 class="mt-2 text-4xl font-bold text-emerald-600">{{ $processedCount }}</h3>
                </div>
            </div>

            @if($pendingRequests->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-16 text-center">
                    <h3 class="text-2xl font-bold text-slate-800">No Pending Requests</h3>
                    <p class="mt-2 text-slate-500">All refund requests have been processed.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($pendingRequests as $request)
                        @php
                            $booking = $request->ticketBooking;
                            $event = $booking->event;
                        @endphp

                        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $event->name }}</h3>
                                    <p class="text-sm text-slate-500">
                                        Requested {{ $request->created_at->format('d M Y, H:i') }}
                                        by {{ $request->user->full_name }}
                                    </p>
                                </div>
                                <span class="inline-flex rounded-full bg-amber-100 px-4 py-1 text-sm font-semibold text-amber-700">
                                    Pending Review
                                </span>
                            </div>

                            <div class="p-6 grid gap-6 lg:grid-cols-2">
                                <div class="space-y-3 text-sm">
                                    <h4 class="font-bold text-slate-800">Ticket Details</h4>
                                    <div class="flex justify-between"><span class="text-slate-500">Ticket</span><span class="font-mono font-semibold">{{ $booking->ticket_number }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Category</span><span>{{ $booking->ticketCategory->name }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Price Paid</span><span>Rs {{ number_format((float) $booking->ticket_price, 2) }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Payment Ref</span><span class="font-mono">{{ $booking->payment->reference }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Event Date</span><span>{{ $event->date }}</span></div>
                                </div>

                                <div class="space-y-3 text-sm">
                                    <h4 class="font-bold text-slate-800">Refund Calculation</h4>
                                    <div class="flex justify-between"><span class="text-slate-500">Policy</span><span>{{ $request->refund_percentage }}% refund</span></div>
                                    <div class="flex justify-between text-base"><span class="font-semibold text-slate-700">Refund Amount</span><span class="font-bold text-emerald-600">Rs {{ number_format((float) $request->refund_amount, 2) }}</span></div>

                                    <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Attendee Reason</p>
                                        <p class="mt-2 text-slate-700 leading-relaxed">{{ $request->reason }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 bg-slate-50 px-6 py-5 space-y-4">
                                <form action="{{ route('cro.refund-requests.approve', $request) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="text" name="cro_notes" placeholder="Optional notes for the attendee..." class="w-full rounded-xl border-slate-300 text-sm">
                                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                                        Approve Refund
                                    </button>
                                </form>

                                <form action="{{ route('cro.refund-requests.decline', $request) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <label for="decline-reason-{{ $request->id }}" class="block text-sm font-semibold text-slate-700">
                                        Decline reason <span class="text-red-600">*</span>
                                    </label>
                                    <textarea
                                        id="decline-reason-{{ $request->id }}"
                                        name="cro_notes"
                                        rows="3"
                                        required
                                        minlength="10"
                                        maxlength="1000"
                                        placeholder="Explain why this refund request is being declined. This will be sent to the attendee."
                                        class="w-full rounded-xl border-slate-300 text-sm"
                                    >{{ old('cro_notes') }}</textarea>
                                    <button type="submit" class="w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700">
                                        Decline Refund
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

</x-app-layout>

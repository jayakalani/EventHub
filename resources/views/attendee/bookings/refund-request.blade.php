<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">
                Refund Request
            </h2>
            <p class="mt-1 text-slate-500">
                Submit a cancellation request for your ticket.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-6 space-y-6">

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Ticket Details --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Ticket Details</h3>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Event</span>
                        <span class="font-semibold text-slate-800">{{ $ticketBooking->event->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Category</span>
                        <span class="font-semibold text-slate-800">{{ $ticketBooking->ticketCategory->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Ticket Number</span>
                        <span class="font-mono font-semibold text-indigo-700">{{ $ticketBooking->ticket_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Price Paid</span>
                        <span class="font-semibold text-indigo-600">Rs {{ number_format((float) $ticketBooking->ticket_price, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Event Date</span>
                        <span class="font-medium text-slate-700">{{ $ticketBooking->event->date }}</span>
                    </div>
                </div>
            </div>

            {{-- Refund Policy --}}
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
                <h3 class="text-lg font-bold text-amber-900">Refund Policy</h3>
                <ul class="mt-3 space-y-2 text-sm text-amber-800">
                    <li>More than 7 days before booking closes → <strong>100% refund</strong></li>
                    <li>Within 7 days of booking closing → <strong>75% refund</strong></li>
                    <li>After the event date → <strong>automatically declined</strong></li>
                </ul>

                <div class="mt-4 rounded-2xl bg-white/70 px-4 py-3 text-sm">
                    <p class="font-semibold text-slate-800">{{ $policy->policyLabel }}</p>
                    @if($policy->refundPercentage > 0)
                        <p class="mt-1 text-slate-600">
                            Estimated refund:
                            <strong>{{ $policy->refundPercentage }}%</strong>
                            (Rs {{ number_format($policy->refundAmount, 2) }})
                        </p>
                    @endif
                </div>
            </div>

            <form
                action="{{ route('attendee.bookings.refund.store', $ticketBooking) }}"
                method="POST"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-5"
            >
                @csrf

                <div>
                    <label for="reason" class="block text-sm font-semibold text-slate-700">
                        Reason for cancellation
                    </label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="5"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="Please explain why you would like to cancel this booking..."
                        class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Minimum 10 characters.</p>
                </div>

                @unless($policy->requiresCroReview)
                    <p class="text-sm text-red-700 bg-red-50 rounded-xl px-4 py-3">
                        This request will be automatically declined because the event has already passed.
                    </p>
                @endunless

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white hover:bg-red-700 transition"
                    >
                        Submit Refund Request
                    </button>

                    <a
                        href="{{ route('attendee.bookings.index') }}"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                    >
                        Cancel
                    </a>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>

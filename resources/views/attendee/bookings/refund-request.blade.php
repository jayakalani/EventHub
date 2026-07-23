<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">
                {{ t(['en' => 'Refund Request', 'si' => 'ආපසු ගෙවීමේ ඉල්ලීම']) }}
            </h2>
            <p class="mt-1 text-slate-500">
                {{ t(['en' => 'Submit a cancellation request for your ticket.', 'si' => 'ඔබේ ටිකට් අවලංගු කිරීමේ ඉල්ලීමක් ඉදිරිපත් කරන්න.']) }}
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
                <h3 class="text-lg font-bold text-slate-900">{{ t(['en' => 'Ticket Details', 'si' => 'ටිකට් විස්තර']) }}</h3>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ t(['en' => 'Event', 'si' => 'ප්‍රසංගය']) }}</span>
                        <span class="font-semibold text-slate-800">{{ $ticketBooking->event->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ t(['en' => 'Category', 'si' => 'වර්ගය']) }}</span>
                        <span class="font-semibold text-slate-800">{{ $ticketBooking->ticketCategory->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ t(['en' => 'Ticket Number', 'si' => 'ටිකට් අංකය']) }}</span>
                        <span class="font-mono font-semibold text-indigo-700">{{ $ticketBooking->ticket_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ t(['en' => 'Price Paid', 'si' => 'ගෙවූ මිල']) }}</span>
                        <span class="font-semibold text-indigo-600">Rs {{ number_format((float) $ticketBooking->ticket_price, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ t(['en' => 'Event Date', 'si' => 'ප්‍රසංග දිනය']) }}</span>
                        <span class="font-medium text-slate-700">{{ $ticketBooking->event->date }}</span>
                    </div>
                </div>
            </div>

            {{-- Refund Policy --}}
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
                <h3 class="text-lg font-bold text-amber-900">{{ t(['en' => 'Refund Policy', 'si' => 'ආපසු ගෙවීමේ ප්‍රතිපත්තිය']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm text-amber-800">
                    <li>{{ t(['en' => 'More than 7 days before booking closes →', 'si' => 'වෙන්කිරීම අවසානයට දින 7කට වඩා පෙර →']) }} <strong>{{ t(['en' => '100% refund', 'si' => '100% ආපසු ගෙවීම']) }}</strong></li>
                    <li>{{ t(['en' => 'Within 7 days of booking closing →', 'si' => 'වෙන්කිරීම අවසානයට දින 7ක් ඇතුළත →']) }} <strong>{{ t(['en' => '75% refund', 'si' => '75% ආපසු ගෙවීම']) }}</strong></li>
                    <li>{{ t(['en' => 'After the event date →', 'si' => 'ප්‍රසංග දිනයෙන් පසු →']) }} <strong>{{ t(['en' => 'automatically declined', 'si' => 'ස්වයංක්‍රීයව ප්‍රතික්ෂේප වේ']) }}</strong></li>
                </ul>

                <div class="mt-4 rounded-2xl bg-white/70 px-4 py-3 text-sm">
                    <p class="font-semibold text-slate-800">{{ $policy->policyLabel }}</p>
                    @if($policy->refundPercentage > 0)
                        <p class="mt-1 text-slate-600">
                            {{ t(['en' => 'Estimated refund:', 'si' => 'ඇස්තමේන්තුගත ආපසු ගෙවීම:']) }}
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
                        {{ t(['en' => 'Reason for cancellation', 'si' => 'අවලංගු කිරීමේ හේතුව']) }}
                    </label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="5"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="{{ t(['en' => 'Please explain why you would like to cancel this booking...', 'si' => 'මෙම වෙන්කිරීම අවලංගු කිරීමට අවශ්‍ය වන්නේ ඇයිදැයි පැහැදිලි කරන්න...']) }}"
                        class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">{{ t(['en' => 'Minimum 10 characters.', 'si' => 'අවම අක්ෂර 10ක්.']) }}</p>
                </div>

                @unless($policy->requiresCroReview)
                    <p class="text-sm text-red-700 bg-red-50 rounded-xl px-4 py-3">
                        {{ t(['en' => 'This request will be automatically declined because the event has already passed.', 'si' => 'ප්‍රසංගය දැනටමත් අවසන් වී ඇති බැවින් මෙම ඉල්ලීම ස්වයංක්‍රීයව ප්‍රතික්ෂේප වේ.']) }}
                    </p>
                @endunless

                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white hover:bg-red-700 transition"
                    >
                        {{ t(['en' => 'Submit Refund Request', 'si' => 'ආපසු ගෙවීමේ ඉල්ලීම ඉදිරිපත් කරන්න']) }}
                    </button>

                    <a
                        href="{{ route('attendee.bookings.index') }}"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                    >
                        {{ t(['en' => 'Cancel', 'si' => 'අවලංගු කරන්න']) }}
                    </a>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>

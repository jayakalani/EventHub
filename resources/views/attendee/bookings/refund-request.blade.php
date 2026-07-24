<x-app-layout>

    <x-slot name="header">
        <div class="min-w-0 flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">
            <h2 class="text-lg font-bold leading-tight text-slate-900 sm:text-xl shrink-0">
                {{ t(['en' => 'Refund Request', 'si' => 'ආපසු ගෙවීමේ ඉල්ලීම']) }}
            </h2>
            <p class="text-xs text-slate-500 sm:text-sm">
                {{ t(['en' => 'Submit a cancellation request for your ticket.', 'si' => 'ඔබේ ටිකට් අවලංගු කිරීමේ ඉල්ලීමක් ඉදිරිපත් කරන්න.']) }}
            </p>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-4">

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Ticket Details --}}
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5">
                <h3 class="text-base font-bold text-slate-900">{{ t(['en' => 'Ticket Details', 'si' => 'ටිකට් විස්තර']) }}</h3>

                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ t(['en' => 'Event', 'si' => 'ප්‍රසංගය']) }}</span>
                        <span class="text-right font-semibold text-slate-800">{{ $ticketBooking->event->name }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ t(['en' => 'Category', 'si' => 'වර්ගය']) }}</span>
                        <span class="text-right font-semibold text-slate-800">{{ $ticketBooking->ticketCategory->name }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ t(['en' => 'Ticket Number', 'si' => 'ටිකට් අංකය']) }}</span>
                        <span class="font-mono font-semibold text-[#0F0363]">{{ $ticketBooking->ticket_number }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ t(['en' => 'Price Paid', 'si' => 'ගෙවූ මිල']) }}</span>
                        <span class="font-semibold text-[#0F0363]">Rs {{ number_format((float) $ticketBooking->ticket_price, 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">{{ t(['en' => 'Event Date', 'si' => 'ප්‍රසංග දිනය']) }}</span>
                        <span class="font-medium text-slate-700">{{ $ticketBooking->event->date }}</span>
                    </div>
                </div>
            </div>

            {{-- Refund Policy --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 sm:px-5">
                <h3 class="text-base font-bold text-amber-900">{{ t(['en' => 'Refund Policy', 'si' => 'ආපසු ගෙවීමේ ප්‍රතිපත්තිය']) }}</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-amber-800">
                    <li>{{ t(['en' => 'More than 7 days before booking closes →', 'si' => 'වෙන්කිරීම අවසානයට දින 7කට වඩා පෙර →']) }} <strong>{{ t(['en' => '100% refund', 'si' => '100% ආපසු ගෙවීම']) }}</strong></li>
                    <li>{{ t(['en' => 'Within 7 days of booking closing →', 'si' => 'වෙන්කිරීම අවසානයට දින 7ක් ඇතුළත →']) }} <strong>{{ t(['en' => '75% refund', 'si' => '75% ආපසු ගෙවීම']) }}</strong></li>
                    <li>{{ t(['en' => 'After the event date →', 'si' => 'ප්‍රසංග දිනයෙන් පසු →']) }} <strong>{{ t(['en' => 'automatically declined', 'si' => 'ස්වයංක්‍රීයව ප්‍රතික්ෂේප වේ']) }}</strong></li>
                </ul>

                <div class="mt-3 rounded-xl bg-white/70 px-3.5 py-2.5 text-sm">
                    <p class="font-semibold text-slate-800">{{ $policy->policyLabel }}</p>
                    @if($policy->refundPercentage > 0)
                        <p class="mt-0.5 text-slate-600">
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
                class="space-y-4 rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5"
            >
                @csrf

                <div>
                    <label for="reason" class="block text-sm font-semibold text-slate-700">
                        {{ t(['en' => 'Reason for cancellation', 'si' => 'අවලංගු කිරීමේ හේතුව']) }}
                    </label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="3"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="{{ t(['en' => 'Please explain why you would like to cancel this booking...', 'si' => 'මෙම වෙන්කිරීම අවලංගු කිරීමට අවශ්‍ය වන්නේ ඇයිදැයි පැහැදිලි කරන්න...']) }}"
                        class="mt-1.5 w-full rounded-xl border-slate-300 shadow-sm focus:border-[#0F0363] focus:ring-[#0F0363]"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">{{ t(['en' => 'Minimum 10 characters.', 'si' => 'අවම අක්ෂර 10ක්.']) }}</p>
                </div>

                @unless($policy->requiresCroReview)
                    <p class="rounded-xl bg-red-50 px-3.5 py-2.5 text-sm text-red-700">
                        {{ t(['en' => 'This request will be automatically declined because the event has already passed.', 'si' => 'ප්‍රසංගය දැනටමත් අවසන් වී ඇති බැවින් මෙම ඉල්ලීම ස්වයංක්‍රීයව ප්‍රතික්ෂේප වේ.']) }}
                    </p>
                @endunless

                <div class="flex flex-col gap-2.5 sm:flex-row">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                    >
                        {{ t(['en' => 'Submit Refund Request', 'si' => 'ආපසු ගෙවීමේ ඉල්ලීම ඉදිරිපත් කරන්න']) }}
                    </button>

                    <a
                        href="{{ route('attendee.bookings.index') }}"
                        class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        {{ t(['en' => 'Cancel', 'si' => 'අවලංගු කරන්න']) }}
                    </a>
                </div>
            </form>

        </div>
    </div>

</x-app-layout>

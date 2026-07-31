@php
    $policyLocked = isset($event) && $event->hasSoldTickets();
    $refundsAllowed = filter_var(
        old('refunds_allowed', isset($event) ? (int) $event->refunds_allowed : 1),
        FILTER_VALIDATE_BOOLEAN
    );
    $fullDays = old('refund_full_days_before_close', isset($event) ? $event->refund_full_days_before_close : 7);
    $fullPercentage = old('refund_full_percentage', isset($event) ? $event->refund_full_percentage : 100);
    $partialPercentage = old('refund_partial_percentage', isset($event) ? $event->refund_partial_percentage : 75);
@endphp

<section
    class="space-y-3 border-t border-gray-100 pt-5"
    @unless ($policyLocked)
        x-data="{ refundsAllowed: {{ $refundsAllowed ? 'true' : 'false' }} }"
    @endunless
>
    <div>
        <h4 class="text-sm font-semibold text-gray-900">Refund Policy</h4>
        <p class="mt-0.5 text-xs text-gray-500">
            @if ($policyLocked)
                This policy is locked because tickets have already been sold for this event.
            @else
                Set whether attendees can request refunds for this event, and at what rates.
            @endif
        </p>
    </div>

    @if ($policyLocked)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Refund policy can only be edited before the first ticket is sold. It is now locked permanently to protect attendees.
        </div>

        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 space-y-3">
            <div class="flex items-center justify-between gap-3 text-sm">
                <span class="text-gray-500">Allow ticket refunds</span>
                <span class="font-semibold text-gray-900">
                    {{ $refundsAllowed ? 'Yes' : 'No' }}
                </span>
            </div>

            @if ($refundsAllowed)
                <div class="grid grid-cols-1 gap-3 border-t border-gray-100 pt-3 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Full refund window</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $fullDays }} days</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Full refund</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $fullPercentage }}%</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Partial refund</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $partialPercentage }}%</p>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="refunds_allowed" value="0">
                <input
                    type="checkbox"
                    name="refunds_allowed"
                    value="1"
                    x-model="refundsAllowed"
                    class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                >
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Allow ticket refunds</span>
                    <span class="mt-0.5 block text-xs text-gray-500">
                        When disabled, attendees cannot cancel bookings or request refunds for this event.
                        Once tickets are sold, this policy cannot be changed.
                    </span>
                </span>
            </label>
            @error('refunds_allowed')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div
            class="grid grid-cols-1 gap-3 md:grid-cols-3"
            x-show="refundsAllowed"
            x-cloak
        >
            <div
                class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                <label for="refund_full_days_before_close"
                    class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Full refund window (days)
                </label>
                <input
                    id="refund_full_days_before_close"
                    type="number"
                    name="refund_full_days_before_close"
                    min="0"
                    max="365"
                    value="{{ $fullDays }}"
                    x-bind:required="refundsAllowed"
                    class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <p class="mt-1 text-xs text-gray-500">Days before booking closes for the full rate.</p>
                @error('refund_full_days_before_close')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                <label for="refund_full_percentage"
                    class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Full refund %
                </label>
                <input
                    id="refund_full_percentage"
                    type="number"
                    name="refund_full_percentage"
                    min="0"
                    max="100"
                    value="{{ $fullPercentage }}"
                    x-bind:required="refundsAllowed"
                    class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('refund_full_percentage')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                <label for="refund_partial_percentage"
                    class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Partial refund %
                </label>
                <input
                    id="refund_partial_percentage"
                    type="number"
                    name="refund_partial_percentage"
                    min="0"
                    max="100"
                    value="{{ $partialPercentage }}"
                    x-bind:required="refundsAllowed"
                    class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <p class="mt-1 text-xs text-gray-500">Applied inside the full-refund window.</p>
                @error('refund_partial_percentage')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <p class="text-xs text-gray-500" x-show="refundsAllowed" x-cloak>
            On or after the event date, refund requests are always declined. Organizer event cancellations still issue a full wallet credit.
        </p>
    @endif
</section>

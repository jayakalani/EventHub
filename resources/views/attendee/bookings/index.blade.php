<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="min-w-0 flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">
                <h2 class="text-lg font-bold leading-tight text-slate-900 sm:text-xl shrink-0">
                    {{ t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']) }}
                </h2>
                <p class="text-xs text-slate-500 sm:text-sm">
                    {{ t(['en' => 'Manage, download and access all your purchased event tickets.', 'si' => 'ඔබ මිලදී ගත් සියලු ප්‍රසංග ටිකට් කළමනාකරණය කරන්න, බාගත කරන්න සහ ප්‍රවේශ වන්න.']) }}
                </p>
            </div>

            <a href="{{ route('attendee.dashboard') }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-dark transition sm:text-sm">
                {{ t(['en' => 'Browse Events', 'si' => 'ප්‍රසංග සොයන්න']) }}
            </a>
        </div>
    </x-slot>

    <div class="py-5">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-4">

            {{-- Success Message --}}
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if((request()->boolean('from_postponement') || session('from_postponement')) && ($hasUnresolvedPostponement ?? false))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 shadow-sm">
                    <p class="text-sm font-semibold text-amber-950">
                        {{ t(['en' => '⚠ Your postponed event has a new date', 'si' => '⚠ කල් දැමූ ඔබේ ප්‍රසංගයට නව දිනයක් ඇත']) }}
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-800">
                        {{ t(['en' => 'If you can attend on the new date, keep your ticket. If you cannot attend, request a full refund on the ticket below.', 'si' => 'නව දිනයේ සහභාගී විය හැකි නම් ඔබේ ටිකට් තබා ගන්න. නොහැකි නම් පහත ටිකට් සඳහා සම්පූර්ණ ආපසු ගෙවීමක් ඉල්ලන්න.']) }}
                    </p>
                    <p class="mt-2 text-xs text-amber-700">
                        {{ t(['en' => 'Use Keep My Ticket or Request Full Refund on each postponed ticket.', 'si' => 'කල් දැමූ සෑම ටිකට්ටුවක් සඳහාම Keep My Ticket හෝ Request Full Refund භාවිතා කරන්න.']) }}
                    </p>
                </div>
            @endif

            {{-- Statistics --}}
            <div class="grid gap-3 sm:grid-cols-3">

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">
                        {{ t(['en' => 'Total Tickets', 'si' => 'මුළු ටිකට්']) }}
                    </p>
                    <h3 class="mt-0.5 text-2xl font-bold text-primary">
                        {{ number_format($bookingCount) }}
                    </h3>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">
                        {{ t(['en' => 'Active Events', 'si' => 'සක්‍රීය ප්‍රසංග']) }}
                    </p>
                    <h3 class="mt-0.5 text-2xl font-bold text-emerald-600">
                        {{ $bookings->count() }}
                    </h3>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">
                        {{ t(['en' => 'Ready To Use', 'si' => 'භාවිතයට සූදානම්']) }}
                    </p>
                    <h3 class="mt-0.5 text-2xl font-bold text-amber-500">
                        {{ $bookingCount }}
                    </h3>
                </div>

            </div>

            {{-- Empty State --}}
            @if($bookings->isEmpty())

                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
                        🎟️
                    </div>

                    <h3 class="mt-4 text-xl font-bold text-slate-800">
                        {{ t(['en' => 'No Tickets Yet', 'si' => 'තවම ටිකට් නැත']) }}
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ t(['en' => 'Purchase tickets from upcoming events to see them here.', 'si' => 'ඉදිරි ප්‍රසංගවලින් ටිකට් මිලදී ගෙන මෙහි බලන්න.']) }}
                    </p>

                    <a href="{{ route('attendee.dashboard') }}"
                        class="mt-4 inline-flex rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">
                        {{ t(['en' => 'Explore Events', 'si' => 'ප්‍රසංග ගවේෂණය කරන්න']) }}
                    </a>

                </div>

            @else

                @foreach($bookings as $eventId => $eventBookings)

                    @php
                        $event = $eventBookings->first()->event;
                    @endphp

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        {{-- Event Header --}}
                        <div class="relative h-36 overflow-hidden sm:h-40">

                            @if(!empty($event->cover))
                                <img
                                    src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    alt=""
                                    aria-hidden="true"
                                    class="absolute inset-0 h-full w-full scale-110 object-cover blur-2xl brightness-90">
                            @else
                                <div
                                    class="absolute inset-0"
                                    style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);"></div>
                            @endif

                            <div class="absolute inset-0 z-[1] bg-black/40"></div>

                            <div class="relative z-[2] flex h-full">
                                @if(!empty($event->cover))
                                    <div class="flex h-full w-2/5 shrink-0 items-center justify-center sm:w-1/3">
                                        <img
                                            src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                            alt="{{ $event->name }}"
                                            class="h-full w-full object-contain">
                                    </div>
                                @endif

                                <div class="flex min-w-0 flex-1 flex-col justify-center px-4 py-3 sm:px-5 sm:py-4">
                                    @if($event->isCancelled())
                                        <div class="mb-2 inline-flex w-fit rounded-lg border border-rose-300/40 bg-rose-500/90 px-2.5 py-1">
                                            <p class="text-xs font-bold uppercase tracking-wide text-white">{{ t(['en' => 'Event Cancelled', 'si' => 'ප්‍රසංගය අවලංගුයි']) }}</p>
                                        </div>
                                        @if($event->cancellation_reason)
                                            <p class="mb-2 max-w-3xl text-xs leading-relaxed text-rose-100 line-clamp-2">
                                                {{ $event->cancellation_reason }}
                                            </p>
                                        @endif
                                    @elseif($event->isPostponed() && $eventBookings->contains(fn ($b) => $b->wasPurchasedBeforeCurrentPostponement()))
                                        <div class="mb-2 inline-flex w-fit rounded-lg border border-amber-300/40 bg-amber-500/90 px-2.5 py-1">
                                            <p class="text-xs font-bold uppercase tracking-wide text-white">{{ t(['en' => '⚠ Event Postponed', 'si' => '⚠ ප්‍රසංගය කල් දමා ඇත']) }}</p>
                                        </div>
                                        <p class="mb-2 max-w-3xl text-xs leading-relaxed text-amber-50">
                                            {{ t(['en' => 'This event has been postponed.', 'si' => 'මෙම ප්‍රසංගය කල් දමා ඇත.']) }}
                                            @if($event->hasDateYetToBeScheduled())
                                                {{ t(['en' => 'The new date will be announced soon.', 'si' => 'නව දිනය ඉක්මනින් නිවේදනය කෙරේ.']) }}
                                            @else
                                                {{ t(['en' => 'New Date', 'si' => 'නව දිනය']) }}:
                                                {{ $event->formattedScheduleDate('d M Y') }}
                                            @endif
                                        </p>
                                    @elseif($event->isCompleted())
                                        <div class="mb-2 inline-flex w-fit rounded-lg border border-slate-300/40 bg-slate-600/90 px-2.5 py-1">
                                            <p class="text-xs font-bold uppercase tracking-wide text-white">{{ t(['en' => 'Event Completed', 'si' => 'ප්‍රසංගය අවසන්']) }}</p>
                                        </div>
                                    @endif

                                    <h3 class="truncate text-xl font-bold text-white sm:text-2xl">
                                        {{ $event->name }}
                                    </h3>

                                    <p class="mt-0.5 truncate text-sm text-white/90">
                                        @if($event->hasDateYetToBeScheduled() && $eventBookings->contains(fn ($b) => $b->wasPurchasedBeforeCurrentPostponement()))
                                            {{ t(['en' => 'Date Yet To Be Scheduled', 'si' => 'දිනය තවම නියම වී නැත']) }}
                                        @elseif($event->hasDateYetToBeScheduled())
                                            {{ t(['en' => 'Date & time not chosen yet', 'si' => 'දිනය සහ වේලාව තවම තෝරා නැත']) }}
                                        @else
                                            {{ $event->date }}

                                            @if($event->time)
                                                • {{ $event->time }}
                                            @endif
                                        @endif

                                        • {{ $event->place }}
                                    </p>

                                    @if($event->host)
                                        <p class="mt-0.5 truncate text-xs text-white/80">
                                            {{ t(['en' => 'Hosted by', 'si' => 'සත්කාරකයා']) }} {{ $event->host->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                        </div>

                        {{-- Tickets Grid --}}
                        <div class="p-4 grid gap-3 sm:p-5 sm:gap-4 md:grid-cols-2 xl:grid-cols-3">

                            @foreach($eventBookings as $booking)

                                <div
                                    class="group rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-4 shadow-sm hover:shadow-lg transition duration-300">

                                    {{-- Top --}}
                                    <div class="flex items-start justify-between gap-2">

                                        <div class="min-w-0">

                                            <div class="flex items-center gap-2">

                                                <span
                                                    class="h-2.5 w-2.5 rounded-full shrink-0"
                                                    style="background-color: {{ $booking->ticketCategory->ticket_color }}">
                                                </span>

                                                <span class="text-sm font-bold text-slate-900 truncate">
                                                    {{ $booking->ticketCategory->name }}
                                                </span>

                                            </div>

                                            <p class="mt-1.5 text-[10px] text-slate-500 uppercase tracking-wide">
                                                {{ t(['en' => 'Ticket Number', 'si' => 'ටිකට් අංකය']) }}
                                            </p>

                                            <p class="font-mono text-xs font-bold text-primary">
                                                {{ $booking->ticket_number }}
                                            </p>

                                        </div>

                                        <span
                                            @class([
                                                'rounded-full px-2.5 py-0.5 text-[10px] font-semibold shrink-0',
                                                $booking->displayStatusBadgeClasses(),
                                            ])>

                                            {{ $booking->displayStatusLabel() }}

                                        </span>

                                    </div>

                                    {{-- QR --}}
                                    <div
                                        class="mt-3 rounded-xl border border-slate-100 bg-white p-2.5 flex justify-center">

                                        {!! $booking->qr_code_svg !!}

                                    </div>

                                    {{-- Details --}}
                                    <div class="mt-3 space-y-1.5">

                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">{{ t(['en' => 'Payment ID', 'si' => 'ගෙවීම් අංකය']) }}</span>
                                            <span class="font-mono text-slate-700">
                                                {{ $booking->payment->reference }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">{{ t(['en' => 'Price', 'si' => 'මිල']) }}</span>
                                            <span class="font-semibold text-primary">
                                                Rs {{ number_format($booking->ticket_price, 2) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-500">{{ t(['en' => 'Purchased', 'si' => 'මිලදී ගත් දිනය']) }}</span>
                                            <span class="font-medium text-slate-700">
                                                {{ $booking->created_at->format('d M Y') }}
                                            </span>
                                        </div>

                                    </div>

                                    {{-- Actions --}}
                                    <div class="mt-3 space-y-2">
                                        <a href="{{ route('attendee.bookings.download', $booking) }}"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary-dark transition sm:text-sm">
                                            {{ t(['en' => 'Download Ticket PDF', 'si' => 'ටිකට් PDF බාගත කරන්න']) }}
                                        </a>

                                        @if($booking->status === \App\Enums\BookingStatusEnum::EventCancelled)
                                            <div class="rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-semibold text-rose-700">
                                                {{ t(['en' => 'Refunded to wallet due to event cancellation', 'si' => 'ප්‍රසංගය අවලංගු වීම නිසා පසුම්බියට ආපසු ගෙවන ලදී']) }}
                                            </div>
                                        @elseif($event->isCompleted())
                                            <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-xs font-semibold text-slate-700">
                                                {{ t(['en' => 'Event completed — ticket archived for your records', 'si' => 'ප්‍රසංගය අවසන් — ටිකට් ඔබේ වාර්තා සඳහා සුරකින ලදී']) }}
                                            </div>
                                        @elseif($booking->isPostponementRefundable())
                                            <div x-data="{ confirmRefund: false }" class="space-y-2">
                                                <template x-if="!confirmRefund">
                                                    <div class="space-y-2">
                                                        <form method="POST" action="{{ route('attendee.bookings.keep-postponement', $booking) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition sm:text-sm">
                                                                {{ t(['en' => 'Keep My Ticket', 'si' => 'මගේ ටිකට් තබා ගන්න']) }}
                                                            </button>
                                                        </form>
                                                        <button type="button"
                                                            @click="confirmRefund = true"
                                                            class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 transition sm:text-sm">
                                                            {{ t(['en' => 'Request Full Refund', 'si' => 'සම්පූර්ණ ආපසු ගෙවීම ඉල්ලන්න']) }}
                                                        </button>
                                                    </div>
                                                </template>
                                                <template x-if="confirmRefund">
                                                    <form method="POST"
                                                        action="{{ route('attendee.bookings.postponement-refund', $booking) }}"
                                                        class="space-y-2 rounded-xl border border-red-200 bg-red-50 p-3">
                                                        @csrf
                                                        <p class="text-xs leading-relaxed text-red-800">
                                                            {{ t(['en' => 'This refund cannot be reversed. The full amount will be credited to your wallet immediately.', 'si' => 'මෙම ආපසු ගෙවීම අහෝසි කළ නොහැක. සම්පූර්ණ මුදල වහාම ඔබේ පසුම්බියට බැර වේ.']) }}
                                                        </p>
                                                        <label class="flex items-start gap-2 text-xs text-red-800">
                                                            <input type="checkbox"
                                                                name="confirm_irreversible"
                                                                value="1"
                                                                required
                                                                class="mt-0.5 rounded border-red-300 text-red-600 focus:ring-red-500">
                                                            <span>{{ t(['en' => 'I understand this refund cannot be reversed.', 'si' => 'මෙම ආපසු ගෙවීම අහෝසි කළ නොහැකි බව මම තේරුම් ගනිමි.']) }}</span>
                                                        </label>
                                                        <div class="flex gap-2">
                                                            <button type="button"
                                                                @click="confirmRefund = false"
                                                                class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                                {{ t(['en' => 'Cancel', 'si' => 'අවලංගු කරන්න']) }}
                                                            </button>
                                                            <button type="submit"
                                                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                                {{ t(['en' => 'Confirm Refund', 'si' => 'ආපසු ගෙවීම තහවුරු කරන්න']) }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </template>
                                            </div>
                                        @elseif($booking->hasKeptCurrentPostponement())
                                            <div class="rounded-xl bg-emerald-50 px-3 py-2 text-center text-xs font-semibold text-emerald-800">
                                                {{ t(['en' => 'Your ticket remains valid for this postponed event.', 'si' => 'කල් දැමූ මෙම ප්‍රසංගය සඳහා ඔබේ ටිකට් වලංගුව පවතී.']) }}
                                            </div>
                                        @elseif($booking->isCancellable())
                                            <a href="{{ route('attendee.bookings.refund.create', $booking) }}"
                                                class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 transition sm:text-sm">
                                                {{ t(['en' => 'Cancel Booking', 'si' => 'වෙන්කිරීම අවලංගු කරන්න']) }}
                                            </a>
                                        @elseif($booking->refundRequest)
                                            <div class="rounded-xl bg-slate-100 px-3 py-2 text-center text-xs font-medium text-slate-600">
                                                {{ t(['en' => 'Refund request:', 'si' => 'ආපසු ගෙවීමේ ඉල්ලීම:']) }} {{ ucfirst(str_replace('_', ' ', $booking->refundRequest->status->value)) }}
                                            </div>
                                        @elseif($booking->isExpired())
                                            <div class="rounded-xl bg-slate-200 px-3 py-2 text-center text-xs font-semibold text-slate-600">
                                                {{ t(['en' => 'Ticket Expired', 'si' => 'ටිකට් කල් ඉකුත් වී ඇත']) }}
                                            </div>
                                        @endif
                                    </div>

                                    @if($booking->refundRequest?->status === \App\Enums\RefundRequestStatusEnum::Declined && $booking->refundRequest->cro_notes)
                                        <div class="mt-2 rounded-xl border border-red-100 bg-red-50 px-3 py-2">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-red-600">
                                                {{ t(['en' => 'Refund Declined', 'si' => 'ආපසු ගෙවීම ප්‍රතික්ෂේප විය']) }}
                                            </p>
                                            <p class="mt-1 text-xs leading-relaxed text-red-800">
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

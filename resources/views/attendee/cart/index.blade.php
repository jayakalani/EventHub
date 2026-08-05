<x-app-layout>
    @php
        $flatItems = $cartItems->flatten();
        $isEmpty = $flatItems->isEmpty();
        $expiredOnLoadCount = $flatItems->filter(fn ($item) => $item->hasPurchaseDeadlinePassed())->count();
        $reservationMinutes = (int) config('cart.reservation_minutes', 30);

        if ($reservationMinutes >= 1440 && $reservationMinutes % 1440 === 0) {
            $reservationDays = (int) ($reservationMinutes / 1440);
            $reservationDurationEn = $reservationDays === 1 ? '1 day' : "{$reservationDays} days";
            $reservationDurationSi = $reservationDays === 1 ? 'දින 1 ක්' : "දින {$reservationDays} ක්";
        } elseif ($reservationMinutes >= 60 && $reservationMinutes % 60 === 0) {
            $reservationHours = (int) ($reservationMinutes / 60);
            $reservationDurationEn = $reservationHours === 1 ? '1 hour' : "{$reservationHours} hours";
            $reservationDurationSi = $reservationHours === 1 ? 'පැය 1 ක්' : "පැය {$reservationHours} ක්";
        } else {
            $reservationDurationEn = $reservationMinutes === 1 ? '1 minute' : "{$reservationMinutes} minutes";
            $reservationDurationSi = $reservationMinutes === 1 ? 'මිනිත්තු 1 ක්' : "මිනිත්තු {$reservationMinutes} ක්";
        }
    @endphp

    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 py-5"
        @unless($isEmpty)
        x-data="{
            selected: {
                @foreach ($selectedCartItemIds as $selectedId)
                    {{ $selectedId }}: true,
                @endforeach
            },
            items: {
                @foreach ($flatItems as $item)
                    {{ $item->id }}: {
                        qty: {{ (int) $item->quantity }},
                        total: {{ (float) $item->line_total }},
                        deadline: @js($item->purchaseDeadlineAt()->toIso8601String()),
                        expired: {{ $item->hasPurchaseDeadlinePassed() ? 'true' : 'false' }},
                        inventoryHeld: {{ $item->inventory_held ? 'true' : 'false' }},
                        remainingMs: 0,
                        label: '',
                        urgency: 'ok',
                    },
                @endforeach
            },
            nowMs: Date.now(),
            walletBalance: {{ (float) $walletBalance }},
            reservationMs: {{ (int) $reservationMinutes }} * 60 * 1000,
            expiryTimer: null,
            init() {
                this.syncExpiry();
                this.expiryTimer = setInterval(() => this.syncExpiry(), 1000);
            },
            destroy() {
                if (this.expiryTimer) {
                    clearInterval(this.expiryTimer);
                }
            },
            syncExpiry() {
                this.nowMs = Date.now();
                let changed = false;

                Object.keys(this.items).forEach(id => {
                    const item = this.items[id];
                    const remaining = new Date(item.deadline).getTime() - this.nowMs;
                    const isExpired = remaining <= 0;

                    item.remainingMs = Math.max(0, remaining);
                    item.label = this.formatCountdown(item.remainingMs);
                    item.urgency = this.urgencyFor(item.remainingMs);

                    if (isExpired !== item.expired) {
                        item.expired = isExpired;
                        changed = true;
                    }

                    if (isExpired && this.selected[id]) {
                        this.selected[id] = false;
                        changed = true;
                    }
                });

                if (changed) {
                    this.persistSelection();
                }
            },
            urgencyFor(ms) {
                if (ms <= 0) return 'expired';

                // Short holds: warn/critical by % remaining. Longer holds: fixed windows.
                if (this.reservationMs <= 2 * 60 * 60 * 1000) {
                    if (ms <= this.reservationMs * 0.15) return 'critical';
                    if (ms <= this.reservationMs * 0.35) return 'warn';
                    return 'ok';
                }

                if (ms <= 6 * 60 * 60 * 1000) return 'critical';
                if (ms <= 24 * 60 * 60 * 1000) return 'warn';
                return 'ok';
            },
            formatCountdown(ms) {
                if (ms <= 0) {
                    return @js(t(['en' => 'Expired', 'si' => 'කල් ඉකුත්']));
                }

                const totalSec = Math.ceil(ms / 1000);
                const days = Math.floor(totalSec / 86400);
                const hours = Math.floor((totalSec % 86400) / 3600);
                const minutes = Math.floor((totalSec % 3600) / 60);
                const seconds = totalSec % 60;
                const pad = (n) => String(n).padStart(2, '0');

                if (days > 0) {
                    return days + 'd ' + hours + 'h ' + pad(minutes) + 'm';
                }

                if (hours > 0) {
                    return hours + 'h ' + pad(minutes) + 'm ' + pad(seconds) + 's';
                }

                return pad(minutes) + ':' + pad(seconds);
            },
            get purchasableIds() {
                return Object.keys(this.items).filter(id => !this.items[id].expired);
            },
            get expiredIds() {
                return Object.keys(this.items).filter(id => this.items[id].expired);
            },
            get expiredCount() {
                return this.expiredIds.length;
            },
            get soonestRemainingMs() {
                const active = this.purchasableIds.map(id => this.items[id].remainingMs);
                if (active.length === 0) return 0;
                return Math.min(...active);
            },
            get soonestLabel() {
                return this.formatCountdown(this.soonestRemainingMs);
            },
            get soonestUrgency() {
                return this.urgencyFor(this.soonestRemainingMs);
            },
            get selectedIds() {
                return this.purchasableIds.filter(id => this.selected[id]);
            },
            get selectedLines() {
                return this.selectedIds.length;
            },
            get selectedTickets() {
                return this.selectedIds.reduce((sum, id) => sum + (this.items[id]?.qty || 0), 0);
            },
            get selectedTotal() {
                return this.selectedIds.reduce((sum, id) => sum + (this.items[id]?.total || 0), 0);
            },
            get allSelected() {
                const ids = this.purchasableIds;
                return ids.length > 0 && ids.every(id => this.selected[id]);
            },
            toggleAll(checked) {
                this.purchasableIds.forEach(id => {
                    this.selected[id] = checked;
                });
                this.persistSelection();
            },
            formatMoney(amount) {
                return 'Rs ' + Number(amount).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
            async persistSelection() {
                try {
                    await fetch(@js(route('attendee.cart.selection')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            cart_item_ids: this.selectedIds.map(id => Number(id)),
                        }),
                    });
                } catch (e) {
                    // Keep UI responsive even if persistence fails.
                }
            },
            async goToWallet(event) {
                event.preventDefault();
                await this.persistSelection();
                window.location.href = @js(route('attendee.wallet.index'));
            },
        }"
        @endunless
    >

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-4">
        <div
            class="rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6"
            style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="min-w-0">
                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl">
                        {{ t(['en' => 'My Cart', 'si' => 'කාර්ට්']) }}
                    </h1>

                    <p class="mt-0.5 text-sm text-violet-100/90">
                        {{ t([
                            'en' => "Seats are reserved for {$reservationDurationEn}. Pay before the timer ends or they return to sale.",
                            'si' => "ආසන {$reservationDurationSi} වෙන්කර ඇත. ටයිමර් අවසන් වීමට පෙර ගෙවන්න, නැතිනම් ඒවා නැවත විකිණීමට යයි.",
                        ]) }}
                    </p>
                </div>

                @unless($isEmpty)
                    <div class="flex flex-wrap gap-2.5">
                        <div class="rounded-xl bg-white/15 backdrop-blur-md ring-1 ring-white/10 px-3.5 py-2">
                            <div class="text-[10px] uppercase tracking-wider text-violet-100/90">
                                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                            </div>
                            <div class="text-lg font-bold leading-tight">
                                {{ $cartItems->count() }}
                            </div>
                        </div>

                        <div class="rounded-xl bg-white/15 backdrop-blur-md ring-1 ring-white/10 px-3.5 py-2">
                            <div class="text-[10px] uppercase tracking-wider text-violet-100/90">
                                {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
                            </div>
                            <div class="text-lg font-bold leading-tight">
                                {{ $flatItems->sum('quantity') }}
                            </div>
                        </div>

                        <div
                            class="rounded-xl backdrop-blur-md ring-1 px-3.5 py-2"
                            :class="{
                                'bg-white/15 ring-white/10': soonestUrgency === 'ok',
                                'bg-amber-400/25 ring-amber-200/40': soonestUrgency === 'warn',
                                'bg-red-500/30 ring-red-200/50 animate-pulse': soonestUrgency === 'critical',
                                'bg-slate-500/30 ring-white/10': soonestUrgency === 'expired' || purchasableIds.length === 0,
                            }"
                        >
                            <div class="text-[10px] uppercase tracking-wider text-violet-100/90">
                                {{ t(['en' => 'Time left', 'si' => 'ඉතිරි කාලය']) }}
                            </div>
                            <div class="font-mono text-lg font-bold leading-tight tabular-nums" x-text="purchasableIds.length ? soonestLabel : '—'">
                                —
                            </div>
                        </div>
                    </div>
                @endunless

            </div>
        </div>
    </div>

    @if($isEmpty)
        {{-- Empty state --}}
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-1.3 6h11.6L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                </svg>
            </div>

            <h2 class="mt-5 text-xl font-bold text-slate-900 sm:text-2xl">
                {{ t(['en' => 'Your cart is empty', 'si' => 'ඔබේ කාර්ට් හිස්ය']) }}
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                {{ t([
                    'en' => "Reserve tickets from an event to hold seats here for {$reservationDurationEn} while you check out.",
                    'si' => "ප්‍රසංගයකින් ටිකට් වෙන්කර ගන්න — ගෙවීමට පෙර මෙහි ආසන {$reservationDurationSi} තබා ගනු ලැබේ.",
                ]) }}
            </p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('attendee.dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ t(['en' => 'Explore Events', 'si' => 'ප්‍රසංග ගවේෂණය කරන්න']) }}
                </a>
                <a href="{{ route('attendee.bookings.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']) }}
                </a>
            </div>
        </div>
    @else
        {{-- Expired cleanup banner --}}
        <div
            x-show="expiredCount > 0"
            x-cloak
            class="mb-4 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm"
            @if($expiredOnLoadCount === 0) style="display: none;" @endif
        >
            <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <i class="bi bi-hourglass-bottom" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-amber-950">
                            <span x-text="expiredCount"></span>
                            {{ t(['en' => 'reservation(s) expired', 'si' => 'වෙන්කිරීම(න්) කල් ඉකුත් වී ඇත']) }}
                        </p>
                        <p class="mt-0.5 text-xs text-amber-800/90">
                            {{ t(['en' => 'Expired holds can no longer be purchased. Clear them to free tickets for others.', 'si' => 'කල් ඉකුත් වෙන්කිරීම් මිලදී ගත නොහැක. අනෙක් අයට ටිකට් නිදහස් කිරීමට ඒවා ඉවත් කරන්න.']) }}
                        </p>
                    </div>
                </div>

                <form action="{{ route('attendee.cart.clear-expired') }}" method="POST" class="shrink-0">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 sm:w-auto"
                    >
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                        {{ t(['en' => 'Clear expired', 'si' => 'කල් ඉකුත් ඉවත් කරන්න']) }}
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">

            {{-- LEFT SIDE --}}
            <div class="xl:col-span-8 space-y-4">

                {{-- Select All --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <label class="flex items-center gap-2.5">
                        <input
                            type="checkbox"
                            :checked="allSelected"
                            :disabled="purchasableIds.length === 0"
                            @change="toggleAll($event.target.checked)"
                            class="h-4 w-4 rounded border-slate-300 text-primary disabled:cursor-not-allowed disabled:opacity-40"
                        >
                        <span class="text-sm font-semibold text-slate-700">
                            {{ t(['en' => 'Select All Tickets', 'si' => 'සියලු ටිකට් තෝරන්න']) }}
                        </span>
                    </label>

                    <div class="text-sm text-slate-500">
                        {{ t(['en' => 'Cart Value', 'si' => 'මුළු එකතුව']) }}
                        <span class="font-bold text-slate-900 ml-2">
                            Rs {{ number_format($cartTotal, 2) }}
                        </span>
                    </div>
                </div>

                {{-- EVENTS --}}
                @foreach ($cartItems as $eventId => $items)
                    @php
                        $event = $items->first()->event;
                    @endphp

                    <div class="group overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-lg transition duration-300">

                        <div class="relative border-b border-slate-100">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>

                            <div class="pl-6 pr-4 py-3.5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                                            <a href="{{ route('attendee.events.show', $event) }}" class="hover:text-primary transition">
                                                {{ $event->name }}
                                            </a>
                                        </h3>

                                        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-slate-500">
                                            <span>
                                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                                                @if ($event->hasDateYetToBeScheduled())
                                                    {{ t(['en' => 'Date & time not chosen yet', 'si' => 'දිනය සහ වේලාව තවම තෝරා නැත']) }}
                                                @else
                                                    {{ $event->formattedScheduleDate('Y-m-d') ?? $event->date }}
                                                @endif
                                            </span>
                                            <span>
                                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                                {{ $event->place }}
                                            </span>
                                            @if($event->host)
                                                <span>
                                                    <i class="bi bi-person" aria-hidden="true"></i>
                                                    {{ $event->host->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                                        {{ $items->count() }} {{ t(['en' => 'Item(s)', 'si' => 'අයිතම']) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach($items as $item)
                                @php
                                    $isExpiredOnLoad = $item->hasPurchaseDeadlinePassed();
                                    $maxQty = $item->inventory_held
                                        ? ((int) $item->ticketCategory->no_of_available_tickets + (int) $item->quantity)
                                        : max((int) $item->ticketCategory->no_of_available_tickets, (int) $item->quantity);
                                @endphp

                                <div
                                    class="px-4 py-3.5 transition"
                                    :class="items[{{ $item->id }}].expired ? 'bg-slate-50/80' : 'hover:bg-slate-50'"
                                >
                                    <div
                                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4"
                                        :class="items[{{ $item->id }}].expired && 'opacity-70'"
                                    >
                                        <input
                                            type="checkbox"
                                            value="{{ $item->id }}"
                                            x-model="selected[{{ $item->id }}]"
                                            :disabled="items[{{ $item->id }}].expired"
                                            @change="$nextTick(() => persistSelection())"
                                            class="h-4 w-4 rounded border-slate-300 text-primary shrink-0 disabled:cursor-not-allowed disabled:opacity-40"
                                            @if($isExpiredOnLoad) disabled @endif
                                        >

                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div
                                                class="w-3.5 h-3.5 rounded-full shadow shrink-0"
                                                style="background: {{ $item->ticketCategory->ticket_color }}"
                                            ></div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="text-sm font-semibold text-slate-900">
                                                        {{ $item->ticketCategory->name }}
                                                    </h4>

                                                    <span
                                                        x-show="items[{{ $item->id }}].expired"
                                                        x-cloak
                                                        class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600"
                                                        @if(! $isExpiredOnLoad) style="display: none;" @endif
                                                    >
                                                        {{ t(['en' => 'Expired', 'si' => 'කල් ඉකුත්']) }}
                                                    </span>
                                                </div>

                                                <p class="text-xs text-slate-500">
                                                    Rs {{ number_format($item->unit_price, 2) }}
                                                    {{ t(['en' => 'per ticket', 'si' => 'ටිකට් එකකට']) }}
                                                </p>

                                                {{-- Live hold + countdown --}}
                                                <div
                                                    x-show="!items[{{ $item->id }}].expired"
                                                    class="mt-2 space-y-1.5"
                                                    @if($isExpiredOnLoad) style="display: none;" @endif
                                                >
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        @if($item->inventory_held)
                                                            <span class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2 py-1 text-[11px] font-bold text-primary ring-1 ring-primary/15">
                                                                <i class="bi bi-shield-lock-fill text-[10px]" aria-hidden="true"></i>
                                                                {{ t([
                                                                    'en' => $item->quantity === 1
                                                                        ? '1 seat reserved'
                                                                        : "{$item->quantity} seats reserved",
                                                                    'si' => $item->quantity === 1
                                                                        ? 'ආසන 1 ක් වෙන්කර ඇත'
                                                                        : "ආසන {$item->quantity} ක් වෙන්කර ඇත",
                                                                ]) }}
                                                            </span>
                                                        @endif

                                                        <div
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold tabular-nums ring-1"
                                                            :class="{
                                                                'bg-emerald-50 text-emerald-800 ring-emerald-200/80': items[{{ $item->id }}].urgency === 'ok',
                                                                'bg-amber-50 text-amber-900 ring-amber-200': items[{{ $item->id }}].urgency === 'warn',
                                                                'bg-red-50 text-red-800 ring-red-200 animate-pulse': items[{{ $item->id }}].urgency === 'critical',
                                                            }"
                                                        >
                                                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                            <span>{{ t(['en' => 'Time left', 'si' => 'ඉතිරි කාලය']) }}</span>
                                                            <span class="font-mono" x-text="items[{{ $item->id }}].label">--:--</span>
                                                        </div>
                                                    </div>
                                                    <p class="text-[11px] leading-snug text-slate-500">
                                                        {{ t([
                                                            'en' => "Held for up to {$reservationDurationEn}. Pay before this ends or seats return to sale.",
                                                            'si' => "උපරිම {$reservationDurationSi} වෙන්කර ඇත. මෙය අවසන් වීමට පෙර ගෙවන්න, නැතිනම් ආසන නැවත විකිණීමට යයි.",
                                                        ]) }}
                                                    </p>
                                                </div>

                                                <p
                                                    x-show="items[{{ $item->id }}].expired"
                                                    x-cloak
                                                    class="mt-1 text-xs font-medium text-slate-500"
                                                    @if(! $isExpiredOnLoad) style="display: none;" @endif
                                                >
                                                    {{ t(['en' => 'Reservation expired — these seats were released back for sale. Clear this item from your cart.', 'si' => 'වෙන්කිරීම කල් ඉකුත් විය — මෙම ආසන නැවත විකිණීමට නිදහස් කර ඇත. කාර්ට් එකෙන් මෙම අයිතමය ඉවත් කරන්න.']) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            x-show="!items[{{ $item->id }}].expired"
                                            @if($isExpiredOnLoad) style="display: none;" @endif
                                        >
                                            <form
                                                action="{{ route('attendee.cart.update', $item) }}"
                                                method="POST"
                                                class="flex items-center gap-2"
                                            >
                                                @csrf
                                                @method('PUT')

                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    min="1"
                                                    max="{{ $maxQty }}"
                                                    value="{{ $item->quantity }}"
                                                    class="w-16 rounded-lg border-slate-300 py-1.5 text-center text-sm focus:border-primary focus:ring-primary"
                                                >

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold hover:bg-slate-200"
                                                >
                                                    {{ t(['en' => 'Update', 'si' => 'යාවත්කාලීන කරන්න']) }}
                                                </button>
                                            </form>
                                        </div>

                                        <div
                                            x-show="items[{{ $item->id }}].expired"
                                            x-cloak
                                            class="text-xs font-semibold text-slate-500"
                                            @if(! $isExpiredOnLoad) style="display: none;" @endif
                                        >
                                            × {{ $item->quantity }}
                                        </div>

                                        <div class="text-right min-w-[100px]">
                                            <div
                                                class="text-sm font-bold"
                                                :class="items[{{ $item->id }}].expired ? 'text-slate-400 line-through' : 'text-primary'"
                                            >
                                                Rs {{ number_format($item->line_total, 2) }}
                                            </div>
                                        </div>

                                        <form action="{{ route('attendee.cart.destroy', $item) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-sm text-red-600 font-medium hover:text-red-700"
                                            >
                                                {{ t(['en' => 'Remove', 'si' => 'ඉවත් කරන්න']) }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- RIGHT SIDEBAR --}}
            <div class="xl:col-span-4">
                <div class="sticky top-24 space-y-3">

                    {{-- Hold reminder --}}
                    <div
                        class="rounded-2xl border px-4 py-3 text-sm"
                        :class="{
                            'border-slate-200 bg-slate-50 text-slate-700': soonestUrgency === 'ok' || purchasableIds.length === 0,
                            'border-amber-200 bg-amber-50 text-amber-950': soonestUrgency === 'warn',
                            'border-red-200 bg-red-50 text-red-950': soonestUrgency === 'critical',
                        }"
                    >
                        <div class="flex items-center gap-2 font-semibold">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                            {{ t([
                                'en' => "Seats reserved for {$reservationDurationEn}",
                                'si' => "ආසන {$reservationDurationSi} වෙන්කර ඇත",
                            ]) }}
                        </div>
                        <p class="mt-1 text-xs opacity-90">
                            {{ t([
                                'en' => 'Tickets stay locked for you until the timer ends. Pay before it runs out or they return to sale for others.',
                                'si' => 'ටයිමර් අවසන් වන තුරු ටිකට් ඔබට අගුළු දමා ඇත. කල් ඉකුත් වීමට පෙර ගෙවන්න, නැතිනම් ඒවා අනෙක් අයට විකිණීමට යයි.',
                            ]) }}
                        </p>
                        <div class="mt-2 flex items-baseline justify-between gap-2" x-show="purchasableIds.length > 0">
                            <span class="text-[11px] font-medium uppercase tracking-wide opacity-70">
                                {{ t(['en' => 'Soonest ends in', 'si' => 'ඉක්මනින්ම අවසන් වන්නේ']) }}
                            </span>
                            <p class="font-mono text-base font-bold tabular-nums" x-text="soonestLabel"></p>
                        </div>
                    </div>

                    <form
                        id="checkout-form"
                        action="{{ route('attendee.cart.checkout') }}"
                        method="POST"
                        @submit="if (selectedIds.length === 0) { $event.preventDefault(); alert(@js(t(['en' => 'Please select at least one ticket to pay.', 'si' => 'ගෙවීමට අවම වශයෙන් ටිකට් එකක් තෝරන්න.']))); }"
                    >
                        @csrf

                        <template x-for="id in Object.keys(items).filter(id => selected[id] && !items[id].expired)" :key="id">
                            <input type="hidden" name="cart_item_ids[]" :value="id">
                        </template>

                        <div class="rounded-2xl bg-white border border-slate-200 shadow-lg p-4 sm:p-5">
                            <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                                {{ t(['en' => 'Order Summary', 'si' => 'ඇණවුම් සාරාංශය']) }}
                            </h3>

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">
                                        {{ t(['en' => 'Reserved Lines', 'si' => 'වෙන්කර ගත් අයිතම']) }}
                                    </span>
                                    <span class="font-semibold" x-text="selectedLines">0</span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">
                                        {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
                                    </span>
                                    <span class="font-semibold" x-text="selectedTickets">0</span>
                                </div>

                                <div class="border-t border-slate-200 pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-slate-700">
                                            {{ t(['en' => 'Total', 'si' => 'මුළු එකතුව']) }}
                                        </span>
                                        <span class="text-xl font-black text-primary" x-text="formatMoney(selectedTotal)">
                                            Rs 0.00
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="payment_method" id="payment_method" value="stripe">

                            <div
                                class="mt-4 rounded-xl border px-3.5 py-3 text-sm"
                                :class="selectedTotal > 0 && walletBalance >= selectedTotal
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : selectedTotal > 0 && walletBalance < selectedTotal
                                        ? 'border-amber-200 bg-amber-50'
                                        : 'border-slate-200 bg-slate-50'"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-700">{{ t(['en' => 'Wallet Balance', 'si' => 'පසුම්බි ශේෂය']) }}</p>
                                        <p class="mt-0.5 text-base font-bold text-primary" x-text="formatMoney(walletBalance)">
                                            Rs {{ number_format($walletBalance, 2) }}
                                        </p>
                                    </div>
                                    <a
                                        href="{{ route('attendee.wallet.index') }}"
                                        @click="goToWallet($event)"
                                        class="shrink-0 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-primary shadow-sm ring-1 ring-primary/20 hover:bg-primary/5"
                                    >
                                        {{ t(['en' => 'Top up', 'si' => 'ගෙවීම කරන්න']) }}
                                    </a>
                                </div>

                                <p
                                    class="mt-2 text-xs font-medium text-emerald-700"
                                    x-show="selectedTotal > 0 && walletBalance >= selectedTotal"
                                    x-cloak
                                >
                                    {{ t(['en' => 'Your wallet covers this order. You can pay with wallet.', 'si' => 'ඔබේ පසුම්බිය මෙම ඇණවුම ආවරණය කරයි. පසුම්බියෙන් ගෙවිය හැක.']) }}
                                </p>
                                <div
                                    class="mt-2 space-y-1"
                                    x-show="selectedTotal > 0 && walletBalance < selectedTotal"
                                    x-cloak
                                >
                                    <p class="text-xs font-medium text-amber-800">
                                        {{ t(['en' => 'Need', 'si' => 'අවශ්‍ය']) }}
                                        <span class="font-bold" x-text="formatMoney(Math.max(0, selectedTotal - walletBalance))"></span>
                                        {{ t(['en' => 'more to pay with wallet.', 'si' => 'තවත් පසුම්බියෙන් ගෙවීමට.']) }}
                                    </p>
                                    <a
                                        href="{{ route('attendee.wallet.index') }}"
                                        @click="goToWallet($event)"
                                        class="inline-flex text-xs font-semibold text-amber-900 underline decoration-amber-400 underline-offset-2 hover:text-amber-950"
                                    >
                                        {{ t(['en' => 'Top up wallet before checkout →', 'si' => 'ගෙවීමට පෙර පසුම්බියට මුදල් එකතු කරන්න →']) }}
                                    </a>
                                </div>
                                <a
                                    href="{{ route('attendee.wallet.index') }}"
                                    @click="goToWallet($event)"
                                    class="mt-1.5 inline-block text-xs font-medium text-slate-500 hover:text-primary"
                                    x-show="selectedTotal === 0"
                                    x-cloak
                                >
                                    {{ t(['en' => 'Manage wallet →', 'si' => 'පසුම්බිය කළමනාකරණය කරන්න →']) }}
                                </a>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button
                                    type="submit"
                                    :disabled="selectedIds.length === 0 || walletBalance < selectedTotal"
                                    :class="(selectedIds.length === 0 || walletBalance < selectedTotal)
                                        ? 'cursor-not-allowed opacity-50'
                                        : 'hover:opacity-95'"
                                    onclick="
                                        if (!confirm(@js(t(['en' => 'Are you sure you want to pay by wallet?', 'si' => 'ඔබට පසුම්බියෙන් ගෙවීමට අවශ්‍ය බව විශ්වාසද?'])))) {
                                            event.preventDefault();
                                            return false;
                                        }
                                        document.getElementById('payment_method').value='wallet';
                                    "
                                    class="w-full rounded-xl bg-gradient-to-r from-[#0F0363] to-[#2A1585] px-5 py-2.5 text-sm font-bold text-white shadow-md transition"
                                >
                                    {{ t(['en' => 'Pay with Wallet', 'si' => 'පසුම්බියෙන් ගෙවන්න']) }}
                                </button>

                                <button
                                    type="submit"
                                    onclick="document.getElementById('payment_method').value='stripe'"
                                    class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-green-500 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95 transition"
                                >
                                    {{ t(['en' => 'Pay Securely with Stripe', 'si' => 'Stripe සමඟ ආරක්ෂිතව ගෙවන්න']) }}
                                </button>
                            </div>

                            <p class="mt-3 text-center text-xs text-slate-500">
                                Secure payment powered by Stripe
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    </div>
</x-app-layout>

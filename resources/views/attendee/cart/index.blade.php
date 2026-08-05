<x-app-layout>
    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 py-5"
        x-data="{
            selected: {
                @foreach ($selectedCartItemIds as $selectedId)
                    {{ $selectedId }}: true,
                @endforeach
            },
            items: {
                @foreach ($cartItems->flatten() as $item)
                    {{ $item->id }}: {
                        qty: {{ (int) $item->quantity }},
                        total: {{ (float) $item->line_total }},
                        deadline: @js($item->purchaseDeadlineAt()->toIso8601String()),
                        expired: {{ $item->hasPurchaseDeadlinePassed() ? 'true' : 'false' }},
                    },
                @endforeach
            },
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
                const now = Date.now();
                let changed = false;

                Object.keys(this.items).forEach(id => {
                    const item = this.items[id];
                    const isExpired = now >= new Date(item.deadline).getTime();

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
            get purchasableIds() {
                return Object.keys(this.items).filter(id => !this.items[id].expired);
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

    {{-- Header Stats --}}
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
                        {{ t(['en' => 'Review your reserved tickets and complete checkout securely.', 'si' => 'ඔබේ වෙන්කර ගත් ටිකට් සමාලෝචනය කර ආරක්ෂිතව ගෙවීම සම්පූර්ණ කරන්න.']) }}
                    </p>
                </div>

                <div class="flex gap-2.5">

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
                            {{ $cartItems->flatten()->sum('quantity') }}
                        </div>
                    </div>

                </div>

            </div>
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
                        @change="toggleAll($event.target.checked)"
                        class="h-4 w-4 rounded border-slate-300 text-primary"
                    >

                    <span class="text-sm font-semibold text-slate-700">
                        {{ t(['en' => 'Select All Tickets', 'si' => 'සියලු ටිකට් තෝරන්න']) }}
                    </span>

                </label>

                <div class="text-sm text-slate-500">
                    {{ t(['en' => 'Cart Value', 'si' => 'මුළු එකතුව']) }}
                    <span class="font-bold text-slate-900 ml-2">
                        Rs {{ number_format($cartTotal,2) }}
                    </span>
                </div>

            </div>

            {{-- EVENTS --}}
            @foreach ($cartItems as $eventId => $items)

                @php
                    $event = $items->first()->event;
                @endphp

                <div class="group overflow-hidden rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-lg transition duration-300">

                    {{-- Event Header --}}
                    <div class="relative border-b border-slate-100">

                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>

                        <div class="pl-6 pr-4 py-3.5">

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">

                                <div>

                                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                                        {{ $event->name }}
                                    </h3>

                                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-slate-500">

                                        <span>
                                            📅
                                            @if ($event->hasDateYetToBeScheduled())
                                                {{ t(['en' => 'Date & time not chosen yet', 'si' => 'දිනය සහ වේලාව තවම තෝරා නැත']) }}
                                            @else
                                                {{ $event->formattedScheduleDate('Y-m-d') ?? $event->date }}
                                            @endif
                                        </span>

                                        <span>
                                            📍 {{ $event->place }}
                                        </span>

                                        @if($event->host)
                                            <span>
                                                👤 {{ $event->host->name }}
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

                    {{-- Ticket Items --}}
                    <div class="divide-y divide-slate-100">

                        @foreach($items as $item)
                            @php
                                $isExpiredOnLoad = $item->hasPurchaseDeadlinePassed();
                            @endphp

                            <div
                                class="px-4 py-3.5 transition"
                                :class="items[{{ $item->id }}].expired ? 'bg-slate-50/80' : 'hover:bg-slate-50'"
                            >

                                <div
                                    class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-4"
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
                                                Rs {{ number_format($item->unit_price,2) }} {{ t(['en' => 'per ticket', 'si' => 'ටිකට් එකකට']) }}
                                            </p>

                                            <p
                                                x-show="items[{{ $item->id }}].expired"
                                                x-cloak
                                                class="mt-1 text-xs font-medium text-slate-500"
                                                @if(! $isExpiredOnLoad) style="display: none;" @endif
                                            >
                                                {{ t(['en' => 'Purchase deadline has passed. This reservation can no longer be checked out.', 'si' => 'මිලදී ගැනීමේ අවසාන දිනය ඉකුත් වී ඇත. මෙම වෙන්කිරීම තවදුරටත් ගෙවීමට නොහැක.']) }}
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
                                                max="{{ $item->ticketCategory->no_of_available_tickets }}"
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
                                            Rs {{ number_format($item->line_total,2) }}
                                        </div>

                                    </div>

                                    <form
                                        action="{{ route('attendee.cart.destroy', $item) }}"
                                        method="POST"
                                    >
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

            <div class="sticky top-24">

                <form
                    id="checkout-form"
                    action="{{ route('attendee.cart.checkout') }}"
                    method="POST"
                    @submit="if (selectedIds.length === 0) { $event.preventDefault(); alert(@js(t(['en' => 'Please select at least one ticket to pay.', 'si' => 'ගෙවීමට අවම වශයෙන් ටිකට් එකක් තෝරන්න.']))); }"
                >
                    @csrf

                    <template x-for="id in Object.keys(items).filter(id => selected[id])" :key="id">
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

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm">
                            <p class="font-semibold text-slate-700">{{ t(['en' => 'Wallet Balance', 'si' => 'පසුම්බි ශේෂය']) }}</p>
                            <p class="mt-0.5 text-base font-bold text-primary">
                                Rs {{ number_format($walletBalance, 2) }}
                            </p>
                            <a
                                href="{{ route('attendee.wallet.index') }}"
                                @click="goToWallet($event)"
                                class="mt-1.5 inline-block text-xs font-medium text-primary hover:text-primary-dark"
                            >
                                {{ t(['en' => 'Manage wallet →', 'si' => 'පසුම්බිය කළමනාකරණය කරන්න →']) }}
                            </a>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <button
                                type="submit"
                                onclick="
                                    if (!confirm(@js(t(['en' => 'Are you sure you want to pay by wallet?', 'si' => 'ඔබට පසුම්බියෙන් ගෙවීමට අවශ්‍ය බව විශ්වාසද?'])))) {
                                        event.preventDefault();
                                        return false;
                                    }
                                    document.getElementById('payment_method').value='wallet';
                                "
                                class="w-full rounded-xl bg-gradient-to-r from-[#0F0363] to-[#2A1585] px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95 transition"
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
</div>
</x-app-layout>

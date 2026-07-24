<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5">

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
                        checked
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
                                            📅 {{ $event->date }}
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

                            <div class="px-4 py-3.5 hover:bg-slate-50 transition">

                                <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-4">

                                    <input
                                        type="checkbox"
                                        checked
                                        name="cart_item_ids[]"
                                        value="{{ $item->id }}"
                                        form="checkout-form"
                                        class="h-4 w-4 rounded border-slate-300 text-primary shrink-0"
                                    >

                                    <div class="flex items-center gap-3 flex-1 min-w-0">

                                        <div
                                            class="w-3.5 h-3.5 rounded-full shadow shrink-0"
                                            style="background: {{ $item->ticketCategory->ticket_color }}"
                                        ></div>

                                        <div class="min-w-0">

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                {{ $item->ticketCategory->name }}
                                            </h4>

                                            <p class="text-xs text-slate-500">
                                                Rs {{ number_format($item->unit_price,2) }} {{ t(['en' => 'per ticket', 'si' => 'ටිකට් එකකට']) }}
                                            </p>

                                        </div>

                                    </div>

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

                                    <div class="text-right min-w-[100px]">

                                        <div class="text-sm font-bold text-primary">
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
                >
                    @csrf

                    <div class="rounded-2xl bg-white border border-slate-200 shadow-lg p-4 sm:p-5">

                        <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                            {{ t(['en' => 'Order Summary', 'si' => 'ඇණවුම් සාරාංශය']) }}
                        </h3>

                        <div class="mt-4 space-y-3 text-sm">

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    {{ t(['en' => 'Reserved Lines', 'si' => 'වෙන්කර ගත් අයිතම']) }}
                                </span>

                                <span class="font-semibold">
                                    {{ $cartItems->flatten()->count() }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
                                </span>

                                <span class="font-semibold">
                                    {{ $cartItems->flatten()->sum('quantity') }}
                                </span>
                            </div>

                            <div class="border-t border-slate-200 pt-3">

                                <div class="flex justify-between items-center">

                                    <span class="font-semibold text-slate-700">
                                        {{ t(['en' => 'Total', 'si' => 'මුළු එකතුව']) }}
                                    </span>

                                    <span class="text-xl font-black text-primary">
                                        Rs {{ number_format($cartTotal,2) }}
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
                            <a href="{{ route('attendee.wallet.index') }}" class="mt-1.5 inline-block text-xs font-medium text-primary hover:text-primary-dark">
                                {{ t(['en' => 'Manage wallet →', 'si' => 'පසුම්බිය කළමනාකරණය කරන්න →']) }}
                            </a>
                        </div>

                        @if($canPayWithWallet)
                            <button
                                type="submit"
                                onclick="document.getElementById('payment_method').value='wallet'"
                                class="mt-3 w-full rounded-xl bg-gradient-to-r from-[#0F0363] to-[#2A1585] px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95 transition"
                            >
                                {{ t(['en' => 'Pay by Wallet', 'si' => 'පසුම්බියෙන් ගෙවන්න']) }}
                            </button>
                        @endif

                        <button
                            type="submit"
                            onclick="document.getElementById('payment_method').value='stripe'"
                            class="mt-2 w-full rounded-xl bg-gradient-to-r from-emerald-600 to-green-500 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:opacity-95 transition"
                        >
                            {{ t(['en' => 'Pay Securely with Stripe', 'si' => 'Stripe සමඟ ආරක්ෂිතව ගෙවන්න']) }}
                        </button>

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

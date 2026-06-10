<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6">

    {{-- Header Stats --}}
    <div class="mb-8">
        <div class="rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-8 text-white shadow-xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                <div>
                    <h1 class="text-4xl font-black tracking-tight">
                        My Cart
                    </h1>

                    <p class="mt-2 text-indigo-100">
                        Review your reserved tickets and complete checkout securely.
                    </p>
                </div>

                <div class="flex gap-4">

                    <div class="rounded-2xl bg-white/10 backdrop-blur px-5 py-4">
                        <div class="text-xs uppercase tracking-wider text-indigo-100">
                            Events
                        </div>
                        <div class="text-2xl font-bold">
                            {{ $cartItems->count() }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/10 backdrop-blur px-5 py-4">
                        <div class="text-xs uppercase tracking-wider text-indigo-100">
                            Tickets
                        </div>
                        <div class="text-2xl font-bold">
                            {{ $cartItems->flatten()->sum('quantity') }}
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

        {{-- LEFT SIDE --}}
        <div class="xl:col-span-8 space-y-6">

            {{-- Select All --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <label class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        checked
                        @change="toggleAll($event.target.checked)"
                        class="h-5 w-5 rounded border-slate-300 text-indigo-600"
                    >

                    <span class="font-semibold text-slate-700">
                        Select All Tickets
                    </span>

                </label>

                <div class="text-sm text-slate-500">
                    Cart Value
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

                <div class="group overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-sm hover:shadow-xl transition duration-300">

                    {{-- Event Header --}}
                    <div class="relative border-b border-slate-100">

                        <div class="absolute left-0 top-0 bottom-0 w-2 bg-indigo-600"></div>

                        <div class="pl-8 pr-6 py-6">

                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                                <div>

                                    <h3 class="text-xl font-bold text-slate-900">
                                        {{ $event->name }}
                                    </h3>

                                    <div class="flex flex-wrap gap-4 mt-2 text-sm text-slate-500">

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

                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700">
                                    {{ $items->count() }} Item(s)
                                </span>

                            </div>

                        </div>

                    </div>

                    {{-- Ticket Items --}}
                    <div class="divide-y divide-slate-100">

                        @foreach($items as $item)

                            <div class="p-5 hover:bg-slate-50 transition">

                                <div class="flex flex-col lg:flex-row lg:items-center gap-5">

                                    <input
                                        type="checkbox"
                                        checked
                                        name="cart_item_ids[]"
                                        value="{{ $item->id }}"
                                        form="checkout-form"
                                        class="h-5 w-5 rounded border-slate-300 text-indigo-600 shrink-0"
                                    >

                                    <div class="flex items-center gap-4 flex-1">

                                        <div
                                            class="w-4 h-4 rounded-full shadow"
                                            style="background: {{ $item->ticketCategory->ticket_color }}"
                                        ></div>

                                        <div>

                                            <h4 class="font-semibold text-slate-900">
                                                {{ $item->ticketCategory->name }}
                                            </h4>

                                            <p class="text-sm text-slate-500">
                                                Rs {{ number_format($item->unit_price,2) }} per ticket
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
                                            class="w-20 rounded-xl border-slate-300 text-center focus:border-indigo-500 focus:ring-indigo-500"
                                        >

                                        <button
                                            type="submit"
                                            class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold hover:bg-slate-200"
                                        >
                                            Update
                                        </button>

                                    </form>

                                    <div class="text-right min-w-[120px]">

                                        <div class="text-lg font-bold text-indigo-600">
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
                                            class="text-red-600 font-medium hover:text-red-700"
                                        >
                                            Remove
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

                    <div class="rounded-3xl bg-white border border-slate-200 shadow-xl p-6">

                        <h3 class="text-xl font-bold text-slate-900">
                            Order Summary
                        </h3>

                        <div class="mt-6 space-y-4">

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    Reserved Lines
                                </span>

                                <span class="font-semibold">
                                    {{ $cartItems->flatten()->count() }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    Tickets
                                </span>

                                <span class="font-semibold">
                                    {{ $cartItems->flatten()->sum('quantity') }}
                                </span>
                            </div>

                            <div class="border-t border-slate-200 pt-4">

                                <div class="flex justify-between items-center">

                                    <span class="font-semibold text-slate-700">
                                        Total
                                    </span>

                                    <span class="text-2xl font-black text-indigo-600">
                                        Rs {{ number_format($cartTotal,2) }}
                                    </span>

                                </div>

                            </div>

                        </div>

                        <input type="hidden" name="payment_method" id="payment_method" value="stripe">

                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                            <p class="font-semibold text-slate-700">Wallet Balance</p>
                            <p class="mt-1 text-lg font-bold text-indigo-600">
                                Rs {{ number_format($walletBalance, 2) }}
                            </p>
                            <a href="{{ route('attendee.wallet.index') }}" class="mt-2 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                Manage wallet →
                            </a>
                        </div>

                        @if($canPayWithWallet)
                            <button
                                type="submit"
                                onclick="document.getElementById('payment_method').value='wallet'"
                                class="mt-4 w-full rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 font-bold text-white shadow-lg hover:scale-[1.02] transition"
                            >
                                Pay by Wallet
                            </button>
                        @endif

                        <button
                            type="submit"
                            onclick="document.getElementById('payment_method').value='stripe'"
                            class="mt-3 w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-green-500 px-6 py-4 font-bold text-white shadow-lg hover:scale-[1.02] transition"
                        >
                            Pay Securely with Stripe
                        </button>

                        <p class="mt-4 text-center text-xs text-slate-500">
                            Secure payment powered by Stripe
                        </p>

                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
</x-app-layout>

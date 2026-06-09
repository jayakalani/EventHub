<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">My Cart</h2>
                <p class="text-slate-500 mt-1">Review reserved tickets grouped by event and pay when ready.</p>
            </div>
            <a href="{{ route('attendee.dashboard') }}"
                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Browse Events
            </a>
        </div>
    </x-slot>

    <div
    class="py-8"
    x-data='{
        itemTotals: @json($cartItems->flatten()->mapWithKeys(fn($item) => [$item->id => (float) $item->line_total])),
        selectedTotal() {
            return Array.from(document.querySelectorAll("input[name=\"cart_item_ids[]\"]:checked"))
                .reduce((sum, el) => sum + (this.itemTotals[el.value] || 0), 0);
        },
        toggleAll(checked) {
            document.querySelectorAll("input[name=\"cart_item_ids[]\"]")
                .forEach(el => el.checked = checked);
        }
    }'
>
        <div class="max-w-7xl mx-auto px-6 space-y-6">

            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($cartItems->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-slate-500">Your cart is empty. Reserve tickets from an event page.</p>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="mt-4 inline-flex rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Find Events
                    </a>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" checked @change="toggleAll($event.target.checked)"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Select all items
                    </label>
                    <div class="text-sm text-slate-500">
                        Cart total: <span class="font-bold text-slate-900">Rs {{ number_format($cartTotal, 2) }}</span>
                    </div>
                </div>

                @foreach ($cartItems as $eventId => $items)
                    @php $event = $items->first()->event; @endphp
                    <div class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                            <h3 class="text-lg font-bold text-slate-900">{{ $event->name }}</h3>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ $event->date }} · {{ $event->place }}
                                @if ($event->host)
                                    · Host: {{ $event->host->name }}
                                @endif
                            </p>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($items as $item)
                                <div class="px-6 py-4 flex flex-col lg:flex-row lg:items-center gap-4">
                                    <input type="checkbox" name="cart_item_ids[]" value="{{ $item->id }}" checked
                                        form="checkout-form"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 shrink-0">

                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <span class="h-3 w-3 rounded-full shrink-0"
                                            style="background-color: {{ $item->ticketCategory->ticket_color }}"></span>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $item->ticketCategory->name }}</p>
                                            <p class="text-sm text-slate-500">Rs {{ number_format($item->unit_price, 2) }} per ticket</p>
                                        </div>
                                    </div>

                                    <form action="{{ route('attendee.cart.update', $item) }}" method="POST"
                                        class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="text-sm text-slate-500">Qty</label>
                                        <input type="number" name="quantity" min="1"
                                            max="{{ $item->ticketCategory->no_of_available_tickets }}"
                                            value="{{ $item->quantity }}"
                                            class="w-20 rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="submit"
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                            Update
                                        </button>
                                    </form>

                                    <div class="font-semibold text-indigo-600 min-w-[120px] text-right">
                                        Rs {{ number_format($item->line_total, 2) }}
                                    </div>

                                    <form action="{{ route('attendee.cart.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-sm font-medium text-red-600 hover:text-red-700">Remove</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <form id="checkout-form" action="{{ route('attendee.cart.checkout') }}" method="POST"
                    class="mt-8 sticky bottom-4 rounded-2xl border border-indigo-100 bg-white p-5 shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    @csrf
                    <div>
                        <p class="text-sm text-slate-500">Selected items will be paid and confirmed</p>
                        <p class="text-sm text-slate-500 mt-1">{{ $cartItems->flatten()->count() }} reserved line(s) in cart</p>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-8 py-3 font-semibold text-white hover:bg-emerald-700">
                        Pay Selected Tickets
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

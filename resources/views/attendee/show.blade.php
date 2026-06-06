<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Event Details
                </h2>
                <p class="text-slate-500 mt-1">
                    event information, ticket categories and bookings.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">

        <div x-data="{ showModal: false, selected: { id: null, name: '', price: 0, available: 0, color: '' }, qty: 1, amount: 0 }">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

            {{-- ERROR ALERT --}}
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                    <div class="flex gap-3">

                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                            ⚠️
                        </div>

                        <div>

                            <h3 class="font-semibold text-red-800">
                                Something went wrong
                            </h3>

                            <ul class="mt-2 text-sm text-red-700 space-y-1">

                                @foreach ($errors->all() as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach

                            </ul>

                        </div>

                    </div>

                </div>
            @endif

            {{-- SUCCESS ALERT --}}
            @if(session('success'))
                <div
                    x-data="{show:true}"
                    x-init="setTimeout(() => show=false,5000)"
                    x-show="show"
                    x-transition
                    class="rounded-2xl border border-green-200 bg-green-50 p-5">

                    <div class="flex justify-between items-center">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                ✓
                            </div>

                            <div>

                                <h3 class="font-semibold text-green-800">
                                    Success
                                </h3>

                                <p class="text-sm text-green-700">
                                    {{ session('success') }}
                                </p>

                            </div>

                        </div>

                        <button
                            @click="show=false"
                            class="text-green-600 hover:text-green-800">
                            ✕
                        </button>

                    </div>

                </div>
            @endif

            {{-- EVENT HERO --}}
            <div class="relative overflow-hidden rounded-[32px] shadow-xl">

                @if($event->cover)
                    <img src="{{ asset('uploads/covers/events/'.$event->cover) }}"
                         class="h-[420px] w-full object-cover">
                @endif

                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                <div class="absolute bottom-0 left-0 right-0 p-8">

                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6">

                        <div>

                            <span class="rounded-full bg-white/20 backdrop-blur px-4 py-1 text-white text-sm">
                                {{ $event->eventCategory->name ?? 'Category' }}
                            </span>

                            <h1 class="mt-4 text-5xl font-bold text-white">
                                {{ $event->name }}
                            </h1>

                            <div class="mt-4 flex flex-wrap gap-5 text-white/90">

                                <span>📍 {{ $event->place }}</span>

                                <span>
                                    📅 {{ \Carbon\Carbon::parse($event->date)->format('d M Y') }}
                                </span>

                                <span>
                                    🕒 {{ $event->time }}
                                </span>

                            </div>

                        </div>

                        <span class="rounded-full px-5 py-2 text-sm font-semibold
                            {{ $event->status === 'ongoing'
                                ? 'bg-green-500 text-white'
                                : 'bg-white/20 backdrop-blur text-white' }}">

                            {{ ucfirst($event->status) }}

                        </span>

                    </div>

                </div>

            </div>

            {{-- STATS --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">

                <div class="bg-white border rounded-3xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Hosted By</p>
                    <h3 class="mt-2 text-lg font-semibold">
                        {{ $event->host->name ?? 'N/A' }}
                    </h3>
                </div>

                <div class="bg-white border rounded-3xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Total Seats</p>
                    <h3 class="mt-2 text-lg font-semibold">
                        {{ number_format($event->no_of_seats) }}
                    </h3>
                </div>

                <div class="bg-white border rounded-3xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Contact Person</p>
                    <h3 class="mt-2 text-lg font-semibold">
                        {{ $event->contactPerson->name ?? 'N/A' }}
                    </h3>
                </div>

                <div class="bg-white border rounded-3xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500">Status</p>
                    <h3 class="mt-2 text-lg font-semibold">
                        {{ ucfirst($event->status) }}
                    </h3>
                </div>

            </div>

            {{-- DESCRIPTION --}}
            <div class="bg-white border rounded-3xl p-8 shadow-sm">

                <h2 class="text-2xl font-bold text-slate-900 mb-4">
                    About This Event
                </h2>

                <p class="leading-relaxed text-slate-600">
                    {{ $event->description }}
                </p>

            </div>

            {{-- SEAT CATEGORIES --}}
            <div>

                <div class="mb-6">

                    <h2 class="text-2xl font-bold text-slate-900">
                        Seat Categories
                    </h2>

                    <p class="text-slate-500">
                        Choose your preferred seating category.
                    </p>

                </div>

                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">

                    @forelse($seatCategories as $category)

                        <div class="bg-white border rounded-[28px] overflow-hidden shadow-sm hover:shadow-xl transition duration-300">

                            <div class="h-2"
                                 style="background-color: {{ $category->ticket_color }}"></div>

                            <div class="p-6">

                                <div class="flex justify-between items-start">

                                    <div>

                                        <h3 class="text-xl font-bold">
                                            {{ $category->name }}
                                        </h3>

                                        <p class="text-sm text-slate-500 mt-1">
                                            {{ $category->description }}
                                        </p>

                                    </div>

                                    <div class="w-5 h-5 rounded-full border"
                                         style="background-color: {{ $category->ticket_color }}">
                                    </div>

                                </div>

                                <div class="mt-6">

                                    <div class="text-4xl font-bold text-indigo-600">
                                        Rs {{ number_format($category->seat_price) }}
                                    </div>

                                    <div class="text-sm text-slate-500">
                                        per ticket
                                    </div>

                                </div>

                                <div class="mt-6 space-y-3">

                                    <div class="flex justify-between">
                                        <span>Total Seats</span>
                                        <span class="font-semibold">
                                            {{ $category->no_of_seats }}
                                        </span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Available</span>
                                        <span class="font-semibold text-green-600">
                                            {{ $category->no_of_available_seats }}
                                        </span>
                                    </div>

                                </div>

                                <button type="button"
                                    @click="selected = { id: {{ $category->id }}, name: {{ json_encode($category->name) }}, price: {{ $category->seat_price }}, available: {{ $category->no_of_available_seats }}, color: {{ json_encode($category->ticket_color) }} }; qty = 1; amount = (selected.price * 1).toFixed(2); showModal = true"
                                    class="mt-6 w-full rounded-2xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-700 transition">

                                    Book Now

                                </button>

                            </div>

                        </div>

                    @empty

                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                            <p class="text-slate-500">
                                No seat categories available yet.
                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

            <!-- Booking Modal -->
            <div x-show="showModal" x-cloak style="display:none;" @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50"></div>

                <!-- Modal box -->
                <div class="relative w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl overflow-hidden z-50">
                    <form action="/attendee/bookings" method="POST" class="p-6">
                        @csrf

                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Reserve Tickets</h3>
                            <button type="button" class="text-slate-400 hover:text-slate-600" @click="showModal = false">✕</button>
                        </div>

                        <div class="mt-4 space-y-3">
                            <input type="hidden" name="event_id" value="{{ $event->id }}">
                            <input type="hidden" name="seat_category_id" :value="selected.id">

                            <div>
                                <label class="text-sm text-slate-500">Category</label>
                                <div class="mt-1 font-semibold text-slate-900" x-text="selected.name"></div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-slate-500">Price</label>
                                    <div class="mt-1 font-semibold text-slate-900">Rs <span x-text="Number(selected.price).toFixed(2)"></span></div>
                                </div>

                                <div>
                                    <label class="text-sm text-slate-500">Available</label>
                                    <div class="mt-1 font-semibold text-green-600" x-text="selected.available"></div>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm text-slate-500">Number of Tickets</label>
                                <input type="number" name="quantity" x-model.number="qty" min="1" :max="selected.available" class="mt-1 w-full rounded-lg border px-3 py-2" @input="amount = (qty * selected.price).toFixed(2)">
                            </div>

                            <div>
                                <label class="text-sm text-slate-500">Amount</label>
                                <div class="mt-1 font-semibold text-slate-900">Rs <span x-text="amount"></span></div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2 text-white font-semibold hover:bg-amber-600">Reserve Tickets</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>

</x-app-layout>
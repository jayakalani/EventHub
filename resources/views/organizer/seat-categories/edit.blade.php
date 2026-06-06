<x-app-layout>
    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 text-sm text-slate-500 mb-2">
                <a href="{{ route('organizer.events.show', $event->id) }}" class="hover:text-indigo-600">
                    Event Details
                </a>
                <span>/</span>
                <span>Edit Seat Category</span>
            </div>

            <h1 class="text-3xl font-bold text-slate-900">
                Edit Seat Category
            </h1>

            <p class="mt-2 text-slate-500">
                Update seating information, pricing and booking availability.
            </p>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
                <h3 class="font-semibold text-red-700 mb-2">
                    Please fix the following errors
                </h3>

                <ul class="list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('organizer.seat-categories.update', [$event->id, $seatCategory->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid lg:grid-cols-3 gap-6">

                {{-- LEFT --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Category Details --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-6">
                            Category Details
                        </h2>

                        <div class="space-y-5">

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Category Name
                                </label>

                                <input type="text" name="name" value="{{ old('name', $seatCategory->name) }}"
                                    class="w-full rounded-2xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Description
                                </label>

                                <textarea name="description" rows="5"
                                    class="w-full rounded-2xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $seatCategory->description) }}</textarea>
                            </div>

                        </div>

                    </div>

                    {{-- Capacity & Pricing --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-6">
                            Capacity & Pricing
                        </h2>

                        <div class="grid md:grid-cols-2 gap-5">

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Total Seats
                                </label>

                                <input type="number" min="1" name="no_of_seats"
                                    value="{{ old('no_of_seats', $seatCategory->no_of_seats) }}"
                                    class="w-full rounded-2xl border-slate-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Seat Price (LKR)
                                </label>

                                <input type="number" step="0" min="0" name="seat_price"
                                    value="{{ old('seat_price', $seatCategory->seat_price) }}"
                                    class="w-full rounded-2xl border-slate-300" required>
                            </div>

                        </div>

                    </div>

                    {{-- Booking Window --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-6">
                            Booking Window
                        </h2>

                        <div class="grid md:grid-cols-2 gap-5">

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Booking Start
                                </label>

                                <input type="datetime-local" name="booking_start"
                                    value="{{ old('booking_start', optional($seatCategory->booking_start)->format('Y-m-d\TH:i')) }}"
                                    class="w-full rounded-2xl border-slate-300">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Booking End
                                </label>

                                <input type="datetime-local" name="booking_end"
                                    value="{{ old('booking_end', optional($seatCategory->booking_end)->format('Y-m-d\TH:i')) }}"
                                    class="w-full rounded-2xl border-slate-300">
                            </div>

                        </div>

                    </div>

                </div>

                {{-- RIGHT SIDEBAR --}}
                <div class="space-y-6">

                    {{-- Event Summary --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-5">
                            Event Information
                        </h2>

                        <div class="space-y-4">

                            <div>
                                <p class="text-xs text-slate-500">
                                    Event Name
                                </p>
                                <p class="font-semibold">
                                    {{ $event->name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Venue
                                </p>
                                <p class="font-semibold">
                                    {{ $event->place }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">
                                    Date
                                </p>
                                <p class="font-semibold">
                                    {{ $event->date }}
                                </p>
                            </div>

                        </div>

                    </div>

                    {{-- Appearance --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-5">
                            Appearance
                        </h2>

                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Ticket Color
                            </label>

                            <input type="color" name="ticket_color" value="{{ $seatCategory->ticket_color }}"
                                class="h-14 w-full rounded-xl border border-slate-300">
                        </div>

                    </div>

                    {{-- Status --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <h2 class="text-lg font-semibold mb-5">
                            Status
                        </h2>

                        <select name="is_active" class="w-full rounded-2xl border-slate-300">
                            <option value="1" {{ old('is_active', $seatCategory->is_active) ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ !old('is_active', $seatCategory->is_active) ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>

                    </div>

                    {{-- Save --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">

                        <button type="submit"
                            class="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 py-4 text-white font-semibold shadow-lg hover:shadow-xl transition">
                            Update Seat Category
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>
</x-app-layout>

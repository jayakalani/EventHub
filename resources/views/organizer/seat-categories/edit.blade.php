<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('Seat Categories') }}
                </p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ __('Edit Seat Category') }}
                </h2>
            </div>

            <a href="{{ route('organizer.events.show', $event->id) }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('Back to Event') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 overflow-hidden rounded-2xl border border-red-200 bg-red-50 px-5 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            !
                        </div>
                        <div>
                            <p class="font-semibold text-red-800">{{ __('Please fix the following errors') }}</p>
                            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-xl shadow-indigo-100/50 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl shadow-inner ring-1 ring-white/25"
                                style="background-color: {{ $seatCategory->ticket_color ?? '#6366f1' }}">
                                <svg class="h-8 w-8 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold tracking-tight">
                                    {{ $seatCategory->name }}
                                </h3>
                                <p class="mt-1 text-sm text-indigo-50">
                                    {{ $event->name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $seatCategory->is_active ? 'bg-emerald-400/20 text-white ring-emerald-100/40' : 'bg-rose-400/20 text-white ring-rose-100/40' }}">
                                {{ $seatCategory->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                LKR {{ number_format($seatCategory->seat_price) }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                {{ $seatCategory->no_of_seats }} {{ __('Seats') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                    <form
                        action="{{ route('organizer.seat-categories.update', [$event->id, $seatCategory->id]) }}"
                        method="POST" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Category Details') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Update seating information, pricing and booking availability.') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="name" :value="__('Category Name')" />
                                    <x-text-input id="name"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="name" :value="old('name', $seatCategory->name)" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="description" :value="__('Description')" />
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $seatCategory->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="no_of_seats" :value="__('Total Seats')" />
                                    <x-text-input id="no_of_seats"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number" name="no_of_seats" min="1"
                                        :value="old('no_of_seats', $seatCategory->no_of_seats)" required />
                                    <x-input-error :messages="$errors->get('no_of_seats')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="seat_price" :value="__('Seat Price (LKR)')" />
                                    <x-text-input id="seat_price"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number" name="seat_price" min="0" step="1"
                                        :value="old('seat_price', $seatCategory->seat_price)" required />
                                    <x-input-error :messages="$errors->get('seat_price')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="booking_start" :value="__('Booking Start')" />
                                    <x-text-input id="booking_start"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="datetime-local" name="booking_start"
                                        :value="old('booking_start', optional($seatCategory->booking_start)->format('Y-m-d\TH:i'))" />
                                    <x-input-error :messages="$errors->get('booking_start')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="booking_end" :value="__('Booking End')" />
                                    <x-text-input id="booking_end"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="datetime-local" name="booking_end"
                                        :value="old('booking_end', optional($seatCategory->booking_end)->format('Y-m-d\TH:i'))" />
                                    <x-input-error :messages="$errors->get('booking_end')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="ticket_color" :value="__('Ticket Color')" />
                                    <input id="ticket_color" type="color" name="ticket_color"
                                        value="{{ old('ticket_color', $seatCategory->ticket_color) }}"
                                        class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <x-input-error :messages="$errors->get('ticket_color')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="is_active" :value="__('Status')" />
                                    <select id="is_active" name="is_active"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="1"
                                            {{ old('is_active', $seatCategory->is_active) ? 'selected' : '' }}>
                                            {{ __('Active') }}
                                        </option>
                                        <option value="0"
                                            {{ !old('is_active', $seatCategory->is_active) ? 'selected' : '' }}>
                                            {{ __('Inactive') }}
                                        </option>
                                    </select>
                                    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('organizer.events.show', $event->id) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button
                                class="justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:ring-indigo-500">
                                {{ __('Update Seat Category') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ __('Event Information') }}
                            </h4>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ __('Event Name') }}</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">{{ $event->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Venue') }}</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">{{ $event->place }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('Date') }}</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">{{ $event->date }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ __('Category Summary') }}
                            </h4>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Available Seats') }}</dt>
                                    <dd class="font-semibold text-gray-900">{{ $seatCategory->no_of_available_seats }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Price') }}</dt>
                                    <dd class="font-semibold text-gray-900">LKR {{ number_format($seatCategory->seat_price) }}</dd>
                                </div>
                                <div class="flex items-center gap-3">
                                    <dt class="text-gray-500">{{ __('Ticket Color') }}</dt>
                                    <dd class="ml-auto flex items-center gap-2">
                                        <span class="inline-block h-5 w-5 rounded-full ring-1 ring-gray-200"
                                            style="background-color: {{ $seatCategory->ticket_color }}"></span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                            <div class="flex gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-indigo-950">{{ __('Review before updating') }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-indigo-800">
                                        {{ __('Reducing total seats below existing bookings may cause availability conflicts.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

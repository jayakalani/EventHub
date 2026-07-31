<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">
                    Event Management
                </p>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">
                    Create New Event
                </h2>
            </div>

            <a href="{{ route('organizer.events.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Events
            </a>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 overflow-hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-red-800">Event Creation Failed</h3>
                            <ul class="mt-1 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('status') === 'event-created')
                <div class="mb-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-emerald-800">Event Created</h3>
                            <p class="text-sm text-emerald-700">Your event has been successfully created.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-lg shadow-indigo-100/40 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 shadow-inner ring-1 ring-white/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold tracking-tight sm:text-xl">New Event Details</h3>
                            <p class="mt-0.5 text-sm text-indigo-50">
                                Fill in the essentials to publish a polished event listing.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 p-5 lg:grid-cols-[1fr_280px] lg:p-6">
                    <form method="POST" action="{{ route('organizer.events.store') }}" enctype="multipart/form-data"
                        class="space-y-5">
                        @csrf

                        {{-- Basics --}}
                        <section class="space-y-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Basic Information</h4>
                                <p class="mt-0.5 text-xs text-gray-500">Name, host, and category for this event.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Event Name</label>
                                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="hosted_by" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Hosted By</label>
                                    <select id="hosted_by" name="hosted_by"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Host Person</option>
                                        @foreach ($hosts as $host)
                                            <option value="{{ $host->id }}" {{ old('hosted_by') == $host->id ? 'selected' : '' }}>
                                                {{ $host->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hosted_by')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="category_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</label>
                                    <select id="category_id" name="category_id"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Category</option>
                                        @foreach ($event_categories as $event_category)
                                            <option value="{{ $event_category->id }}" {{ old('category_id') == $event_category->id ? 'selected' : '' }}>
                                                {{ $event_category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        {{-- Schedule & Venue --}}
                        <section class="space-y-3 border-t border-gray-100 pt-5">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Schedule & Venue</h4>
                                <p class="mt-0.5 text-xs text-gray-500">When and where the event takes place.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Date</label>
                                    <input id="date" type="date" name="date" value="{{ old('date') }}"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                    @error('date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="time" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Time</label>
                                    <input id="time" type="time" name="time" value="{{ old('time') }}"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                    @error('time')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <label for="place" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Place</label>
                                    <input id="place" type="text" name="place" value="{{ old('place') }}"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                    @error('place')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        {{-- Capacity & Contact --}}
                        <section class="space-y-3 border-t border-gray-100 pt-5">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Capacity & Contact</h4>
                                <p class="mt-0.5 text-xs text-gray-500">Ticket capacity and customer relations contact.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="no_of_tickets" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Number of tickets</label>
                                    <input id="no_of_tickets" type="number" name="no_of_tickets" value="{{ old('no_of_tickets') }}"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                    @error('no_of_tickets')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <label for="contact_person" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Customer Relations Officer</label>
                                    <select id="contact_person" name="contact_person"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Contact Person</option>
                                        @foreach ($croUsers as $croUser)
                                            <option value="{{ $croUser->id }}" {{ old('contact_person') == $croUser->id ? 'selected' : '' }}>
                                                {{ $croUser->first_name }} {{ $croUser->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contact_person')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <label for="description" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Event Description</label>
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        @include('organizer.events.partials.refund-policy-fields')

                        {{-- Cover --}}
                        <section class="space-y-3 border-t border-gray-100 pt-5">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Cover Image</h4>
                                <p class="mt-0.5 text-xs text-gray-500">Upload a banner attendees will see first.</p>
                            </div>

                            <div
                                class="rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 p-5 text-center transition hover:border-indigo-300 hover:bg-indigo-50/70">
                                <svg class="mx-auto h-9 w-9 text-indigo-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <label for="cover"
                                    class="mt-3 inline-flex cursor-pointer items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                    Choose Cover Image
                                </label>
                                <input id="cover" type="file" name="cover"
                                    class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-50"
                                    accept=".jpg,.jpeg,.png" required>
                                <p class="mt-2 text-xs text-gray-500">Accepted file types: JPG, JPEG, PNG | Max size: 2MB</p>
                                @error('cover')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </section>

                        <div
                            class="flex flex-col-reverse gap-2.5 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('organizer.events.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Save Event
                            </button>
                        </div>
                    </form>

                    <aside class="space-y-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Before you save</h4>
                            <ul class="mt-3 space-y-2.5 text-sm text-gray-600">
                                <li class="flex gap-2">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">1</span>
                                    Choose a clear event name and matching category.
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">2</span>
                                    Double-check date, time, and venue details.
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">3</span>
                                    Add ticket categories after creating the event.
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                            <div class="flex gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-indigo-950">Cover tip</h4>
                                    <p class="mt-0.5 text-sm leading-5 text-indigo-800">
                                        Use a wide landscape image so the event looks great on both desktop and mobile.
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

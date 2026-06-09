<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('Event Management') }}
                </p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ __('Edit Event') }}
                </h2>
            </div>

            <a href="{{ route('organizer.events.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('Back to Events') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-xl shadow-indigo-100/50 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold uppercase shadow-inner ring-1 ring-white/25">
                                {{ strtoupper(substr($event->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold tracking-tight">
                                    {{ $event->name }}
                                </h3>
                                <p class="mt-1 text-sm text-indigo-50">
                                    {{ $event->place }} &middot; {{ $event->date }} {{ $event->time }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                {{ $event->no_of_tickets }} {{ __('tickets') }}
                            </span>
                            @if ($event->eventCategory)
                                <span
                                    class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                    {{ $event->eventCategory->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                    <form method="POST" action="{{ route('organizer.events.update', $event->id) }}"
                        enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Event Details') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Update event information, schedule, and cover image.') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="name" :value="__('Event Name')" />
                                    <x-text-input id="name"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="name" :value="old('name', $event->name)" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="hosted_by" :value="__('Hosted By')" />
                                    <select id="hosted_by" name="hosted_by"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">{{ __('Select Host Person') }}</option>
                                        @foreach ($hosts as $host)
                                            <option value="{{ $host->id }}"
                                                {{ old('hosted_by', $event->hosted_by) == $host->id ? 'selected' : '' }}>
                                                {{ $host->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('hosted_by')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="category_id" :value="__('Category')" />
                                    <select id="category_id" name="category_id"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach ($event_categories as $event_category)
                                            <option value="{{ $event_category->id }}"
                                                {{ old('category_id', $event->category_id) == $event_category->id ? 'selected' : '' }}>
                                                {{ $event_category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="date" :value="__('Date')" />
                                    <x-text-input id="date"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="date" name="date" :value="old('date', $event->date)" required />
                                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="time" :value="__('Time')" />
                                    <x-text-input id="time"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="time" name="time" :value="old('time', $event->time)" required />
                                    <x-input-error :messages="$errors->get('time')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="place" :value="__('Place / Venue')" />
                                    <x-text-input id="place"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="place" :value="old('place', $event->place)" required />
                                    <x-input-error :messages="$errors->get('place')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="no_of_tickets" :value="__('Number of tickets')" />
                                    <x-text-input id="no_of_tickets"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number" name="no_of_tickets" min="1"
                                        :value="old('no_of_tickets', $event->no_of_tickets)" required />
                                    <x-input-error :messages="$errors->get('no_of_tickets')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="contact_person" :value="__('Customer Relations Officer')" />
                                    <select id="contact_person" name="contact_person"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">{{ __('Select Contact Person') }}</option>
                                        @foreach ($croUsers as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('contact_person', $event->contact_person) == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="description" :value="__('Event Description')" />
                                    <textarea id="description" name="description" rows="5" required
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $event->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="cover" :value="__('Cover Image')" />

                                    @if ($event->cover)
                                        <div class="mt-3 mb-4">
                                            <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Current Cover') }}</p>
                                            <img src="{{ asset('storage/' . $event->cover) }}" alt="{{ __('Current Cover') }}"
                                                class="h-32 w-48 rounded-xl object-cover shadow-sm ring-1 ring-gray-200">
                                        </div>
                                    @endif

                                    <div
                                        class="mt-2 rounded-2xl border-2 border-dashed border-gray-200 bg-white p-6 text-center transition hover:border-indigo-300">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div class="mt-3">
                                            <label for="cover"
                                                class="cursor-pointer text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                {{ __('Upload New Cover') }}
                                            </label>
                                            <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png"
                                                class="hidden">
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ __('JPG, JPEG or PNG up to 2MB') }}
                                            </p>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('cover')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('organizer.events.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button
                                class="justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:ring-indigo-500">
                                {{ __('Update Event') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ __('Event Summary') }}
                            </h4>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Event ID') }}</dt>
                                    <dd class="font-semibold text-gray-900">#{{ $event->id }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Host') }}</dt>
                                    <dd class="font-semibold text-gray-900 text-right">
                                        {{ $event->host?->name ?? __('Not assigned') }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Category') }}</dt>
                                    <dd class="font-semibold text-gray-900 text-right">
                                        {{ $event->eventCategory?->name ?? __('Not assigned') }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Created') }}</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $event->created_at?->format('M d, Y') }}
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
                                        {{ __('Changing the date, venue, or ticket count may affect existing bookings and ticket categories.') }}
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

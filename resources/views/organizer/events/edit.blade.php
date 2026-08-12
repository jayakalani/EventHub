<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">
                    Event Management
                </p>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">
                    Edit Event
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
                            <h3 class="font-semibold text-red-800">Update Failed</h3>
                            <ul class="mt-1 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if ($event->isPostponed())
                <div class="mb-4 overflow-hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-800">POSTPONED</p>
                    <p class="mt-1 text-sm text-amber-700">{{ $event->postponementScheduleLabel() }}</p>
                    <p class="mt-1 text-sm text-amber-700">
                        Changing the event date or time will update the postponed schedule. Status stays <strong>Postponed</strong>. Cancel the event only if it will not happen.
                    </p>
                </div>
            @endif

            <div
                class="overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-lg shadow-indigo-100/40 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-5 py-5 text-white sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-lg font-bold uppercase shadow-inner ring-1 ring-white/25">
                                {{ strtoupper(substr($event->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-bold tracking-tight sm:text-xl">
                                    {{ $event->name }}
                                </h3>
                                <p class="mt-0.5 text-sm text-indigo-50">
                                    {{ $event->place }} &middot; {{ $event->date }} {{ $event->time }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                {{ $event->no_of_tickets }} tickets
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

                <div class="grid gap-6 p-5 lg:grid-cols-[1fr_280px] lg:p-6">
                    <form method="POST" action="{{ route('organizer.events.update', $event->id) }}"
                        enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Basics --}}
                        <section class="space-y-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Event Details</h4>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Update event information, schedule, and cover image.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="name" value="Event Name" />
                                    <x-text-input id="name"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="name" :value="old('name', $event->name)" required :title-case="true" />
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="host_id" value="Host" />
                                    <select id="host_id" name="host_id"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Host</option>
                                        @foreach ($hosts as $host)
                                            <option value="{{ $host->id }}"
                                                {{ old('host_id', $event->host_id) == $host->id ? 'selected' : '' }}>
                                                {{ $host->name }}{{ $host->is_active ? '' : ' (Inactive)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('host_id')" class="mt-1" />
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="category_id" value="Category" />
                                    <select id="category_id" name="category_id"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Category</option>
                                        @foreach ($event_categories as $event_category)
                                            <option value="{{ $event_category->id }}"
                                                data-allows-artists="{{ $event_category->allows_artists ? '1' : '0' }}"
                                                {{ old('category_id', $event->category_id) == $event_category->id ? 'selected' : '' }}>
                                                {{ $event_category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                                </div>

                                @php
                                    $selectedArtistIds = collect(old('artist_ids', $event->artists->pluck('id')->all()));
                                @endphp
                                <div id="artists-field"
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2 hidden">
                                    <x-input-label value="Artists" />
                                    <p class="mt-1 text-xs text-gray-500">Select one or more artists (Music &amp; Entertainment only).</p>
                                    <div id="artist-ids" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @forelse ($artists as $artist)
                                            <label class="group flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500">
                                                <input type="checkbox"
                                                    name="artist_ids[]"
                                                    value="{{ $artist->id }}"
                                                    class="artist-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    {{ $selectedArtistIds->contains($artist->id) ? 'checked' : '' }}>
                                                <span class="min-w-0 flex-1 font-medium text-gray-800">{{ $artist->name }}{{ $artist->is_active ? '' : ' (Inactive)' }}</span>
                                                <span class="shrink-0 rounded-lg bg-gray-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500 group-has-[:checked]:bg-indigo-600 group-has-[:checked]:text-white">
                                                    Select
                                                </span>
                                            </label>
                                        @empty
                                            <p class="text-sm text-gray-500 sm:col-span-2">No active artists available. Add artists first.</p>
                                        @endforelse
                                    </div>
                                    <x-input-error :messages="$errors->get('artist_ids')" class="mt-1" />
                                    <x-input-error :messages="$errors->get('artist_ids.*')" class="mt-1" />
                                </div>

                                <div class="md:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/40 p-3"
                                    x-data="{ scheduleTba: {{ old('schedule_tba', $event->isUpcomingScheduleTba() ? '1' : '0') == '1' ? 'true' : 'false' }} }">
                                    @unless ($event->isPostponed())
                                        <label class="flex items-start gap-3 text-sm text-slate-700">
                                            <input type="hidden" name="schedule_tba" value="0">
                                            <input type="checkbox"
                                                name="schedule_tba"
                                                value="1"
                                                class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                x-model="scheduleTba"
                                                @checked(old('schedule_tba', $event->isUpcomingScheduleTba() ? '1' : '0') == '1')>
                                            <span>
                                                <span class="font-semibold">Place, date &amp; time not decided yet</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">Leave schedule undecided (same idea as postponed TBA).</span>
                                            </span>
                                        </label>
                                    @endunless

                                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2" @unless($event->isPostponed()) x-show="!scheduleTba" x-cloak @endunless>
                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                            <x-input-label for="date" value="Date" />
                                            <x-text-input id="date"
                                                class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="date" name="date" :value="old('date', $event->date)" />
                                            <x-input-error :messages="$errors->get('date')" class="mt-1" />
                                        </div>

                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                            <x-input-label for="time" value="Time" />
                                            <x-text-input id="time"
                                                class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="time" name="time" :value="old('time', $event->time)" />
                                            <x-input-error :messages="$errors->get('time')" class="mt-1" />
                                        </div>

                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                            <x-input-label for="place" value="Place / Venue" />
                                            <x-text-input id="place"
                                                class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="text" name="place" :value="old('place', $event->place)" :title-case="true" />
                                            <x-input-error :messages="$errors->get('place')" class="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="no_of_tickets" value="Number of tickets" />
                                    <x-text-input id="no_of_tickets"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="number" name="no_of_tickets" min="1"
                                        :value="old('no_of_tickets', $event->no_of_tickets)" required />
                                    <x-input-error :messages="$errors->get('no_of_tickets')" class="mt-1" />
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="contact_person" value="Customer Relations Officer" />
                                    <select id="contact_person" name="contact_person"
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Contact Person</option>
                                        @foreach ($croUsers as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('contact_person', $event->contact_person) == $user->id ? 'selected' : '' }}>
                                                {{ $user->first_name }} {{ $user->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="description" value="Event Description" />
                                    <textarea id="description" name="description" rows="4" required
                                        class="mt-1.5 block w-full rounded-xl border-gray-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $event->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="cover" value="Cover Image" />

                                    @if ($event->cover)
                                        <div class="mb-3 mt-2">
                                            <p class="mb-1.5 text-xs font-medium text-gray-500">Current Cover</p>
                                            <img src="{{ asset('storage/' . $event->cover) }}" alt="Current Cover"
                                                class="h-28 w-44 rounded-xl object-cover shadow-sm ring-1 ring-gray-200">
                                        </div>
                                    @endif

                                    <div
                                        class="mt-1.5 rounded-xl border-2 border-dashed border-gray-200 bg-white p-5 text-center transition hover:border-indigo-300">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div class="mt-2">
                                            <label for="cover"
                                                class="cursor-pointer text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                Upload New Cover
                                            </label>
                                            <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png"
                                                class="hidden">
                                            <p class="mt-1 text-xs text-gray-500">
                                                JPG, JPEG or PNG up to 2MB
                                            </p>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('cover')" class="mt-1" />
                                </div>
                            </div>
                        </section>

                        @include('organizer.events.partials.refund-policy-fields', ['event' => $event])

                        <div
                            class="flex flex-col-reverse gap-2.5 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('organizer.events.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Cancel
                            </a>
                            <x-primary-button
                                class="justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold shadow-sm transition hover:bg-indigo-700 focus:ring-indigo-500">
                                Update Event
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="space-y-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Event Summary
                            </h4>
                            <dl class="mt-3 space-y-2.5 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Event ID</dt>
                                    <dd class="font-semibold text-gray-900">#{{ $event->id }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Host</dt>
                                    <dd class="text-right font-semibold text-gray-900">
                                        {{ $event->host?->name ?? 'Not assigned' }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Category</dt>
                                    <dd class="text-right font-semibold text-gray-900">
                                        {{ $event->eventCategory?->name ?? 'Not assigned' }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Created</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $event->created_at?->format('M d, Y') }}
                                    </dd>
                                </div>
                            </dl>
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
                                    <h4 class="text-sm font-semibold text-indigo-950">Review before updating</h4>
                                    <p class="mt-0.5 text-sm leading-5 text-indigo-800">
                                        Changing the date, venue, or ticket count may affect existing bookings and ticket categories.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const categorySelect = document.getElementById('category_id');
            const artistsField = document.getElementById('artists-field');
            const artistCheckboxes = () => document.querySelectorAll('.artist-checkbox');

            if (!categorySelect || !artistsField) {
                return;
            }

            const syncArtistsVisibility = () => {
                const selected = categorySelect.options[categorySelect.selectedIndex];
                const allows = selected && selected.dataset.allowsArtists === '1';
                artistsField.classList.toggle('hidden', !allows);
                artistCheckboxes().forEach((checkbox) => {
                    checkbox.disabled = !allows;
                    if (!allows) {
                        checkbox.checked = false;
                    }
                });
            };

            categorySelect.addEventListener('change', syncArtistsVisibility);
            syncArtistsVisibility();
        })();
    </script>
</x-app-layout>

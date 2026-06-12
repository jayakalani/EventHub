<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="mt-1 text-sm text-gray-500">
                    Discover upcoming events and book your next experience.
                </p>
            </div>

            <!-- Settings Dropdown -->
            <div x-data="{ open: false }" class="relative inline-block text-left mb-8">

                <button @click="open = !open"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">
                    Settings
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false"
                    class="absolute right-0 mt-2 w-80 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 p-6 z-50">

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm mb-4">
                        <p class="text-sm font-medium text-slate-500">Available Events</p>
                        <h3 class="mt-2 text-2xl font-semibold text-blue-600">{{ $events->count() }}</h3>
                        <p class="text-sm text-slate-500">Currently active events</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">My Bookings</p>
                        <h3 class="mt-2 text-2xl font-semibold text-green-600">{{ $myBookings ?? 0 }}</h3>
                        <p class="text-sm text-slate-500">Tickets booked</p>
                    </div>

                </div>
            </div>

            <div class="text-sm text-gray-600">
                Welcome,
                <span class="font-semibold">{{ Auth::user()->first_name }}</span>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (! empty($selectedHost))
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4">
                    <p class="text-sm text-indigo-900">
                        {{ __('Showing events hosted by') }}
                        <span class="font-semibold">{{ $selectedHost->name }}</span>
                    </p>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                        {{ __('Clear host filter') }}
                    </a>
                </div>
            @endif

            <!-- HERO -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 p-8 mb-8 text-white shadow">

                <div class="max-w-2xl">

                    <h1 class="text-3xl font-bold">
                        Discover Amazing Events
                    </h1>

                    <p class="mt-3 text-indigo-100">
                        Explore concerts, workshops, conferences, sports events and more.
                    </p>

                    <form action="{{ route('attendee.dashboard') }}" method="GET" class="mt-6">

                        <div class="grid gap-3 md:grid-cols-4">

                            @if (request('host'))
                                <input type="hidden" name="host" value="{{ request('host') }}">
                            @endif

                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Event Name" class="rounded-2xl border-0 px-4 py-3 text-slate-800 shadow">

                            <input type="date" name="date" value="{{ request('date') }}"
                                class="rounded-2xl border-0 px-4 py-3 text-slate-800 shadow">

                            <button type="submit"
                                class="rounded-2xl bg-white px-6 py-3 font-semibold text-indigo-600 shadow hover:bg-slate-100 transition">
                                Search
                            </button>

                        </div>

                    </form>

                </div>
            </div>

            @include('partials.events-browse')

            {{-- Submit Complaint --}}
            <section class="mt-12 rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Submit a Complaint</h2>
                            <p class="text-sm text-slate-500">Report an issue with your experience. Attach screenshots or PDFs if helpful.</p>
                        </div>
                    </div>
                    <a href="{{ route('attendee.support.index', ['tab' => 'complaints']) }}"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        View my complaints
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                        <ul class="list-disc pl-5 space-y-1 text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('attendee.complaints.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="complaint-subject" class="block text-sm font-semibold text-slate-700 mb-1">Subject</label>
                        <input type="text" id="complaint-subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                            placeholder="Brief summary of your complaint"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="complaint-message" class="block text-sm font-semibold text-slate-700 mb-1">Message</label>
                        <textarea id="complaint-message" name="message" rows="4" required minlength="10" maxlength="2000"
                            placeholder="Describe your complaint in detail..."
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                    </div>
                    <div>
                        <label for="complaint-attachments" class="block text-sm font-semibold text-slate-700 mb-1">
                            Attachments <span class="font-normal text-slate-500">(optional — JPG, PNG, PDF, max 5 MB each, up to 5 files)</span>
                        </label>
                        <input type="file" id="complaint-attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                            <i class="bi bi-send" aria-hidden="true"></i>
                            Submit Complaint
                        </button>
                    </div>
                </form>
            </section>

            @if ($pastEvents->isNotEmpty())
                <div class="mt-12">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-900">Past Events</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Completed events you can still revisit for details, tickets, and feedback.
                        </p>
                    </div>

                    @include('partials.events-browse', [
                        'events' => $pastEvents,
                        'browseSection' => 'past',
                    ])
                </div>
            @endif

        </div>
    </div>

</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="font-semibold text-red-800">
                                Something went wrong
                            </h3>

                            <ul class="mt-2 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Success Message --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                    class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm">
                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-3">

                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
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

                        <button @click="show = false" class="text-green-600 hover:text-green-800">
                            ✕
                        </button>

                    </div>
                </div>
            @endif
            <h2 class="text-2xl font-bold text-gray-800">
                {{ __('Event Details') }}
            </h2>


            <div class="flex gap-3">
                <a href="{{ route('organizer.seat-categories.create', $event->id) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 transition">
                    + Add Seat Category
                </a>

                <a href="{{ route('organizer.events.exportPdf', $event->id) }}"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 transition">
                    Export PDF
                </a>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- EVENT CARD --}}
            <div class="bg-white rounded-xl shadow-md overflow-hidden">

                {{-- Cover --}}
                @if ($event->cover)
                    <img src="{{ asset('uploads/covers/events/' . $event->cover) }}" class="w-full h-64 object-cover">
                @endif

                <div class="p-6 space-y-6">

                    {{-- Title + Meta --}}
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ $event->name }}
                            </h1>

                            <p class="text-gray-500 mt-1">
                                {{ $event->place }} • {{ $event->date }} {{ $event->time }}
                            </p>
                        </div>

                        <span
                            class="px-3 py-1 text-sm rounded-full
                            {{ $event->status === 'ongoing' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($event->status) }}
                        </span>
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Hosted By</p>
                            <p class="font-semibold">{{ $event->host->name ?? 'N/A' }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Category</p>
                            <p class="font-semibold">{{ $event->eventCategory->name ?? 'N/A' }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Total Seats</p>
                            <p class="font-semibold">{{ $event->no_of_seats }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Contact Person</p>
                            <p class="font-semibold">{{ $event->contactPerson->name ?? 'N/A' }}</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-lg md:col-span-2">
                            <p class="text-gray-500">Description</p>
                            <p class="font-medium text-gray-700">
                                {{ $event->description }}
                            </p>
                        </div>

                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <a href="{{ route('organizer.events.edit', $event->id) }}"
                            class="inline-flex items-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                            </svg>
                            Edit Event
                        </a>

                        <form action="{{ route('organizer.events.destroy', $event->id) }}" method="POST"
                            onsubmit="return confirm('Delete this event?')" class="inline">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7H5m5 4v6m4-6v6m-7-10l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12M10 7V4a1 1 0 011-1h2a1 1 0 011 1v3" />
                                </svg>
                                Delete Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            {{-- SEAT CATEGORIES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">

                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            Seat Categories
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Manage ticket categories, pricing and availability.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50">

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Category
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Seats
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Available
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Price
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Color
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th
                                    class="text-left px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Booking Period
                                </th>

                                <th
                                    class="text-center px-4 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($seatCategories as $category)
                                <tr class="hover:bg-slate-50 transition">

                                    {{-- Category --}}
                                    <td class="px-4 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $category->name }}
                                        </div>

                                        @if ($category->description)
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ $category->description }}
                                            </div>
                                        @endif

                                    </td>

                                    {{-- Seats --}}
                                    <td class="px-4 py-4 text-slate-700">
                                        {{ $category->no_of_seats }}
                                    </td>

                                    {{-- Available --}}
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                            {{ $category->no_of_available_seats }}
                                        </span>
                                    </td>

                                    {{-- Price --}}
                                    <td class="px-4 py-4 font-semibold text-slate-900">
                                        Rs {{ number_format($category->seat_price, 2) }}
                                    </td>

                                    {{-- Color --}}
                                    <td class="px-4 py-4">

                                        <div class="flex items-center gap-2">

                                            <div class="w-5 h-5 rounded-full border border-slate-300"
                                                style="background-color: {{ $category->ticket_color }}">
                                            </div>

                                            <span class="text-sm text-slate-600">
                                                {{ $category->ticket_color }}
                                            </span>

                                        </div>

                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-4">

                                        @if ($category->is_active)
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>

                                    {{-- Booking Period --}}
                                    <td class="px-4 py-4 text-sm text-slate-600">

                                        @if ($category->booking_start && $category->booking_end)
                                            <div>
                                                {{ \Carbon\Carbon::parse($category->booking_start)->format('d M Y') }}
                                            </div>

                                            <div class="text-xs text-slate-400">
                                                to
                                            </div>

                                            <div>
                                                {{ \Carbon\Carbon::parse($category->booking_end)->format('d M Y') }}
                                            </div>
                                        @else
                                            -
                                        @endif

                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-4">

                                        <div class="flex items-center justify-center gap-2">

                                            {{-- Edit --}}
                                            <a href="{{ route('organizer.seat-categories.edit', [$event, $category]) }}"
                                                class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-2 text-xs font-medium text-white hover:bg-amber-600 transition">

                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">

                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                                                </svg>

                                                Edit

                                            </a>

                                            {{-- Delete --}}
                                            <form
                                                action="{{ route('organizer.seat-categories.destroy', [$event->id, $category->id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this seat category?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700 transition">

                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">

                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7H5m5 4v6m4-6v6m-7-10l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12M10 7V4a1 1 0 011-1h2a1 1 0 011 1v3" />
                                                    </svg>

                                                    Delete

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="py-12 text-center">

                                        <div class="text-slate-400">
                                            No seat categories added yet.
                                        </div>

                                        <a href="{{ route('organizer.seat-categories.create', $event->id) }}"
                                            class="inline-flex mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                            Add First Category
                                        </a>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>

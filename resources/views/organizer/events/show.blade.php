<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Event Details') }}
            </h2>
            <div>
                <a href="{{ route('organizer.seat-categories.create', $event->id) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    + Add New Seat Category
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    {{-- Cover Photo --}}
                    @if($event->cover)
                        <div class="mb-4">
                            <img src="{{ asset('storage/'.$event->cover) }}" alt="Cover Photo"
                                 class="w-full max-w-md rounded shadow">
                        </div>
                    @endif

                    {{-- Event Details --}}
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold mb-2">{{ $event->name }}</h2>
                        <p><strong>Hosted By:</strong> {{ $event->host->name ?? 'N/A' }}</p>
                        <p><strong>Category:</strong> {{ $event->eventCategory->name ?? 'N/A' }}</p>
                        <p><strong>Date:</strong> {{ $event->date }} {{ $event->time }}</p>
                        <p><strong>Place:</strong> {{ $event->place }}</p>
                        <p><strong>Total Seats:</strong> {{ $event->no_of_seats }}</p>
                        <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
                        <p><strong>Contact Person:</strong> {{ $event->contactPerson->name ?? 'N/A' }}</p>
                        <p><strong>Description:</strong> {{ $event->description }}</p>
                    </div>

                    {{-- Seat Categories --}}
                    <h3 class="text-xl font-semibold mb-3">Seat Categories</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Seats</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available Seats</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket Color</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking Start</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking End</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($seatCategories as $category)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->description ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->no_of_seats }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->no_of_available_seats }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($category->seat_price, 2) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <span class="px-2 py-1 rounded text-white"
                                                  style="background-color: {{ $category->ticket_color }}">
                                                {{ $category->ticket_color }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->is_active ? 'Yes' : 'No' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->booking_start ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $category->booking_end ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No seat categories added yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Export Buttons --}}
                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('organizer.events.exportPdf', $event->id) }}"
                           class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Export PDF
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

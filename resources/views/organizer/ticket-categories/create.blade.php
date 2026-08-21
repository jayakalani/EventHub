<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New ticket Category') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    {{-- Validation Errors --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ticket Category Form --}}
                    <form action="{{ route('organizer.ticket-categories.store', $event->id) }}" method="POST"
                        class="space-y-6">
                        @csrf

                        {{-- Event (bound from route; not editable) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event</label>
                            <div class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-900 shadow-sm">
                                {{ $event->name }}
                            </div>
                        </div>

                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Category Name</label>
                            <input type="text" name="name" id="name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required data-title-case>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>

                        {{-- Total tickets --}}
                        <div>
                            <label for="no_of_tickets" class="block text-sm font-medium text-gray-700">Total tickets</label>
                            <input type="number" name="no_of_tickets" id="no_of_tickets" min="1"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        {{-- ticket Price --}}
                        <div>
                            <label for="ticket_price" class="block text-sm font-medium text-gray-700">ticket Price</label>
                            <input type="number" step="0" name="ticket_price" id="ticket_price" min="0"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        {{-- Timed discount (optional) --}}
                        
                        {{-- Ticket Color --}}
                        <div>
                            <label for="ticket_color" class="block text-sm font-medium text-gray-700">Ticket
                                Color</label>
                            <input type="text" name="ticket_color" id="ticket_color"
                                placeholder="e.g. red or #FF0000"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        {{-- Active Status --}}
                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700">Active</label>
                            <select name="is_active" id="is_active"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="1" selected>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        {{-- Booking Start --}}
                        <div>
                            <label for="booking_start" class="block text-sm font-medium text-gray-700">Booking
                                Start</label>
                            <input type="datetime-local" name="booking_start" id="booking_start"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        {{-- Booking End --}}
                        <div>
                            <label for="booking_end" class="block text-sm font-medium text-gray-700">Booking End</label>
                            <input type="datetime-local" name="booking_end" id="booking_end"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        {{-- Submit --}}
                        <div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Create ticket Category
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

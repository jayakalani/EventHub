<x-app-layout>
    <x-slot name="header">
        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('All Events') }}
                </h2>
            </div>

            <div>
                <a href="{{ route('organizer.events.create') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 mx-2">
                    + Create New Event
                </a>

                <a href="{{ route('organizer.events.export.csv') }}"
                   class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Export CSV</a>
                <a href="{{ route('organizer.events.export.pdf') }}"
                   class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Export PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="flex flex-col md:flex-row justify-between md:w-auto mb-6">
            <div class="md:w-auto mb-4 md:mb-0">
                <button @click="openFilters = !openFilters"
                        class="md:hidden px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Filters
                </button>

                <div :class="{'block': openFilters, 'hidden': !openFilters}" class="hidden md:flex flex-wrap gap-4 mt-4 md:mt-3">
                    <form method="GET" action="{{ route('organizer.events.index') }}" class="flex flex-wrap gap-4 mt-4 md:mt-0">
                        <input type="text" name="search" placeholder="Search name, place, host"
                               value="{{ request('search') }}"
                               class="px-4 py-2 border rounded w-full md:w-64">

                        <select name="status" class="px-8 py-2 border rounded w-full md:w-auto">
                            <option value="">All Statuses</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>

                        <!-- Date range -->
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-4 py-2 border rounded w-full md:w-auto">
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-4 py-2 border rounded w-full md:w-auto">

                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Apply</button>
                        <a href="{{ route('organizer.events.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hosted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seats</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($events as $event)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->host->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->eventCategory->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->date }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->time }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->place }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $event->no_of_seats }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($event->status) }}</td>
                                    <td>
                                        <form action="{{ route('organizer.events.updateStatus', $event->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="border rounded px-6 py-1">
                                                <option value="upcoming" {{ $event->status == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                                                <option value="ongoing" {{ $event->status == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                                <option value="completed" {{ $event->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ $event->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <a href="{{ route('organizer.events.show', $event->id) }}" class="btn btn-info btn-sm">View</a>
                                        <a href="{{ route('organizer.events.edit', $event->id) }}" class="text-blue-600 hover:text-blue-900 mr-2">Edit</a>
                                        <form action="{{ route('organizer.events.destroy', $event->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this event?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $events->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

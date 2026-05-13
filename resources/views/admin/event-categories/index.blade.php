<x-app-layout>
    <x-slot name="header">        

        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('All Event Categories') }}
                </h2>
            </div>

            <div> 
                <a href="{{ route('admin.event.category.create') }}" 
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 mx-2">
                    + Create New Event Category
                </a>

                <a href="{{ route('admin.event-categories.export.csv') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Export CSV</a>
                <a href="{{ route('admin.event-categories.export.pdf') }}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Export PDF</a>

            </div>  
        </div>  
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between md:w-auto mb-6">
            <!-- Filters -->
            <div class=" md:w-auto mb-4 md:mb-0">
                <!-- Mobile toggle -->
                <button @click="openFilters = !openFilters" 
                        class="md:hidden px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Filters
                </button>   

                <div :class="{'block': openFilters, 'hidden': !openFilters}" class="hidden md:flex flex-wrap gap-4 mt-4 md:mt-3">
                    <form method="GET" action="{{ route('admin.event-categories.index') }}" class="flex flex-wrap gap-4 mt-4 md:mt-0">
                        <input type="text" name="search" placeholder="Search event category name"
                            value="{{ request('search') }}"
                            class="px-4 py-2 border rounded w-full md:w-64">


                        <select name="status" class="px-10 py-2 border rounded w-full md:w-auto">
                            <option value="">Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <!-- Date range -->
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-4 py-2 border rounded w-full md:w-auto">
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-4 py-2 border rounded w-full md:w-auto">

                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Apply</button>
                        <a href="{{ route('admin.event-categories.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Reset</a>
                    </form>
                </div>    
            </div>
        </div>
    </div>

        <div class="w-full py-12">
            <div class=" mx-auto sm:px-6 lg:px-8">
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Category Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($event_categories as $event_category)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $event_category->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $event_category->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $event_category->creator->first_name }} {{ $event_category->creator->last_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                                                            
                                            <!-- Active/Inactive Toggle Button -->
                                            <form action="{{ route('admin.event.category.toggleActive', $event_category->id) }}" method="POST" class="inline ml-1">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 text-xs rounded {{ $event_category->is_active ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-gray-500 text-white hover:bg-gray-600' }}">
                                                    {{ $event_category->is_active ? '✅ Active' : '❌ Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <!-- Edit Button -->
                                            <a href="{{ route('admin.event.category.edit', $event_category->id) }}" class="text-blue-600 hover:text-blue-900 mr-2 inline-block">
                                                Edit
                                            </a>
                                                
                                            <!-- Delete Button -->
                                            <form action="{{ route('admin.event.category.destroy', $event_category->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this event category?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
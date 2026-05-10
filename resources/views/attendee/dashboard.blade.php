<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendee Dashboard') }}
        </h2>
        <span class="text-gray-600">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</span>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Stats Cards -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-3xl font-bold text-blue-600">0</div>
                        <div class="text-gray-600 mt-2">Upcoming Events</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-3xl font-bold text-green-600">0</div>
                        <div class="text-gray-600 mt-2">Registered Events</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-3xl font-bold text-purple-600">0</div>
                        <div class="text-gray-600 mt-2">Past Events</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="#" class="bg-blue-600 text-white rounded-lg p-4 text-center hover:bg-blue-700">
                            Browse Events
                        </a>
                        <a href="#" class="bg-green-600 text-white rounded-lg p-4 text-center hover:bg-green-700">
                            My Registrations
                        </a>
                        <a href="#" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                            Event History
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-app-layout>

 
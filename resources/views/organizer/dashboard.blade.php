<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard - EventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Organizer Dashboard</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Stats Cards -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-blue-600">0</div>
                    <div class="text-gray-600 mt-2">Total Events</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-green-600">0</div>
                    <div class="text-gray-600 mt-2">Total Attendees</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-purple-600">0</div>
                    <div class="text-gray-600 mt-2">Upcoming Events</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-orange-600">0</div>
                    <div class="text-gray-600 mt-2">Past Events</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('organizer.events.create') }}"class="bg-blue-600 text-white rounded-lg p-4 text-center hover:bg-blue-700">Create New Event</a>

                    <a href="#" class="bg-green-600 text-white rounded-lg p-4 text-center hover:bg-green-700">
                        View My Events
                    </a>
                    <a href="#" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                        Manage Attendees
                    </a>

                    <a href="#" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                        View Unpublished Events
                    </a>

                     <a href="{{ route('organizer.host.create') }}" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                        Create Host
                    </a>

                     <a href="#" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                        View Host Persons
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
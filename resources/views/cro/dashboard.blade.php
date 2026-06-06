<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRO Dashboard - EventHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">CRO Dashboard</h1>
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
                    <div class="text-gray-600 mt-2">Total Inquiries</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-green-600">0</div>
                    <div class="text-gray-600 mt-2">Resolved</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-yellow-600">0</div>
                    <div class="text-gray-600 mt-2">Pending</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-3xl font-bold text-purple-600">0</div>
                    <div class="text-gray-600 mt-2">Total Events</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="#" class="bg-blue-600 text-white rounded-lg p-4 text-center hover:bg-blue-700">
                        View Inquiries
                    </a>
                    <a href="#" class="bg-green-600 text-white rounded-lg p-4 text-center hover:bg-green-700">
                        Respond to Users
                    </a>
                    <a href="#" class="bg-purple-600 text-white rounded-lg p-4 text-center hover:bg-purple-700">
                        Event Reports
                    </a>
                    <a href="#" class="bg-orange-600 text-white rounded-lg p-4 text-center hover:bg-orange-700">
                        User Management
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>

</html>

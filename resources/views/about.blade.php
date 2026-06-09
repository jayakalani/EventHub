<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" :class="darkMode ? 'dark' : ''">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EventHub</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Alpine.js (for dark mode toggle) -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 dark:bg-[#0a0a0a] text-gray-800 dark:text-gray-200 min-h-screen">

    <!-- 🌐 NAVBAR -->
    <nav
        class="flex justify-between items-center px-6 lg:px-12 py-4 backdrop-blur-md bg-white/70 dark:bg-[#161615]/70 sticky top-0 z-50 border-b border-gray-200 dark:border-gray-800">

        <!-- Logo -->
        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            EventHub
        </h1>

        <!-- Right -->
        <div class="flex items-center gap-4">

            @auth
                <a href="/dashboard" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium">Sign In</a>
                <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium"> Register</a>
            @endauth

        </div>
    </nav>


    <!-- 🌟 MAIN -->
    <div class="flex justify-center items-center px-6 py-0">
        <main class="flex flex-col-reverse lg:flex-row max-w-7xl w-full rounded-2xl overflow-hidden shadow-xl">

            <!-- LEFT CONTENT -->
            <div class="flex-1 px-8 lg:p-16">

                <!-- HERO -->
                <div class="mb-10 animate-fade-in">

                    <h1
                        class="text-4xl lg:text-6xl font-extrabold mb-6 
                        bg-gradient-to-r from-blue-600 via-purple-600 to-pink-500 
                        bg-clip-text text-transparent">
                        EventHub
                    </h1>

                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-xl">
                        Smart event management system for ticketing, ticket allocation, and seamless organization of
                        concerts, theatre shows, and large-scale events.
                    </p>

                    <!-- BUTTONS -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        @auth
                            <a href="/dashboard"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md">
                                Sign In
                            </a>

                            <a href="{{ route('register') }}"
                                class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl">
                                Get Started
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- FEATURES -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

                    <div
                        class="backdrop-blur-lg bg-white/60 dark:bg-gray-800/60 p-6 rounded-xl shadow-md hover:scale-105 transition">
                        <div class="text-3xl mb-3">🎫</div>
                        <h3 class="font-semibold text-lg">Ticketing</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Manage and generate event tickets easily.
                        </p>
                    </div>

                    <div
                        class="backdrop-blur-lg bg-white/60 dark:bg-gray-800/60 p-6 rounded-xl shadow-md hover:scale-105 transition">
                        <div class="text-3xl mb-3">💺</div>
                        <h3 class="font-semibold text-lg">ticket Allocation</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Assign tickets efficiently.</p>
                    </div>

                    <div
                        class="backdrop-blur-lg bg-white/60 dark:bg-gray-800/60 p-6 rounded-xl shadow-md hover:scale-105 transition">
                        <div class="text-3xl mb-3">📊</div>
                        <h3 class="font-semibold text-lg">Analytics</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Track performance & engagement.</p>
                    </div>

                </div>

            </div>


            <!-- RIGHT IMAGE -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6">

                <!-- Image Container (for better UX) -->
                <div class="rounded-2xl overflow-hidden shadow-lg">
                    <img src="{{ asset('cover-images/event-hub-illustration.png') }}"
                        class="w-full max-w-lg object-contain transition hover:scale-105 duration-500">
                </div>

            </div>

        </main>
    </div>


    <!-- ✨ ANIMATION STYLE -->
    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 1s ease-out;
        }
    </style>

</body>

</html>

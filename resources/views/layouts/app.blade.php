<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EventHub') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="font-sans antialiased">
    @php
        $userRoleName = Auth::user()?->userRole?->name_en;
        $isAttendee = $userRoleName === \App\Models\UserRole::ATTENDEE;
        $isOrganizer = $userRoleName === \App\Models\UserRole::ORGANIZER;
        $isAdmin = $userRoleName === \App\Models\UserRole::ADMIN;
        $isCro = $userRoleName === \App\Models\UserRole::CRO;
    @endphp

    <div class="flex min-h-screen flex-col bg-[#F0F8FF]">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="sticky top-16 z-40 border-b border-slate-200/80 bg-white/85 shadow-sm backdrop-blur-xl">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-5 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        @if ($isAttendee)
            @include('partials.attendee-footer')
        @elseif ($isOrganizer)
            @include('partials.organizer-footer')
        @elseif ($isAdmin)
            @include('partials.admin-footer')
        @elseif ($isCro)
            @include('partials.cro-footer')
        @endif
    </div>
    <x-scroll-to-top />
    @stack('scripts')
</body>

</html>

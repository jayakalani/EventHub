<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900">
                    Organizer Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage events, attendees, and hosts from one centralized dashboard.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('organizer.reports') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-full text-sm font-semibold shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Reports
                </a>

                <a href="{{ route('organizer.events.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    All Events
                </a>

                <a href="{{ route('organizer.hosts') }}"
                    class="inline-flex items-center px-4 py-2 bg-slate-800 text-white rounded-full text-sm font-semibold shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition">
                    All Hosts
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Statistics Cards -->
            <div class="grid gap-6 lg:grid-cols-4 mb-6">

                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        My Events
                    </p>
                    <h3 class="mt-4 text-3xl font-semibold text-blue-600">
                        {{ $totalEvents ?? 0 }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Events you have created
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Attendees
                    </p>
                    <h3 class="mt-4 text-3xl font-semibold text-green-600">
                        {{ $totalAttendees ?? 0 }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Registered attendees
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Upcoming Events
                    </p>
                    <h3 class="mt-4 text-3xl font-semibold text-purple-600">
                        {{ $upcomingEvents ?? 0 }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Scheduled events
                    </p>
                </div>
            </div>

            <!-- Main Dashboard Area -->
            <div class="grid gap-6 xl:grid-cols-3">

                <!-- Recent Events -->
                <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">

                    <div class="px-6 py-5 border-b border-slate-200 sm:px-8">
                        <h3 class="text-lg font-semibold text-slate-900">
                            Recent Events
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Latest events created and managed by you.
                        </p>
                    </div>

                    <div class="divide-y divide-slate-200">

                        @forelse($events ?? [] as $event)
                            <div class="flex flex-col gap-2 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ $event->name }}
                                    </p>

                                    <p class="text-sm text-slate-500">
                                        {{ \Carbon\Carbon::parse($event->date)->format('M d, Y') }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                                    Event
                                </span>

                            </div>

                        @empty

                            <div class="px-6 py-12 text-center">
                                <p class="text-slate-500">
                                    No events available yet.
                                </p>

                                <a href="{{ route('organizer.events.create') }}"
                                    class="inline-flex mt-4 items-center px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-semibold hover:bg-blue-700">
                                    Create First Event
                                </a>
                            </div>
                        @endforelse

                    </div>
                </div>

                <!-- Quick Actions -->
                <aside class="rounded-3xl border border-slate-200 bg-white shadow-sm">

                    <div class="px-6 py-5 border-b border-slate-200 sm:px-8">
                        <h3 class="text-lg font-semibold text-slate-900">
                            Quick Actions
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Frequently used organizer tools.
                        </p>
                    </div>

                    <div class="space-y-3 px-6 py-6 sm:px-8">

                        <a href="{{ route('organizer.reports') }}"
                            class="block rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                            Reports & Analytics
                        </a>

                        <a href="{{ route('organizer.events.index') }}"
                            class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
                            Manage Events
                        </a>

                        <a href="{{ route('organizer.events.create') }}"
                            class="block rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">
                            Create Event
                        </a>

                        <a href="{{ route('organizer.hosts') }}"
                            class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
                            All Hosts
                        </a>

                        <a href="{{ route('organizer.reports', ['tab' => 'attendees']) }}"
                            class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
                            View Attendees
                        </a>

                    </div>
                </aside>

            </div>

        </div>
    </div>
</x-app-layout>

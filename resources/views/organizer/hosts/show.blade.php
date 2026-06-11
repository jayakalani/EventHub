<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('organizer.hosts') }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; Back to Hosts
                </a>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">
                    {{ $host->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Host details and events
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-sm">
                <div class="grid md:grid-cols-3">
                    <div class="md:col-span-1 h-56 md:h-auto">
                        @if ($host->cover)
                            <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}" alt="{{ $host->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop"
                                alt="Host Cover" class="h-full w-full object-cover">
                        @endif
                    </div>

                    <div class="md:col-span-2 p-6 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $host->name }}</h3>
                                <span
                                    class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $host->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $host->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm text-gray-600">
                            <p>📧 {{ $host->email }}</p>
                            <p>📞 {{ $host->contact_number }}</p>
                            <p>{{ $events->count() }} event(s)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Events by this host
                    </h3>
                    <a href="{{ route('organizer.events.index', ['search' => $host->name]) }}"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        Browse in Events
                    </a>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($events as $event)
                        <div
                            class="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-xl transition overflow-hidden">
                            @if ($event->cover)
                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    alt="{{ $event->name }}" class="h-40 w-full object-cover">
                            @else
                                <div class="h-40 bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                                    No Image
                                </div>
                            @endif

                            <div class="p-4">
                                <h4 class="font-semibold text-base text-gray-900 line-clamp-1">{{ $event->name }}</h4>
                                <p class="mt-2 text-xs text-gray-600">📅 {{ $event->date }}</p>
                                <p class="mt-1 text-xs text-gray-600">📍 {{ $event->place }}</p>

                                @if ($event->eventCategory)
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $event->eventCategory->name }}
                                    </p>
                                @endif

                                <span
                                    class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold
                                    {{ $event->status === 'upcoming' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $event->status === 'ongoing' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $event->status === 'completed' ? 'bg-gray-100 text-gray-700' : '' }}
                                    {{ $event->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                                    {{ ucfirst($event->status) }}
                                </span>

                                <a href="{{ route('organizer.events.show', $event) }}"
                                    class="mt-4 block text-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                    View Event
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">
                            This host has no events yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

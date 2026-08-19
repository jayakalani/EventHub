<x-app-layout>

    <!-- Header -->
    <x-slot name="header">

        <!-- Single Compact Header Row -->
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">

            <!-- Left -->
            <div class="flex items-center gap-3">

                <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">
                    All Artists
                </h2>

                <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">
                    {{ $artists->total() }} Artists
                </span>

            </div>

            <!-- Right -->
            <div class="flex flex-wrap items-center gap-2">

                <!-- Search -->
                <form method="GET" action="{{ route('organizer.artists') }}" class="flex flex-wrap items-center gap-2">

                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}"
                        class="w-44 xl:w-52 px-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                    <!-- Status -->
                    <select name="status"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                        <option value="">All</option>

                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>

                    <!-- Apply -->
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                        Apply
                    </button>

                    <!-- Reset -->
                    <a href="{{ route('organizer.artists') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-xl hover:bg-gray-200 transition">
                        Reset
                    </a>

                </form>

                <!-- Divider -->
                <div class="hidden xl:block w-px h-8 bg-gray-200 mx-1"></div>

                <!-- Actions -->
                <div class="flex items-center gap-2">

                    <a href="{{ route('organizer.artist.create') }}"
                        class="px-3 py-2 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition">
                        Create Artist
                    </a>

                    <a href="{{ route('organizer.artists.export.csv') }}"
                        class="px-3 py-2 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition">
                        CSV
                    </a>

                    <a href="{{ route('organizer.artists.export.pdf') }}"
                        class="px-3 py-2 bg-red-600 text-white text-sm rounded-xl hover:bg-red-700 transition">
                        PDF
                    </a>

                </div>

            </div>

        </div>

    </x-slot>

    <!-- Page -->
    <div class="py-5">

        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Success -->
            @if (session('success'))
                <div class="mb-5 px-4 py-3 rounded-xl bg-green-100 border border-green-200 text-green-700 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 px-4 py-3 rounded-xl bg-red-100 border border-red-200 text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Artists Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">

                @forelse($artists as $artist)
                    <div
                        class="group bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition duration-300">

                        <!-- Cover -->
                        <div class="relative h-40 overflow-hidden">

                            @if ($artist->cover)
                                <img src="{{ asset('uploads/covers/artists/' . $artist->cover) }}"
                                    alt="{{ $artist->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop"
                                    alt="Artist Cover"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @endif

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>

                            <!-- Status -->
                            <div class="absolute top-3 right-3">

                                <span
                                    class="px-2 py-1 text-[10px] font-semibold rounded-full
                                    {{ $artist->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">

                                    {{ $artist->is_active ? 'Active' : 'Inactive' }}
                                </span>

                            </div>

                            <!-- Name -->
                            <div class="absolute bottom-3 left-3 right-3">

                                <h3 class="text-base font-bold text-white truncate">
                                    {{ $artist->name }}
                                </h3>

                            </div>

                        </div>

                        <!-- Content -->
                        <div class="p-4">

                            <!-- Info -->
                            <div class="space-y-1.5 mb-4">
                                <p class="text-base font-bold text-gray-900 ">
                                    {{ $artist->name }}
                                </p>

                                <p class="text-xs text-gray-600 truncate">
                                    📧 {{ $artist->email }}
                                </p>

                                <p class="text-xs text-gray-600 truncate">
                                    📞 {{ $artist->contact_number }}
                                </p>

                            </div>

                            <!-- Actions -->
                            <div class="grid grid-cols-3 gap-2">
                                @php
                                    $artistLocked = $artist->events_count > 0 || $artist->artist_follows_count > 0;
                                    $artistLockReason = $artist->artist_follows_count > 0
                                        ? 'Followed by attendees — cannot deactivate'
                                        : 'Linked to events — cannot deactivate';
                                @endphp

                                <!-- View -->
                                <a href="{{ route('organizer.artists.show', $artist) }}"
                                    class="col-span-3 text-center px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition">
                                    View
                                </a>

                                @can('toggleActive', $artist)
                                    <!-- Toggle -->
                                    @if ($artist->is_active && $artistLocked)
                                        <div class="col-span-3">
                                            <button type="button" disabled
                                                title="{{ $artistLockReason }}"
                                                class="w-full py-2 rounded-xl text-xs font-medium bg-gray-100 text-gray-400 cursor-not-allowed">
                                                Deactivate
                                            </button>
                                        </div>
                                    @else
                                        <form action="{{ route('organizer.artists.toggleActive', $artist) }}" method="POST"
                                            class="col-span-3">
                                            @csrf

                                            <button type="submit"
                                                onclick="return confirm('Are you sure you want to {{ $artist->is_active ? 'deactivate' : 'activate' }} this artist?')"
                                                class="w-full py-2 rounded-xl text-xs font-medium transition
                                                {{ $artist->is_active
                                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">

                                                {{ $artist->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @can('update', $artist)
                                    <!-- Edit -->
                                    <a href="{{ route('organizer.artists.edit', $artist) }}"
                                        class="text-center px-3 py-2 rounded-xl bg-indigo-100 text-indigo-700 text-xs font-medium hover:bg-indigo-200 transition">
                                        Edit
                                    </a>
                                @endcan

                                @can('delete', $artist)
                                    <!-- Delete -->
                                    @if ($artistLocked)
                                        @php
                                            $artistDeleteBlockMessage = $artist->artist_follows_count > 0
                                                ? 'This artist cannot be deleted because attendees are following them.'
                                                : 'This artist cannot be deleted because they are linked to '.$artist->events_count.' event(s). Remove or reassign those events first.';
                                        @endphp
                                        <button type="button"
                                            onclick="alert(@js($artistDeleteBlockMessage))"
                                            class="col-span-2 w-full px-3 py-2 rounded-xl bg-gray-100 text-gray-400 text-xs font-medium cursor-not-allowed"
                                            title="{{ $artist->artist_follows_count > 0 ? 'Followed by attendees — cannot delete' : 'Linked to events — cannot delete' }}">
                                            Delete
                                        </button>
                                    @else
                                        <form action="{{ route('organizer.artists.destroy', $artist) }}" method="POST"
                                            class="col-span-2">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                onclick="return confirm('Delete this artist? This cannot be undone.')"
                                                class="w-full px-3 py-2 rounded-xl bg-red-100 text-red-600 text-xs font-medium hover:bg-red-200 transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                            </div>

                        </div>

                    </div>

                @empty

                    <!-- Empty -->
                    <div class="col-span-full">

                        <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-10 text-center">

                            <div class="text-4xl mb-4">
                                🎭
                            </div>

                            <h3 class="text-xl font-bold text-gray-700 mb-2">
                                No Artists Found
                            </h3>

                            <p class="text-sm text-gray-500 mb-5">
                                No artists match your filters.
                            </p>

                            <a href="{{ route('organizer.artist.create') }}"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 transition">
                                + Create Artist
                            </a>

                        </div>

                    </div>
                @endforelse

            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $artists->links() }}
            </div>

        </div>

    </div>

</x-app-layout>

<x-app-layout>

    <!-- Header -->
    <x-slot name="header">

        <!-- Single Compact Header Row -->
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">

            <!-- Left -->
            <div class="flex items-center gap-3">

                <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">
                    All Hosts
                </h2>

                <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">
                    {{ $hosts->total() }} Hosts
                </span>

            </div>

            <!-- Right -->
            <div class="flex flex-wrap items-center gap-2">

                <!-- Search -->
                <form method="GET" action="{{ route('organizer.hosts') }}" class="flex flex-wrap items-center gap-2">

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

                    <!-- From -->
                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                    <!-- To -->
                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                    <!-- Apply -->
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                        Apply
                    </button>

                    <!-- Reset -->
                    <a href="{{ route('organizer.hosts') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-xl hover:bg-gray-200 transition">
                        Reset
                    </a>

                </form>

                <!-- Divider -->
                <div class="hidden xl:block w-px h-8 bg-gray-200 mx-1"></div>

                <!-- Actions -->
                <div class="flex items-center gap-2">

                    <a href="{{ route('organizer.host.create') }}"
                        class="px-3 py-2 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition">
                        Create Host
                    </a>

                    <a href="{{ route('organizer.hosts.export.csv') }}"
                        class="px-3 py-2 bg-green-600 text-white text-sm rounded-xl hover:bg-green-700 transition">
                        CSV
                    </a>

                    <a href="{{ route('organizer.hosts.export.pdf') }}"
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

            <!-- Hosts Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">

                @forelse($hosts as $host)
                    <div
                        class="group bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition duration-300">

                        <!-- Cover -->
                        <div class="relative h-40 overflow-hidden">

                            @if ($host->cover)
                                <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}"
                                    alt="{{ $host->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&auto=format&fit=crop"
                                    alt="Host Cover"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @endif

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>

                            <!-- Status -->
                            <div class="absolute top-3 right-3">

                                <span
                                    class="px-2 py-1 text-[10px] font-semibold rounded-full
                                    {{ $host->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">

                                    {{ $host->is_active ? 'Active' : 'Inactive' }}
                                </span>

                            </div>

                            <!-- Name -->
                            <div class="absolute bottom-3 left-3 right-3">

                                <h3 class="text-base font-bold text-white truncate">
                                    {{ $host->name }}
                                </h3>

                            </div>

                        </div>

                        <!-- Content -->
                        <div class="p-4">

                            <!-- Info -->
                            <div class="space-y-1.5 mb-4">
                                <p class="text-base font-bold text-gray-900 ">
                                    {{ $host->name }}
                                </p>

                                <p class="text-xs text-gray-600 truncate">
                                    📧 {{ $host->email }}
                                </p>

                                <p class="text-xs text-gray-600 truncate">
                                    📞 {{ $host->contact_number }}
                                </p>

                            </div>

                            <!-- Actions -->
                            <div class="grid grid-cols-3 gap-2">

                                <!-- Toggle -->
                                <form action="{{ route('organizer.hosts.toggleActive', $host->id) }}" method="POST"
                                    class="col-span-3">
                                    @csrf

                                    <button type="submit"
                                        class="w-full py-2 rounded-xl text-xs font-medium transition
                                        {{ $host->is_active
                                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">

                                        {{ $host->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                <!-- Edit -->
                                <a href="{{ route('organizer.hosts.edit', $host->id) }}"
                                    class="text-center px-3 py-2 rounded-xl bg-indigo-100 text-indigo-700 text-xs font-medium hover:bg-indigo-200 transition">
                                    Edit
                                </a>

                                <!-- Delete -->
                                <form action="{{ route('organizer.hosts.destroy', $host->id) }}" method="POST"
                                    class="col-span-2">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" onclick="return confirm('Delete this host?')"
                                        class="w-full px-3 py-2 rounded-xl bg-red-100 text-red-600 text-xs font-medium hover:bg-red-200 transition">
                                        Delete
                                    </button>
                                </form>

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
                                No Hosts Found
                            </h3>

                            <p class="text-sm text-gray-500 mb-5">
                                No hosts match your filters.
                            </p>

                            <a href="{{ route('organizer.host.create') }}"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-xl hover:bg-indigo-700 transition">
                                + Create Host
                            </a>

                        </div>

                    </div>
                @endforelse

            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $hosts->links() }}
            </div>

        </div>

    </div>

</x-app-layout>

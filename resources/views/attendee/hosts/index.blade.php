<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">
                    {{ __('Hosts') }}
                </h2>

                <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">
                    {{ $hosts->total() }} {{ __('Active Hosts') }}
                </span>
            </div>

            <form method="GET" action="{{ route('attendee.hosts.index') }}"
                class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" placeholder="{{ __('Search hosts...') }}"
                    value="{{ request('search') }}"
                    class="w-44 xl:w-52 px-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                    {{ __('Apply') }}
                </button>

                <a href="{{ route('attendee.hosts.index') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-xl hover:bg-gray-200 transition">
                    {{ __('Reset') }}
                </a>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($hosts as $host)
                    <div
                        class="group rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                        <div class="relative h-44 overflow-hidden">
                            @if ($host->cover)
                                <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}"
                                    alt="{{ $host->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="h-full w-full bg-slate-100 flex items-center justify-center text-slate-400">
                                    {{ __('No Image') }}
                                </div>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>

                            <div class="absolute bottom-3 left-3 right-3">
                                <h3 class="text-lg font-bold text-white truncate">
                                    {{ $host->name }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                    <i class="bi  text-base" aria-hidden="true"></i>
                                    {{ $host->host_likes_count ?? 0 }}
                                </span>

                                <form action="{{ route('attendee.hosts.like', $host) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        aria-label="{{ $host->is_liked ? __('Unlike host') : __('Like host') }}"
                                        title="{{ $host->is_liked ? __('Unlike') : __('Like') }}"
                                        class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                        {{ $host->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <i class="bi {{ $host->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                            aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>

                            <a href="{{ route('attendee.hosts.show', $host) }}"
                                class="mt-4 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                {{ __('View Details') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
                            <div class="text-4xl mb-4">🎭</div>
                            <h3 class="text-xl font-bold text-slate-700 mb-2">
                                {{ __('No Hosts Found') }}
                            </h3>
                            <p class="text-sm text-slate-500">
                                {{ __('No active hosts match your search.') }}
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $hosts->links() }}
            </div>
        </div>
    </div>

</x-app-layout>

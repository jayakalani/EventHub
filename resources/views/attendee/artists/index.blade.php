<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-900 whitespace-nowrap">
                    {{ t(['en' => 'Artists', 'si' => 'කලාකරුවන්']) }}
                </h2>

                <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">
                    {{ $artists->total() }} {{ t(['en' => 'Active Artists', 'si' => 'සක්‍රීය කලාකරුවන්']) }}
                </span>
            </div>

            <form method="GET" action="{{ route('attendee.artists.index') }}"
                class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" placeholder="{{ t(['en' => 'Search artists...', 'si' => 'කලාකරුවන් සොයන්න...']) }}"
                    value="{{ request('search') }}"
                    class="w-44 xl:w-52 px-4 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">
                    {{ t(['en' => 'Apply', 'si' => 'සොයන්න']) }}
                </button>

                <a href="{{ route('attendee.artists.index') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-xl hover:bg-gray-200 transition">
                    {{ t(['en' => 'Reset', 'si' => 'යළි සකසන්න']) }}
                </a>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($artists as $artist)
                    <div
                        class="group rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                        <div class="relative aspect-[3/4] overflow-hidden bg-slate-200">
                            @if ($artist->cover)
                                <img src="{{ asset('uploads/covers/artists/' . $artist->cover) }}"
                                    alt=""
                                    aria-hidden="true"
                                    class="absolute inset-0 h-full w-full scale-110 object-cover blur-2xl brightness-90">
                                <img src="{{ asset('uploads/covers/artists/' . $artist->cover) }}"
                                    alt="{{ $artist->name }}"
                                    class="relative z-[1] h-full w-full object-contain transition duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-400">
                                    {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                </div>
                            @endif

                            <div class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t from-black/70 via-black/15 to-transparent"></div>

                            <div class="absolute bottom-3 left-3 right-3 z-[3]">
                                <h3 class="truncate text-lg font-bold text-white drop-shadow">
                                    {{ $artist->name }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                        <i class="bi bi-hand-thumbs-up text-base" aria-hidden="true"></i>
                                        {{ $artist->artist_likes_count ?? 0 }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600">
                                        <i class="bi bi-person-check text-base" aria-hidden="true"></i>
                                        {{ $artist->artist_follows_count ?? 0 }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <form action="{{ route('attendee.artists.follow', $artist) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            aria-label="{{ $artist->is_followed ? t(['en' => 'Unfollow artist', 'si' => 'අනුගමනය නවත්වන්න']) : t(['en' => 'Follow artist', 'si' => 'අනුගමනය කරන්න']) }}"
                                            title="{{ $artist->is_followed ? t(['en' => 'Following', 'si' => 'අනුගමනය කරයි']) : t(['en' => 'Follow', 'si' => 'අනුගමනය කරන්න']) }}"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold transition
                                            {{ $artist->is_followed
                                                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                            <i class="bi {{ $artist->is_followed ? 'bi-person-check-fill' : 'bi-person-plus' }}"
                                                aria-hidden="true"></i>
                                            {{ $artist->is_followed
                                                ? t(['en' => 'Following', 'si' => 'අනුගමනය'])
                                                : t(['en' => 'Follow', 'si' => 'අනුගමනය']) }}
                                        </button>
                                    </form>

                                    <form action="{{ route('attendee.artists.like', $artist) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            aria-label="{{ $artist->is_liked ? t(['en' => 'Unlike artist', 'si' => 'කැමති නැත']) : t(['en' => 'Like artist', 'si' => 'කැමතියි']) }}"
                                            title="{{ $artist->is_liked ? t(['en' => 'Unlike', 'si' => 'කැමති නැත']) : t(['en' => 'Like', 'si' => 'කැමතියි']) }}"
                                            class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                            {{ $artist->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <i class="bi {{ $artist->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <a href="{{ route('attendee.artists.show', $artist) }}"
                                class="mt-4 inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-xs font-semibold text-white hover:bg-primary-dark transition">
                                {{ t(['en' => 'View Details', 'si' => 'විස්තර බලන්න']) }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
                            <div class="text-4xl mb-4">🎭</div>
                            <h3 class="text-xl font-bold text-slate-700 mb-2">
                                {{ t(['en' => 'No Artists Found', 'si' => 'කලාකරුවන් හමු නොවීය']) }}
                            </h3>
                            <p class="text-sm text-slate-500">
                                {{ __('No active artists match your search.') }}
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $artists->links() }}
            </div>
        </div>
    </div>

</x-app-layout>

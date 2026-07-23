<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('attendee.hosts.index') }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; {{ t(['en' => 'Back to Hosts', 'si' => 'සත්කාරකයන් වෙත ආපසු']) }}
                </a>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">
                    {{ $host->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ t(['en' => 'Host details and events', 'si' => 'සත්කාරක විස්තර සහ ප්‍රසංග']) }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="rounded-3xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="grid md:grid-cols-3">
                    <div class="md:col-span-1 h-56 md:h-auto">
                        @if ($host->cover)
                            <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}" alt="{{ $host->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="h-full min-h-56 bg-slate-100 flex items-center justify-center text-slate-400">
                                {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-2 p-6 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">{{ $host->name }}</h3>
                                <span
                                    class="mt-2 inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                    {{ t(['en' => 'Active', 'si' => 'සක්‍රීය']) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                    <i aria-hidden="true"></i>
                                    {{ $host->host_likes_count ?? 0 }}
                                </span>

                                <form action="{{ route('attendee.hosts.like', $host) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        aria-label="{{ $host->is_liked ? t(['en' => 'Unlike host', 'si' => 'කැමති නැත']) : t(['en' => 'Like host', 'si' => 'කැමතියි']) }}"
                                        class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                        {{ $host->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <i class="bi {{ $host->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                            aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ t(['en' => 'Events by this host', 'si' => 'මෙම සත්කාරකයාගේ ප්‍රසංග']) }}
                    </h3>
                    <a href="{{ route('attendee.dashboard', ['host' => $host->id]) }}"
                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ t(['en' => 'Browse in Events', 'si' => 'ප්‍රසංග තුළ බලන්න']) }}
                    </a>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($events as $event)
                        <div
                            class="rounded-3xl border border-slate-200 bg-white shadow-sm hover:shadow-xl transition overflow-hidden">
                            @if ($event->cover)
                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    class="h-40 w-full object-cover">
                            @else
                                <div class="h-40 bg-slate-100 flex items-center justify-center text-slate-400">
                                    {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                </div>
                            @endif

                            <div class="p-5">
                                <h4 class="font-semibold text-lg text-slate-900 line-clamp-1">{{ $event->name }}</h4>
                                <p class="mt-2 text-sm text-slate-500">📅 {{ $event->date }}</p>
                                <p class="mt-1 text-sm text-slate-500">📍 {{ $event->place }}</p>

                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-[#1877F2]">
                                        <i  aria-hidden="true"></i>
                                        {{ $event->likes_count ?? 0 }}
                                    </span>

                                    <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            aria-label="{{ $event->is_liked ? t(['en' => 'Unlike event', 'si' => 'කැමති නැත']) : t(['en' => 'Like event', 'si' => 'කැමතියි']) }}"
                                            class="inline-flex items-center justify-center rounded-full p-2.5 text-xl transition
                                            {{ $event->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>

                                <a href="{{ route('attendee.events.show', $event) }}"
                                    class="mt-4 block text-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                    {{ t(['en' => 'View Event', 'si' => 'ප්‍රසංග බලන්න']) }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                            {{ t(['en' => 'This host has no events yet.', 'si' => 'මෙම සත්කාරකයාට තවම ප්‍රසංග නැත.']) }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

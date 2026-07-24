<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
            <div class="min-w-0">
                <a href="{{ route('attendee.hosts.index') }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800 sm:text-sm">
                    &larr; {{ t(['en' => 'Back to Hosts', 'si' => 'සත්කාරකයන් වෙත ආපසු']) }}
                </a>
                <h2 class="mt-0.5 text-lg font-bold leading-tight text-slate-900 sm:text-xl">
                    {{ $host->name }}
                </h2>
                <p class="text-xs text-slate-500 sm:text-sm">
                    {{ t(['en' => 'Host details and events', 'si' => 'සත්කාරක විස්තර සහ ප්‍රසංග']) }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid md:grid-cols-3">
                    <div class="md:col-span-1 h-40 md:h-auto md:min-h-[10rem]">
                        @if ($host->cover)
                            <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}" alt="{{ $host->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full min-h-40 items-center justify-center bg-slate-100 text-sm text-slate-400">
                                {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                            </div>
                        @endif
                    </div>

                    <div class="md:col-span-2 px-4 py-4 sm:px-5 sm:py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">{{ $host->name }}</h3>
                                <span
                                    class="mt-1.5 inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    {{ t(['en' => 'Active', 'si' => 'සක්‍රීය']) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                    <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                    {{ $host->host_likes_count ?? 0 }}
                                </span>

                                <form action="{{ route('attendee.hosts.like', $host) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        aria-label="{{ $host->is_liked ? t(['en' => 'Unlike host', 'si' => 'කැමති නැත']) : t(['en' => 'Like host', 'si' => 'කැමතියි']) }}"
                                        class="inline-flex items-center justify-center rounded-full p-2 text-lg transition
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
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ t(['en' => 'Events by this host', 'si' => 'මෙම සත්කාරකයාගේ ප්‍රසංග']) }}
                    </h3>
                    <a href="{{ route('attendee.dashboard', ['host' => $host->id]) }}"
                        class="text-xs font-semibold text-[#0F0363] hover:opacity-80 sm:text-sm">
                        {{ t(['en' => 'Browse in Events', 'si' => 'ප්‍රසංග තුළ බලන්න']) }}
                    </a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                    @forelse($events as $event)
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-lg">
                            @if ($event->cover)
                                <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                    class="h-32 w-full object-cover">
                            @else
                                <div class="flex h-32 items-center justify-center bg-slate-100 text-sm text-slate-400">
                                    {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                </div>
                            @endif

                            <div class="p-3.5">
                                <h4 class="line-clamp-1 text-base font-semibold text-slate-900">{{ $event->name }}</h4>
                                <p class="mt-1 text-xs text-slate-500">📅 {{ $event->date }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">📍 {{ $event->place }}</p>

                                <div class="mt-2.5 flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#1877F2]">
                                        <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                        {{ $event->likes_count ?? 0 }}
                                    </span>

                                    <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            aria-label="{{ $event->is_liked ? t(['en' => 'Unlike event', 'si' => 'කැමති නැත']) : t(['en' => 'Like event', 'si' => 'කැමතියි']) }}"
                                            class="inline-flex items-center justify-center rounded-full p-2 text-lg transition
                                            {{ $event->is_liked ? 'bg-[#1877F2] text-white hover:bg-[#166fe5]' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                            <i class="bi {{ $event->is_liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>

                                <a href="{{ route('attendee.events.show', $event) }}"
                                    class="mt-3 block rounded-xl bg-primary px-3 py-2 text-center text-xs font-semibold text-white transition hover:bg-primary-dark sm:text-sm">
                                    {{ t(['en' => 'View Event', 'si' => 'ප්‍රසංග බලන්න']) }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                            {{ t(['en' => 'This host has no events yet.', 'si' => 'මෙම සත්කාරකයාට තවම ප්‍රසංග නැත.']) }}
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

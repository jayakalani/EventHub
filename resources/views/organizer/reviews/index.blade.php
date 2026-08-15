<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Reviews
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Attendee ratings across your events.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Total Reviews</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ number_format($stats['total']) }}
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Average Score</p>
                    <h3 class="mt-1 text-2xl font-bold text-yellow-600">
                        @if ($stats['total'] > 0)
                            {{ number_format($stats['average'], 1) }}/5
                        @else
                            —
                        @endif
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Events Reviewed</p>
                    <h3 class="mt-1 text-2xl font-bold text-indigo-600">
                        {{ number_format($stats['events']) }}
                    </h3>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">5-Star Reviews</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ number_format($stats['five_star']) }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <form method="GET" action="{{ route('organizer.reviews.index') }}"
                    class="grid grid-cols-1 gap-2.5 md:grid-cols-3 lg:grid-cols-7">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search attendee, email, event..."
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2">

                    <select name="event_id"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Events</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected(($filters['event_id'] ?? null) == $event->id)>
                                {{ $event->filterLabel() }}
                            </option>
                        @endforeach
                    </select>

                    <select name="score"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Scores</option>
                        @for ($score = 5; $score >= 1; $score--)
                            <option value="{{ $score }}" @selected(($filters['score'] ?? null) == $score)>
                                {{ $score }} star{{ $score === 1 ? '' : 's' }}
                            </option>
                        @endfor
                    </select>

                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 rounded-xl bg-indigo-600 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Apply
                        </button>
                        <a href="{{ route('organizer.reviews.index') }}"
                            class="flex flex-1 items-center justify-center rounded-xl bg-slate-100 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Inbox --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-base font-semibold text-slate-900">
                        Reviews inbox
                        <span class="ml-2 text-sm font-normal text-slate-500">
                            {{ number_format($ratings->total()) }} results
                        </span>
                    </h3>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($ratings as $rating)
                        <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-yellow-50 text-sm font-bold text-yellow-700">
                                    {{ strtoupper(substr($rating->user?->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($rating->user?->last_name ?? '', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $rating->user?->full_name ?? 'Unknown' }}
                                        </p>
                                        <div class="flex items-center gap-0.5">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-3.5 w-3.5 {{ $rating->score >= $star ? 'text-yellow-400' : 'text-slate-200' }}"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                </svg>
                                            @endfor
                                        </div>
                                        <span
                                            class="inline-flex rounded-lg bg-yellow-50 px-2 py-0.5 text-xs font-semibold text-yellow-800">
                                            {{ $rating->score }}/5
                                        </span>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $rating->user?->email ?? '—' }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-700">
                                        @if ($rating->event)
                                            <a href="{{ route('organizer.events.show', $rating->event) }}"
                                                class="font-medium text-indigo-600 hover:text-indigo-800">
                                                {{ $rating->event->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                <p class="text-xs text-slate-500">
                                    {{ $rating->created_at?->format('M d, Y H:i') }}
                                    <span class="text-slate-400">· {{ $rating->created_at?->diffForHumans() }}</span>
                                </p>
                                @if ($rating->event)
                                    <a href="{{ route('organizer.events.show', $rating->event) }}"
                                        class="inline-flex rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                        View event
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <h3 class="text-base font-semibold text-slate-800">
                                No reviews found
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                No ratings match your filters yet.
                            </p>
                            <a href="{{ route('organizer.reviews.index') }}"
                                class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                Clear filters
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                {{ $ratings->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

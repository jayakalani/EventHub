<x-app-layout>
    @php
        $status = $event->trashed() ? 'archived' : $event->status;
        $statusLabels = [
            'unpublished' => 'Unpublished',
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'postponed' => 'Postponed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'archived' => 'Archived',
        ];
        $statusBadge = match ($status) {
            'unpublished' => 'bg-slate-100 text-slate-700 ring-slate-200/70',
            'upcoming' => 'bg-sky-100 text-sky-700 ring-sky-200/70',
            'ongoing' => 'bg-emerald-100 text-emerald-700 ring-emerald-200/70',
            'postponed' => 'bg-amber-100 text-amber-800 ring-amber-200/70',
            'completed' => 'bg-indigo-100 text-indigo-700 ring-indigo-200/70',
            'cancelled' => 'bg-rose-100 text-rose-700 ring-rose-200/70',
            default => 'bg-slate-100 text-slate-600 ring-slate-200/70',
        };
    @endphp

    <div class="admin-event-show relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-600">Platform event</p>
                            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $event->name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">#{{ $event->id }} · {{ $event->organizer?->full_name ?? 'Unknown organizer' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusBadge }}">
                                {{ $statusLabels[$status] ?? ucfirst($status) }}
                            </span>
                            <a href="{{ route('admin.events.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Back to events
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            @if ($event->cover)
                <div class="overflow-hidden rounded-2xl border border-white/70 shadow-sm">
                    <img src="{{ asset('uploads/covers/events/'.$event->cover) }}"
                        alt="{{ $event->name }}"
                        class="h-56 w-full object-cover sm:h-72">
                </div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2">
                <div class="glass-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Schedule</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Date</dt>
                            <dd class="font-medium text-slate-800">
                                {{ $event->hasDateYetToBeScheduled() ? 'Not decided yet' : ($event->formattedScheduleDate() ?: '—') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Time</dt>
                            <dd class="font-medium text-slate-800">{{ $event->time ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Place</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $event->displayPlace() }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="glass-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">People & catalog</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Organizer</dt>
                            <dd class="font-medium text-slate-800">{{ $event->organizer?->full_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Category</dt>
                            <dd class="font-medium text-slate-800">{{ $event->eventCategory?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Host</dt>
                            <dd class="font-medium text-slate-800">{{ $event->host?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">CRO</dt>
                            <dd class="font-medium text-slate-800">{{ $event->contactPerson?->full_name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            @if ($event->artists->isNotEmpty())
                <section class="glass-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Artists</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $event->artists->pluck('name')->join(', ') }}</p>
                </section>
            @endif

            @if ($event->cancellation_reason || $event->postponement_reason)
                <section class="glass-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Moderation notes</h2>
                    @if ($event->cancellation_reason)
                        <p class="mt-2 text-sm text-rose-700">
                            <span class="font-semibold">Cancelled:</span>
                            {{ $event->cancellation_reason }}
                            @if ($event->cancelled_at)
                                <span class="text-rose-500"> · {{ $event->cancelled_at->format('d M Y H:i') }}</span>
                            @endif
                        </p>
                    @endif
                    @if ($event->postponement_reason)
                        <p class="mt-2 text-sm text-amber-800">
                            <span class="font-semibold">Postponed:</span>
                            {{ $event->postponement_reason }}
                            @if ($event->postponed_at)
                                <span class="text-amber-600"> · {{ $event->postponed_at->format('d M Y H:i') }}</span>
                            @endif
                        </p>
                    @endif
                </section>
            @endif

            <section class="glass-card p-5">
                <h2 class="text-sm font-semibold text-slate-900">Description</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $event->description ?: '—' }}</p>
            </section>

            <section class="glass-card overflow-hidden !p-0">
                <div class="border-b border-white/60 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Ticket categories</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Price (LKR)</th>
                                <th class="px-5 py-3">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($event->ticketCategories as $category)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-800">{{ $category->name }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ number_format((float) $category->ticket_price, 2) }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ number_format($category->no_of_tickets) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">No ticket categories.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

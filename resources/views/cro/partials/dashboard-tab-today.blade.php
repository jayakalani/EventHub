{{-- Today: queue, priority, handoffs, events, activity --}}
<div class="space-y-5">
    <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
        <div class="flex flex-col gap-3 border-b border-white/50 bg-gradient-to-r from-indigo-50/70 via-white/30 to-transparent px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-200/60 bg-indigo-50/80 text-indigo-600 shadow-sm">
                    <i class="bi bi-inbox"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-900">Today’s work</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Claim or open items in your queue</p>
                </div>
            </div>
            <span class="inline-flex h-8 min-w-8 items-center justify-center self-start rounded-xl bg-indigo-600/10 px-2.5 text-sm font-bold text-indigo-700 ring-1 ring-indigo-200/60">
                {{ number_format($todayTasks['queueTotal'] ?? count($todayWork)) }}
            </span>
        </div>
        <div class="divide-y divide-white/50">
            @forelse ($todayWork as $item)
                @php
                    $typeTone = match ($item['type'] ?? '') {
                        'complaint' => ['badge' => 'bg-rose-100/90 text-rose-700', 'label' => 'Complaint'],
                        'refund' => ['badge' => 'bg-amber-100/90 text-amber-800', 'label' => 'Refund'],
                        default => ['badge' => 'bg-sky-100/90 text-sky-800', 'label' => 'Inquiry'],
                    };
                @endphp
                <div class="btn-smooth flex flex-col gap-3 px-4 py-3.5 hover:bg-white/50 sm:flex-row sm:items-center sm:justify-between sm:px-5 {{ !empty($item['urgent']) ? 'bg-rose-50/40' : '' }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $typeTone['badge'] }}">{{ $typeTone['label'] }}</span>
                            @if (!empty($item['urgent']))
                                <span class="inline-flex rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Urgent</span>
                            @endif
                            @if (!empty($item['age']))
                                <span class="text-[11px] font-medium text-slate-400">{{ $item['age'] }}</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate font-semibold text-slate-900">{{ $item['title'] }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['meta'] }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if (!empty($item['claimUrl']))
                            <form method="POST" action="{{ $item['claimUrl'] }}">
                                @csrf
                                <button type="submit"
                                    class="btn-smooth inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-md">
                                    <i class="bi bi-hand-index-thumb"></i>
                                    Claim
                                </button>
                            </form>
                        @endif
                        <a href="{{ $item['href'] }}"
                            class="btn-smooth inline-flex items-center gap-1.5 rounded-xl border border-white/70 bg-white/70 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                            {{ $item['actionLabel'] === 'Claim' ? 'Open' : ($item['actionLabel'] ?? 'Open') }}
                            <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-4 py-14 text-center sm:px-5">
                    <span class="mb-2 flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100/90 text-emerald-600 ring-1 ring-emerald-200/70">
                        <i class="bi bi-check-lg"></i>
                    </span>
                    <p class="text-sm font-medium text-slate-700">Queue clear</p>
                    <p class="mt-0.5 text-xs text-slate-500">No open inquiries, refunds, or urgent complaints in your scope.</p>
                </div>
            @endforelse
        </div>
    </section>

    @if (count($handoffs))
        <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
            <div class="flex items-center justify-between gap-3 border-b border-amber-200/50 bg-gradient-to-r from-amber-50/50 via-white/20 to-transparent px-4 py-3.5 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200/70 bg-amber-50 text-amber-700 shadow-sm">
                        <i class="bi bi-clipboard-check"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-amber-950">Organizer handoffs</h2>
                        <p class="mt-0.5 text-xs text-amber-800/80 sm:text-sm">Postponed or cancelled follow-ups</p>
                    </div>
                </div>
                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-amber-100 px-2.5 text-sm font-bold text-amber-800">
                    {{ count($handoffs) }}
                </span>
            </div>
            <div class="divide-y divide-amber-100/70">
                @foreach ($handoffs as $handoff)
                    <a href="{{ $handoff['href'] }}"
                        class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-amber-50/70 sm:px-5">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <i class="bi bi-clipboard-check text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $handoff['event']['name'] ?? 'Event' }}</p>
                                <span class="rounded-md bg-white/80 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-800 ring-1 ring-amber-200/80">
                                    {{ $handoff['event']['statusLabel'] ?? ucfirst($handoff['type'] ?? '') }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ number_format($handoff['summary']['openInquiries'] ?? 0) }} open inquiries
                                · {{ number_format($handoff['summary']['pendingRefunds'] ?? 0) }} pending refunds
                            </p>
                        </div>
                        <i class="bi bi-chevron-right mt-1 text-xs text-amber-400"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0 lg:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-rose-200/40 bg-gradient-to-r from-rose-50/40 via-white/20 to-transparent px-4 py-3.5 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200/70 bg-rose-50 text-rose-600 shadow-sm">
                        <i class="bi bi-exclamation-triangle"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-rose-900">High priority</h2>
                        <p class="mt-0.5 text-xs text-rose-600/80 sm:text-sm">Urgent cases</p>
                    </div>
                </div>
                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl bg-rose-100 px-2.5 text-sm font-bold text-rose-700">
                    {{ count($dashboard['highPriority']) }}
                </span>
            </div>
            <div class="divide-y divide-rose-100/60">
                @forelse ($dashboard['highPriority'] as $case)
                    <a href="{{ $case['href'] }}"
                        class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-rose-50/50 sm:px-5">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                            <i class="bi bi-exclamation-triangle-fill text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-rose-950">{{ $case['title'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $case['meta'] }}</p>
                        </div>
                        <i class="bi bi-chevron-right mt-1 text-xs text-rose-300"></i>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center px-4 py-12 text-center sm:px-5">
                        <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <i class="bi bi-check-lg"></i>
                        </span>
                        <p class="text-sm font-medium text-slate-700">All clear</p>
                        <p class="mt-0.5 text-xs text-slate-500">No high-priority cases right now.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
            <div class="border-b border-white/50 bg-gradient-to-r from-slate-50/80 via-white/20 to-transparent px-4 py-3.5 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/70 bg-white/80 text-slate-600 shadow-sm">
                        <i class="bi bi-activity"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-slate-900">Recent activity</h2>
                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Latest support actions</p>
                    </div>
                </div>
            </div>
            <div class="max-h-[22rem] divide-y divide-white/40 overflow-y-auto">
                @forelse ($dashboard['recentActivity'] as $activity)
                    @php
                        $color = match ($activity['color']) {
                            'emerald' => 'bg-emerald-100 text-emerald-600',
                            'amber' => 'bg-amber-100 text-amber-600',
                            'blue' => 'bg-blue-100 text-blue-600',
                            default => 'bg-indigo-100 text-indigo-600',
                        };
                    @endphp
                    <div class="btn-smooth flex items-start gap-3 px-4 py-3 hover:bg-white/50 sm:px-5">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                            <i class="bi {{ $activity['icon'] }} text-sm"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ $activity['title'] }}</p>
                                <span class="shrink-0 text-[11px] font-medium text-slate-400">{{ $activity['time'] }}</span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $activity['meta'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-12 text-center text-sm text-slate-500 sm:px-5">No recent activity yet.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-12">
        <section id="cro-events-today" class="glass-card overflow-hidden !p-0 hover:!translate-y-0 xl:col-span-7">
            <div class="border-b border-white/50 bg-gradient-to-r from-cyan-50/50 via-white/20 to-transparent px-4 py-3.5 sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-cyan-200/70 bg-cyan-50/80 text-cyan-700 shadow-sm">
                        <i class="bi bi-calendar-event"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-slate-900">Events today</h2>
                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Live event support load</p>
                    </div>
                </div>
            </div>
            <div class="divide-y divide-white/40">
                @forelse ($dashboard['eventsToday'] as $event)
                    <div class="btn-smooth px-4 py-3.5 hover:bg-white/50 sm:px-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="font-semibold text-slate-900">{{ $event['name'] }}</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-lg border border-cyan-200/50 bg-cyan-50/70 px-2 py-0.5 text-[11px] font-semibold text-cyan-700">
                                    <i class="bi bi-people"></i>
                                    {{ number_format($event['attendees']) }} attendees
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-lg border border-amber-200/50 bg-amber-50/70 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                    <i class="bi bi-envelope"></i>
                                    {{ number_format($event['openInquiries']) }} inquiries
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-lg border border-rose-200/50 bg-rose-50/70 px-2 py-0.5 text-[11px] font-semibold text-rose-700">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    {{ number_format($event['pendingRefunds']) }} refunds
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center px-4 py-12 text-center sm:px-5">
                        <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="bi bi-calendar-x"></i>
                        </span>
                        <p class="text-sm text-slate-500">No events happening today.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="xl:col-span-5">
            <x-dashboard-mini-calendar :calendar="$dashboard['miniCalendar']" class="h-full hover:!translate-y-0" />
        </div>
    </div>
</div>

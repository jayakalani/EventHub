{{-- Today: onboarding, live ops, needs attention --}}
<div class="space-y-3">
            {{-- Onboarding checklist --}}
            @if ($onboarding['show'] ?? false)
                <section class="glass-panel overflow-hidden !rounded-2xl border-indigo-200/60">
                    <div class="border-b border-indigo-100/70 bg-gradient-to-r from-indigo-50/80 via-violet-50/40 to-cyan-50/30 px-4 py-3.5 sm:px-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-bold text-slate-900">Get started</h2>
                                    <span class="inline-flex rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">
                                        {{ $onboarding['completed_count'] }}/{{ $onboarding['total'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                    Create host → Create event → Add ticket categories → Publish
                                </p>
                            </div>
                            <div class="h-2 w-28 overflow-hidden rounded-full bg-indigo-100 sm:w-40">
                                <div class="progress-fill h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-500"
                                    style="--progress: {{ $onboarding['total'] > 0 ? round(($onboarding['completed_count'] / $onboarding['total']) * 100) : 0 }}%; --progress-delay: 80ms"></div>
                            </div>
                        </div>
                    </div>

                    <ol class="divide-y divide-indigo-50/80">
                        @foreach ($onboarding['steps'] as $index => $step)
                            <li class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span @class([
                                        'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-1',
                                        'bg-emerald-100 text-emerald-700 ring-emerald-200/80' => $step['done'],
                                        'bg-indigo-100 text-indigo-700 ring-indigo-200/80' => ! $step['done'] && empty($step['locked']),
                                        'bg-slate-100 text-slate-400 ring-slate-200/80' => ! $step['done'] && ! empty($step['locked']),
                                    ])>
                                        @if ($step['done'])
                                            <i class="bi bi-check-lg"></i>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p @class([
                                            'text-sm font-semibold',
                                            'text-slate-900' => ! $step['done'],
                                            'text-slate-500 line-through' => $step['done'],
                                        ])>
                                            {{ $step['label'] }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $step['description'] }}</p>
                                    </div>
                                </div>

                                @if ($step['done'])
                                    <span class="inline-flex items-center gap-1 self-start text-xs font-semibold text-emerald-600 sm:self-auto">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Done
                                    </span>
                                @elseif (! empty($step['locked']))
                                    <span class="inline-flex items-center gap-1 self-start text-xs font-semibold text-slate-400 sm:self-auto">
                                        <i class="bi bi-lock-fill"></i>
                                        Complete previous step
                                    </span>
                                @else
                                    <a href="{{ $step['url'] }}"
                                        class="btn-smooth inline-flex items-center gap-1.5 self-start rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 sm:self-auto">
                                        {{ $step['cta'] }}
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Day-of event operations --}}
            @if ($dayOfOps['active'] ?? false)
                <section class="glass-panel overflow-hidden !rounded-2xl border-cyan-200/60">
                    <div class="flex flex-col gap-3 border-b border-cyan-100/70 bg-gradient-to-r from-cyan-50/80 via-sky-50/50 to-indigo-50/40 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 shadow-sm ring-1 ring-cyan-200/70">
                                <i class="bi bi-door-open-fill"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-bold text-slate-900">Live today</h2>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-cyan-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                                        Door ops
                                    </span>
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-cyan-100 px-2 text-xs font-bold text-cyan-800">
                                        {{ $dayOfOps['count'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                    Check-in
                                    <span class="font-semibold text-slate-700">
                                        {{ number_format($dayOfOps['checked_in']) }}/{{ number_format($dayOfOps['sold']) }}
                                    </span>
                                    · {{ $dayOfOps['rate'] }}% admitted
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0">
                            <a href="{{ $dayOfOps['scan_url'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-cyan-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-qr-code-scan"></i>
                                Scan tickets
                            </a>
                            <a href="{{ $dayOfOps['guest_list_url'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-xl border border-white/70 bg-white/70 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-sm hover:bg-white sm:text-sm">
                                <i class="bi bi-people"></i>
                                Guest list
                            </a>
                        </div>
                    </div>

                    <div class="divide-y divide-cyan-100/60">
                        @foreach ($dayOfOps['events'] as $liveEvent)
                            <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ $liveEvent['url'] }}" class="truncate text-sm font-semibold text-slate-900 hover:text-cyan-700">
                                            {{ $liveEvent['name'] }}
                                        </a>
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                            'bg-blue-100 text-blue-800' => ($liveEvent['status'] ?? '') === 'ongoing',
                                            'bg-emerald-100 text-emerald-800' => ($liveEvent['status'] ?? '') !== 'ongoing',
                                        ])>
                                            {{ $liveEvent['status'] }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        <i class="bi bi-clock"></i> {{ $liveEvent['time'] }}
                                        @if ($liveEvent['place'])
                                            · {{ $liveEvent['place'] }}
                                        @endif
                                    </p>

                                    <div class="mt-2.5 max-w-sm">
                                        <div class="mb-1 flex items-center justify-between gap-2 text-[11px]">
                                            <span class="font-semibold text-slate-600">
                                                {{ number_format($liveEvent['checked_in']) }}/{{ number_format($liveEvent['sold']) }} checked in
                                            </span>
                                            <span class="font-bold text-cyan-700">{{ $liveEvent['rate'] }}%</span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-cyan-100/80">
                                            <div class="progress-fill h-full rounded-full bg-gradient-to-r from-cyan-500 to-sky-500"
                                                style="--progress: {{ min(100, $liveEvent['rate']) }}%; --progress-delay: {{ 80 + ($loop->index * 40) }}ms"></div>
                                        </div>
                                        @if ($liveEvent['awaiting'] > 0)
                                            <p class="mt-1 text-[11px] text-slate-400">
                                                {{ number_format($liveEvent['awaiting']) }} still awaiting entry
                                            </p>
                                        @elseif ($liveEvent['sold'] > 0)
                                            <p class="mt-1 text-[11px] font-medium text-emerald-600">
                                                All sold tickets checked in
                                            </p>
                                        @else
                                            <p class="mt-1 text-[11px] text-slate-400">No sold tickets yet</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ $liveEvent['scan_url'] }}"
                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-cyan-600/95 px-3 py-2 text-xs font-semibold text-white hover:bg-cyan-700">
                                        <i class="bi bi-qr-code-scan"></i>
                                        Scan
                                    </a>
                                    <a href="{{ $liveEvent['guest_list_url'] }}"
                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/70 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white">
                                        <i class="bi bi-list-ul"></i>
                                        Guest list
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Needs attention --}}
            @if (($needsAttention['count'] ?? 0) > 0)
                <section class="glass-panel overflow-hidden !rounded-2xl border-amber-200/60"
                    x-data="{ expanded: {{ ($needsAttention['count'] ?? 0) <= 3 ? 'true' : 'false' }} }">
                    <div class="flex flex-col gap-3 border-b border-amber-100/70 bg-gradient-to-r from-amber-50/80 via-orange-50/40 to-rose-50/30 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 shadow-sm ring-1 ring-amber-200/70">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-bold text-slate-900">Needs attention</h2>
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-600 px-2 text-xs font-bold text-white">
                                        {{ $needsAttention['count'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                    Low inventory, postponed TBA dates, and unpublished drafts
                                </p>
                            </div>
                        </div>

                        @if (($needsAttention['count'] ?? 0) > 3)
                            <button type="button"
                                @click="expanded = !expanded"
                                class="btn-smooth inline-flex items-center justify-center gap-1.5 self-start rounded-xl border border-white/70 bg-white/60 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-sm hover:bg-white/90 sm:self-auto">
                                <span x-text="expanded ? 'Show less' : 'Show all'"></span>
                                <i class="bi" :class="expanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </button>
                        @endif
                    </div>

                    <div class="divide-y divide-amber-100/60">
                        @foreach ($needsAttention['items'] as $item)
                            @php
                                $accent = match ($item['accent'] ?? 'amber') {
                                    'rose' => [
                                        'icon' => 'bg-rose-100 text-rose-600 ring-rose-200/80',
                                        'badge' => 'bg-rose-100 text-rose-700',
                                        'cta' => 'text-rose-700 hover:text-rose-800',
                                    ],
                                    'orange' => [
                                        'icon' => 'bg-orange-100 text-orange-600 ring-orange-200/80',
                                        'badge' => 'bg-orange-100 text-orange-700',
                                        'cta' => 'text-orange-700 hover:text-orange-800',
                                    ],
                                    'slate' => [
                                        'icon' => 'bg-slate-100 text-slate-600 ring-slate-200/80',
                                        'badge' => 'bg-slate-100 text-slate-700',
                                        'cta' => 'text-indigo-600 hover:text-indigo-700',
                                    ],
                                    default => [
                                        'icon' => 'bg-amber-100 text-amber-700 ring-amber-200/80',
                                        'badge' => 'bg-amber-100 text-amber-800',
                                        'cta' => 'text-amber-700 hover:text-amber-800',
                                    ],
                                };
                            @endphp
                            <a href="{{ $item['url'] }}"
                                class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-amber-50/40 sm:px-5"
                                @if ($loop->index >= 3)
                                    x-show="expanded"
                                    x-cloak
                                    x-transition
                                @endif>
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $accent['icon'] }}">
                                    <i class="bi {{ $item['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $accent['badge'] }}">
                                            {{ $item['badge'] }}
                                        </span>
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $item['message'] }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-[11px] font-medium text-slate-400">{{ $item['meta'] }}</p>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $accent['cta'] }}">
                                            {{ $item['cta'] }}
                                            <i class="bi bi-arrow-right text-[10px]"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! ($onboarding['show'] ?? false) && ! ($dayOfOps['active'] ?? false) && ($needsAttention['count'] ?? 0) === 0)
                <section class="glass-panel !rounded-2xl px-4 py-3 sm:px-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-900">You're clear for today</h2>
                            <p class="mt-0.5 text-sm text-slate-500">
                                No live door ops or attention items. Today's pulse is in the hero above — KPIs and goals continue below.
                            </p>
                        </div>
                    </div>
                </section>
            @endif
</div>

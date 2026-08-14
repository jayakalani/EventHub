{{-- Performance: KPIs, goal, event tables, operations --}}
<div class="space-y-3">
            {{-- 2. KPI snapshot --}}
            <section class="space-y-3">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-900">Performance snapshot</h2>
                    <p class="text-xs text-slate-500">
                        @if ($focusFilter['selectedEventId'] ?? $kpiFilter['selectedEventId'] ?? null)
                            Whole-event totals for
                            <span class="font-semibold text-slate-700">{{ $focusFilter['selectedEventName'] ?? $kpiFilter['selectedEventName'] }}</span>
                        @else
                            All-events monthly overview
                        @endif
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'emerald' => [
                                    'top' => 'border-t-emerald-500',
                                    'iconBg' => 'bg-emerald-100/70',
                                    'iconText' => 'text-emerald-600',
                                ],
                                'indigo' => [
                                    'top' => 'border-t-indigo-500',
                                    'iconBg' => 'bg-indigo-100/70',
                                    'iconText' => 'text-indigo-600',
                                ],
                                'blue' => [
                                    'top' => 'border-t-blue-500',
                                    'iconBg' => 'bg-blue-100/70',
                                    'iconText' => 'text-blue-600',
                                ],
                                'cyan' => [
                                    'top' => 'border-t-cyan-500',
                                    'iconBg' => 'bg-cyan-100/70',
                                    'iconText' => 'text-cyan-600',
                                ],
                                'rose' => [
                                    'top' => 'border-t-rose-500',
                                    'iconBg' => 'bg-rose-100/70',
                                    'iconText' => 'text-rose-600',
                                ],
                                'amber' => [
                                    'top' => 'border-t-amber-500',
                                    'iconBg' => 'bg-amber-100/70',
                                    'iconText' => 'text-amber-600',
                                ],
                                default => [
                                    'top' => 'border-t-slate-400',
                                    'iconBg' => 'bg-slate-100/70',
                                    'iconText' => 'text-slate-600',
                                ],
                            };
                            $showTrend = $kpi['showTrend'] ?? true;
                            $trendPositive = $kpi['trendUp'];
                            $trendClass = $trendPositive ? 'text-emerald-600' : 'text-rose-600';
                            $trendArrow = $trendPositive ? '▲' : '▼';
                        @endphp

                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-3 sm:p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-slate-500">
                                        <span aria-hidden="true">{{ $kpi['emoji'] }}</span>
                                        {{ $kpi['label'] }}
                                    </p>
                                    <p class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        {{ $kpi['value'] }}
                                    </p>
                                </div>
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $accent['iconBg'] }} transition-transform duration-300 ease-out group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>

                            <p class="mt-2 text-xs text-slate-500">
                                {{ $kpi['trendHint'] }}
                                @if ($showTrend && filled($kpi['trendLabel']))
                                    <span class="ml-1 font-bold {{ $trendClass }}">
                                        @if ($kpiFilter['selectedEventId'])
                                            {{ $kpi['trendLabel'] }}
                                        @else
                                            <span aria-hidden="true">{{ $trendArrow }}</span>{{ $kpi['trendLabel'] }}
                                        @endif
                                    </span>
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Analytics trends --}}
            @php
                $chartPeriods = $dashboard['charts']['periods'] ?? [];
                $defaultChartPeriod = $dashboard['charts']['defaultPeriod'] ?? 'month';
            @endphp
            <section class="space-y-3" x-data="{ chartPeriod: @js($defaultChartPeriod) }">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Sales analytics</h2>
                        <p class="text-xs text-slate-500">
                            Revenue and ticket trend for
                            <span data-chart-period-label>{{ $chartPeriods[$defaultChartPeriod]['label'] ?? 'This Month' }}</span>
                        </p>
                    </div>
                    <div class="inline-flex rounded-xl border border-white/70 bg-white/60 p-1 shadow-sm">
                        @foreach (['week' => '7 days', 'month' => '30 days'] as $periodKey => $periodLabel)
                            <button type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="chartPeriod === '{{ $periodKey }}'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-white/80'"
                                @click="chartPeriod = '{{ $periodKey }}'; window.dispatchEvent(new CustomEvent('organizer-chart-period', { detail: { period: '{{ $periodKey }}' } }))">
                                {{ $periodLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-2">
                    <div class="glass-card p-3 sm:p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-slate-500">Revenue</p>
                                <p class="mt-0.5 text-lg font-bold tracking-tight text-slate-900" data-chart-total="revenue">
                                    {{ $chartPeriods[$defaultChartPeriod]['revenue']['totalFormatted'] ?? '0' }}
                                </p>
                            </div>
                            <span data-chart-change="revenue"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold {{ ($chartPeriods[$defaultChartPeriod]['revenue']['up'] ?? true) ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                {{ ($chartPeriods[$defaultChartPeriod]['revenue']['up'] ?? true) ? '▲' : '▼' }}
                                {{ abs((float) ($chartPeriods[$defaultChartPeriod]['revenue']['changePercent'] ?? 0)) }}%
                            </span>
                        </div>
                        <div class="mt-4 h-56">
                            <canvas id="organizerRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="glass-card p-3 sm:p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-slate-500">Tickets sold</p>
                                <p class="mt-0.5 text-lg font-bold tracking-tight text-slate-900" data-chart-total="tickets">
                                    {{ $chartPeriods[$defaultChartPeriod]['tickets']['totalFormatted'] ?? '0' }}
                                </p>
                            </div>
                            <span data-chart-change="tickets"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold {{ ($chartPeriods[$defaultChartPeriod]['tickets']['up'] ?? true) ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                {{ ($chartPeriods[$defaultChartPeriod]['tickets']['up'] ?? true) ? '▲' : '▼' }}
                                {{ abs((float) ($chartPeriods[$defaultChartPeriod]['tickets']['changePercent'] ?? 0)) }}%
                            </span>
                        </div>
                        <div class="mt-4 h-56">
                            <canvas id="organizerTicketSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3. Revenue Goal --}}
            @php
                $isPeriodGoal = ($revenueGoal['mode'] ?? '') === 'period';
                $periodGoals = $revenueGoal['goals'] ?? [];
                $goalFormOpen = $errors->has('revenue_goal')
                    || $errors->has('starts_at')
                    || $errors->has('ends_at');
                $eventGoalProgress = min(100, (float) ($revenueGoal['progress'] ?? 0));
                $eventGoalRing = 2 * M_PI * 42;
                $eventGoalOffset = $eventGoalRing * (1 - ($eventGoalProgress / 100));
            @endphp
            <section class="glass-panel overflow-hidden !rounded-2xl border-emerald-200/60"
                x-data="{ editing: {{ $goalFormOpen ? 'true' : 'false' }} }">
                <div class="flex flex-col gap-3 border-b border-emerald-100/70 bg-gradient-to-r from-emerald-50/90 via-teal-50/45 to-cyan-50/35 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 shadow-sm ring-1 ring-emerald-200/70">
                            <i class="bi bi-bullseye"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Revenue Goal</h2>
                                <span class="inline-flex rounded-full bg-emerald-600/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                    @if ($isPeriodGoal)
                                        All Events
                                    @else
                                        Event
                                    @endif
                                </span>
                                @if ($isPeriodGoal && count($periodGoals) > 0)
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-emerald-100 px-2 text-xs font-bold text-emerald-800">
                                        {{ count($periodGoals) }}
                                    </span>
                                @elseif (! $isPeriodGoal && ($revenueGoal['achieved'] ?? false))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                        <i class="bi bi-check-lg"></i>
                                        Reached
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                @if ($isPeriodGoal)
                                    Custom date-range targets across all your events
                                @else
                                    Sales target for
                                    <span class="font-semibold text-slate-700">{{ $revenueGoal['label'] ?? 'this event' }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end">
                        <button type="button"
                            @click="editing = !editing"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 hover:shadow-md sm:text-sm">
                            <i class="bi" :class="editing ? 'bi-x-lg' : 'bi-plus-lg'"></i>
                            <span x-text="editing ? 'Cancel' : '{{ $isPeriodGoal ? 'Add Goal' : 'Set Goal' }}'"></span>
                        </button>
                    </div>
                </div>

                {{-- Inline editor --}}
                <div class="border-b border-emerald-100/60 bg-white/55 px-4 py-4 sm:px-5"
                    x-show="editing"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    @if ($isPeriodGoal)
                        <form method="POST" action="{{ route('organizer.revenue-goal.update') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                            @csrf
                            @method('PUT')
                            @foreach ($filterQuery as $key => $value)
                                @if ($key !== 'goal_event')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            @if (! array_key_exists('focus_event', $filterQuery))
                                <input type="hidden" name="focus_event" value="">
                            @endif

                            <div>
                                <label for="revenue_goal" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Amount (LKR)</label>
                                <input id="revenue_goal"
                                    type="number"
                                    name="revenue_goal"
                                    min="1000"
                                    step="1000"
                                    value="{{ old('revenue_goal') }}"
                                    placeholder="e.g. 500000"
                                    class="mt-1.5 w-full rounded-xl border-slate-300/80 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required>
                                @error('revenue_goal')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="goal_starts_at" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Start date</label>
                                <input id="goal_starts_at"
                                    type="date"
                                    name="starts_at"
                                    value="{{ old('starts_at') }}"
                                    class="mt-1.5 w-full rounded-xl border-slate-300/80 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required>
                                @error('starts_at')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="goal_ends_at" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">End date</label>
                                <input id="goal_ends_at"
                                    type="date"
                                    name="ends_at"
                                    value="{{ old('ends_at') }}"
                                    class="mt-1.5 w-full rounded-xl border-slate-300/80 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required>
                                @error('ends_at')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 hover:shadow-md">
                                <i class="bi bi-check2-circle"></i>
                                Save goal
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('organizer.revenue-goal.update') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            @method('PUT')
                            @foreach ($filterQuery as $key => $value)
                                @if ($key !== 'goal_event')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            @if (! array_key_exists('focus_event', $filterQuery))
                                <input type="hidden" name="focus_event" value="">
                            @endif
                            @if (! empty($revenueGoal['selectedEventId']))
                                <input type="hidden" name="goal_event" value="{{ $revenueGoal['selectedEventId'] }}">
                            @endif

                            <div class="min-w-0 flex-1">
                                <label for="revenue_goal_event" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event goal (LKR)</label>
                                <input id="revenue_goal_event"
                                    type="number"
                                    name="revenue_goal"
                                    min="1000"
                                    step="1000"
                                    value="{{ old('revenue_goal', (int) $revenueGoal['goal']) }}"
                                    class="mt-1.5 w-full rounded-xl border-slate-300/80 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required>
                                @error('revenue_goal')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                class="btn-smooth inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 hover:shadow-md">
                                <i class="bi bi-check2-circle"></i>
                                Save goal
                            </button>
                        </form>
                    @endif
                </div>

                @if ($isPeriodGoal)
                    <div class="p-4">
                        @if (count($periodGoals) === 0)
                            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200/80 bg-gradient-to-b from-emerald-50/40 to-white/30 px-4 py-10 text-center">
                                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200/70">
                                    <i class="bi bi-calendar2-range text-xl"></i>
                                </span>
                                <p class="text-sm font-semibold text-slate-800">No date-range goals yet</p>
                                <p class="mt-1 max-w-sm text-xs text-slate-500">
                                    Set a start date, end date, and target amount to track all-events revenue over any period.
                                </p>
                                <button type="button"
                                    @click="editing = true"
                                    class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                    <i class="bi bi-plus-lg"></i>
                                    Create your first goal
                                </button>
                            </div>
                        @else
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($periodGoals as $index => $periodGoal)
                                    @php
                                        $ring = 2 * M_PI * 34;
                                        $progress = min(100, (float) $periodGoal['progress']);
                                        $offset = $ring * (1 - ($progress / 100));
                                        $isActive = (bool) ($periodGoal['is_active'] ?? false);
                                        $isAchieved = (bool) ($periodGoal['achieved'] ?? false);
                                    @endphp
                                    <article @class([
                                        'group relative overflow-hidden rounded-2xl border bg-white/70 p-4 shadow-sm backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md',
                                        'border-emerald-300/80 ring-1 ring-emerald-200/50' => $isActive,
                                        'border-emerald-200/70' => $isAchieved && ! $isActive,
                                        'border-white/80' => ! $isActive && ! $isAchieved,
                                    ])>
                                        <div @class([
                                            'absolute inset-y-0 left-0 w-1',
                                            'bg-emerald-500' => $isActive,
                                            'bg-teal-400' => $isAchieved && ! $isActive,
                                            'bg-slate-200' => ! $isActive && ! $isAchieved,
                                        ])></div>

                                        <div class="flex items-start gap-3 pl-2">
                                            <div class="relative h-[4.5rem] w-[4.5rem] shrink-0">
                                                <svg class="h-full w-full -rotate-90" viewBox="0 0 84 84" aria-hidden="true">
                                                    <circle cx="42" cy="42" r="34" fill="none" stroke="currentColor" stroke-width="7" class="text-emerald-100" />
                                                    <circle cx="42" cy="42" r="34" fill="none" stroke="url(#goalRing{{ $periodGoal['id'] }})" stroke-width="7"
                                                        stroke-linecap="round"
                                                        stroke-dasharray="{{ $ring }}"
                                                        stroke-dashoffset="{{ $offset }}"
                                                        class="transition-[stroke-dashoffset] duration-700 ease-out" />
                                                    <defs>
                                                        <linearGradient id="goalRing{{ $periodGoal['id'] }}" x1="0%" y1="0%" x2="100%" y2="0%">
                                                            <stop offset="0%" stop-color="#10b981" />
                                                            <stop offset="100%" stop-color="#06b6d4" />
                                                        </linearGradient>
                                                    </defs>
                                                </svg>
                                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                    <span class="text-sm font-bold leading-none text-emerald-700">{{ number_format($progress, $progress == (int) $progress ? 0 : 1) }}%</span>
                                                </div>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            @if ($isActive)
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                                                    Active
                                                                </span>
                                                            @endif
                                                            @if ($isAchieved)
                                                                <span class="inline-flex rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                                                    Reached
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="mt-1.5 flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                                            <i class="bi bi-calendar3 text-emerald-600/80"></i>
                                                            <span class="truncate">{{ $periodGoal['label'] }}</span>
                                                        </p>
                                                    </div>

                                                    <form method="POST" action="{{ route('organizer.revenue-goal.destroy', $periodGoal['id']) }}"
                                                        onsubmit="return confirm('Remove this revenue goal?');"
                                                        class="shrink-0 opacity-70 transition group-hover:opacity-100">
                                                        @csrf
                                                        @method('DELETE')
                                                        @foreach ($filterQuery as $key => $value)
                                                            @if ($key !== 'goal_event')
                                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                            @endif
                                                        @endforeach
                                                        @if (! array_key_exists('focus_event', $filterQuery))
                                                            <input type="hidden" name="focus_event" value="">
                                                        @endif
                                                        <button type="submit"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                                            title="Remove goal"
                                                            aria-label="Remove goal">
                                                            <i class="bi bi-trash3 text-sm"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <div class="mt-3 grid grid-cols-2 gap-2">
                                                    <div class="rounded-xl bg-emerald-50/70 px-2.5 py-2">
                                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700/80">Earned</p>
                                                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">LKR {{ number_format($periodGoal['current'], 0) }}</p>
                                                    </div>
                                                    <div class="rounded-xl bg-slate-50/80 px-2.5 py-2">
                                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Target</p>
                                                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">LKR {{ number_format($periodGoal['goal'], 0) }}</p>
                                                    </div>
                                                </div>

                                                <p class="mt-2 text-[11px] text-slate-500">
                                                    @if ($isAchieved)
                                                        <span class="font-medium text-emerald-600">Target complete for this period</span>
                                                    @else
                                                        LKR {{ number_format($periodGoal['remaining'], 0) }} remaining
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-4">
                        <div class="grid gap-3 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-center">
                            <div class="flex justify-center lg:justify-start">
                                <div class="relative h-32 w-32 sm:h-36 sm:w-36">
                                    <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                                        <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="8" class="text-emerald-100" />
                                        <circle cx="50" cy="50" r="42" fill="none" stroke="url(#eventGoalRing)" stroke-width="8"
                                            stroke-linecap="round"
                                            stroke-dasharray="{{ $eventGoalRing }}"
                                            stroke-dashoffset="{{ $eventGoalOffset }}"
                                            class="transition-[stroke-dashoffset] duration-700 ease-out" />
                                        <defs>
                                            <linearGradient id="eventGoalRing" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#10b981" />
                                                <stop offset="100%" stop-color="#06b6d4" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-2xl font-bold tracking-tight text-emerald-700 sm:text-3xl">
                                            {{ number_format($eventGoalProgress, $eventGoalProgress == (int) $eventGoalProgress ? 0 : 1) }}%
                                        </span>
                                        <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Complete</span>
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 space-y-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue earned</p>
                                    <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                                        LKR {{ number_format($revenueGoal['current'], 0) }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        of
                                        <span class="font-semibold text-slate-700">LKR {{ number_format($revenueGoal['goal'], 0) }}</span>
                                        goal
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <div class="rounded-xl border border-emerald-100/80 bg-emerald-50/60 px-3 py-2.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700/80">Progress</p>
                                        <p class="mt-0.5 text-sm font-bold text-slate-900">{{ number_format($eventGoalProgress, 1) }}%</p>
                                    </div>
                                    <div class="rounded-xl border border-white/80 bg-white/70 px-3 py-2.5">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Remaining</p>
                                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">
                                            LKR {{ number_format($revenueGoal['remaining'], 0) }}
                                        </p>
                                    </div>
                                    <div class="col-span-2 rounded-xl border border-white/80 bg-white/70 px-3 py-2.5 sm:col-span-1">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Status</p>
                                        <p @class([
                                            'mt-0.5 text-sm font-bold',
                                            'text-emerald-700' => $revenueGoal['achieved'] ?? false,
                                            'text-slate-700' => ! ($revenueGoal['achieved'] ?? false),
                                        ])>
                                            {{ ($revenueGoal['achieved'] ?? false) ? 'Goal reached' : 'In progress' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="h-2.5 overflow-hidden rounded-full bg-emerald-100/80">
                                    <div class="progress-fill h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"
                                        style="--progress: {{ $eventGoalProgress }}%; --progress-delay: 120ms"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            {{-- 4. Performance + status --}}
            <section class="grid gap-3 xl:grid-cols-12">
                <div class="glass-panel overflow-hidden xl:col-span-8"
                    x-data="{ showCompleted: false }">
                    <div class="flex flex-col gap-2 border-b border-white/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Event Performance</h2>
                            <p class="text-sm text-slate-500">Live and upcoming events first</p>
                        </div>
                        <a href="{{ route('organizer.events.index') }}"
                            class="btn-smooth inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Manage events
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-white/40 text-xs font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-4">Event</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Sold</th>
                                    <th class="px-3 py-3">Remaining Tickets</th>
                                    <th class="px-3 py-3">Fill</th>
                                    <th class="bg-rose-50/70 px-4 py-2.5 text-right font-semibold text-rose-700">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($performance as $row)
                                    @include('organizer.partials.performance-row', [
                                        'row' => $row,
                                        'delay' => 80 + ($loop->index * 40),
                                    ])
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-14 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                                <i class="bi bi-calendar-plus text-xl"></i>
                                            </div>
                                            <p class="mt-3 text-sm font-semibold text-slate-800">No live events yet</p>
                                            @if ($onboarding['show'] ?? false)
                                                <p class="mt-1 text-xs text-slate-500">Follow the get-started checklist above.</p>
                                            @endif
                                            <a href="{{ route('organizer.events.create') }}"
                                                class="mt-2 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                Create an event
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (count($performanceCompleted) > 0)
                        <div class="border-t border-white/50">
                            <button type="button"
                                @click="showCompleted = !showCompleted"
                                class="btn-smooth flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm font-semibold text-slate-600 hover:bg-white/40 sm:px-4">
                                <span>
                                    Completed & cancelled
                                    <span class="ml-1 font-medium text-slate-400">({{ count($performanceCompleted) }})</span>
                                </span>
                                <i class="bi text-xs" :class="showCompleted ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </button>
                            <div class="overflow-x-auto" x-show="showCompleted" x-cloak x-transition>
                                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($performanceCompleted as $row)
                                            @include('organizer.partials.performance-row', [
                                                'row' => $row,
                                                'delay' => 40,
                                                'rowClass' => 'bg-slate-50/40',
                                            ])
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="space-y-3 xl:col-span-4">
                    <div class="glass-panel p-4">
                        <h2 class="text-lg font-bold text-slate-900">Event Status</h2>
                        <p class="mt-0.5 text-sm text-slate-500">How your catalog is distributed</p>

                        <div class="mt-3 space-y-2.5">
                            @foreach($statusSummary as $status)
                                @php
                                    $pct = round(($status['count'] / $totalEvents) * 100);
                                    $bar = match ($status['color']) {
                                        'emerald' => 'bg-emerald-500',
                                        'blue' => 'bg-blue-500',
                                        'amber' => 'bg-amber-400',
                                        'orange' => 'bg-orange-500',
                                        'rose' => 'bg-rose-500',
                                        'slate' => 'bg-slate-400',
                                        default => 'bg-slate-400',
                                    };
                                    $text = match ($status['color']) {
                                        'emerald' => 'text-emerald-700',
                                        'blue' => 'text-blue-700',
                                        'amber' => 'text-amber-700',
                                        'orange' => 'text-orange-700',
                                        'rose' => 'text-rose-700',
                                        'slate' => 'text-slate-600',
                                        default => 'text-slate-600',
                                    };
                                    $track = match ($status['color']) {
                                        'emerald' => 'bg-emerald-100',
                                        'blue' => 'bg-blue-100',
                                        'amber' => 'bg-amber-100',
                                        'orange' => 'bg-orange-100',
                                        'rose' => 'bg-rose-100',
                                        'slate' => 'bg-slate-200',
                                        default => 'bg-slate-100',
                                    };
                                @endphp
                                <div class="flex items-center gap-3">
                                    <span class="w-24 shrink-0 text-xs font-semibold text-slate-600">{{ $status['label'] }}</span>
                                    <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full {{ $track }}">
                                        <div class="progress-fill h-full rounded-full {{ $bar }}"
                                            style="--progress: {{ $pct }}%; --progress-delay: {{ 100 + ($loop->index * 60) }}ms"></div>
                                    </div>
                                    <span class="w-14 shrink-0 text-right text-xs font-bold {{ $text }}">
                                        {{ $status['count'] }}
                                        <span class="font-medium text-slate-400">{{ $pct }}%</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <x-dashboard-mini-calendar :calendar="$dashboard['miniCalendar']" />
                </aside>
            </section>

            {{-- 5. Operations --}}
            <section class="grid gap-3 lg:grid-cols-3">
                <div class="glass-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/50 px-4 py-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Upcoming</h2>
                            <p class="text-xs text-slate-500">
                                @if ($nextUpcomingEvent)
                                    Next: {{ $nextUpcomingEvent['day_label'] }}
                                @else
                                    Next on your schedule
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('organizer.calendar.index') }}"
                            class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-700">Calendar</a>
                    </div>

                    @if ($nextUpcomingEvent)
                        <div class="border-b border-indigo-100/70 bg-gradient-to-r from-indigo-50/70 to-cyan-50/40 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="inline-flex rounded-full bg-indigo-600/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                        Next up
                                    </span>
                                    <h3 class="mt-1.5 truncate text-sm font-bold text-slate-900">{{ $nextUpcomingEvent['name'] }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        <i class="bi bi-clock"></i> {{ $nextUpcomingEvent['time'] }}
                                        @if ($nextUpcomingEvent['place'])
                                            · {{ $nextUpcomingEvent['place'] }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ $nextUpcomingEvent['manage_url'] }}"
                                    class="btn-smooth shrink-0 rounded-lg bg-indigo-600/95 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700">
                                    Manage
                                </a>
                            </div>

                            @if (count($nextUpcomingEvent['categories'] ?? []) > 0)
                                <div class="mt-3 space-y-1.5">
                                    @foreach ($nextUpcomingEvent['categories'] as $category)
                                        @php
                                            $categoryColor = $category['color'] ?? '#6366f1';
                                        @endphp
                                        <div class="flex items-center justify-between gap-2 rounded-lg border border-white/60 bg-white/55 px-2.5 py-1.5"
                                            style="border-left: 3px solid {{ $categoryColor }};">
                                            <p class="truncate text-xs font-semibold text-slate-700">{{ $category['name'] }}</p>
                                            <p @class([
                                                'shrink-0 text-xs font-bold',
                                                'text-rose-600' => $category['remaining'] === 0,
                                                'text-amber-700' => $category['remaining'] > 0 && $category['remaining'] <= 10,
                                                'text-slate-700' => $category['remaining'] > 10,
                                            ])>
                                                {{ number_format($category['remaining']) }} left
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="max-h-[22rem] divide-y divide-white/40 overflow-y-auto">
                        @php
                            $upcomingList = collect($upcomingEvents)
                                ->when($nextUpcomingEvent, fn ($items) => $items->reject(
                                    fn ($event) => (int) ($event['id'] ?? 0) === (int) ($nextUpcomingEvent['id'] ?? 0)
                                ))
                                ->values();
                        @endphp
                        @forelse($upcomingList as $event)
                            <a href="{{ $event['url'] }}" class="btn-smooth flex gap-3 px-4 py-3 hover:bg-white/45">
                                <div @class([
                                    'flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl border backdrop-blur-sm',
                                    'border-orange-200/80 bg-orange-50/80 text-orange-700' => ($event['status'] ?? '') === 'postponed',
                                    'border-white/60 bg-indigo-50/80 text-indigo-700' => ($event['status'] ?? '') !== 'postponed',
                                ])>
                                    @if (($event['status'] ?? '') === 'postponed' && ($event['date_tba'] ?? false))
                                        <span class="text-[9px] font-bold uppercase leading-none">TBA</span>
                                    @else
                                        <span class="text-[10px] font-semibold uppercase leading-none">{{ $event['month'] }}</span>
                                        <span class="mt-0.5 text-base font-bold leading-none">{{ $event['day'] }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $event['name'] }}</p>
                                        @if (($event['status'] ?? '') === 'postponed')
                                            <span class="shrink-0 rounded-full bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-orange-700">Postponed</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        @if (($event['status'] ?? '') === 'postponed' && ($event['date_tba'] ?? false))
                                            Date yet to be scheduled
                                        @else
                                            @if($event['time']) {{ $event['time'] }} · @endif
                                            {{ $event['place'] ?? 'Venue TBD' }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ number_format($event['sold']) }}/{{ number_format($event['capacity']) }} sold
                                    </p>
                                </div>
                            </a>
                        @empty
                            @if (! $nextUpcomingEvent)
                                <p class="px-5 py-10 text-center text-sm text-slate-500">No upcoming events.</p>
                            @else
                                <p class="px-5 py-6 text-center text-xs text-slate-400">No other upcoming events.</p>
                            @endif
                        @endforelse
                    </div>
                </div>

                <div class="glass-panel overflow-hidden" data-live-sales>
                    <div class="flex items-center justify-between border-b border-white/50 px-4 py-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Recent Sales</h2>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200/70"
                                    data-live-pulse-badge
                                    title="Auto-refreshes every 20s">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                    Live
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">
                                Latest ticket purchases
                                <span class="text-slate-400" data-live-refreshed></span>
                            </p>
                        </div>
                        <a href="{{ route('organizer.sales.index') }}"
                            class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-white/40 overflow-y-auto" data-live-sales-list>
                        @forelse($recentPurchases as $purchase)
                            <a href="{{ $purchase['url'] }}" class="btn-smooth flex items-start gap-3 px-4 py-3 hover:bg-white/45">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/60 bg-emerald-50/80 text-sm font-bold text-emerald-700 backdrop-blur-sm">
                                    <i class="bi bi-ticket-perforated"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="truncate font-mono text-sm font-semibold text-slate-900">{{ $purchase['ticket_number'] ?? '—' }}</p>
                                        <p class="shrink-0 text-[11px] font-medium text-slate-400">{{ $purchase['booked_at'] }}</p>
                                    </div>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        @foreach($purchase['category_badges'] ?? [['label' => $purchase['category'] ?? 'General', 'color' => '#6366f1']] as $badge)
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-black/5"
                                                style="background-color: {{ ($badge['color'] ?? '#6366f1') }}18;">
                                                <span class="h-1.5 w-1.5 rounded-full"
                                                    style="background-color: {{ $badge['color'] ?? '#6366f1' }}"></span>
                                                {{ $badge['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between gap-3">
                                        <p class="truncate text-xs text-slate-500">{{ $purchase['event'] }}</p>
                                        <p class="shrink-0 text-sm font-bold text-slate-900">
                                            LKR {{ number_format($purchase['amount'], 0) }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-4" data-live-sales-empty>
                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="glass-panel overflow-hidden">
                    <div class="border-b border-white/50 px-4 py-3">
                        <h2 class="text-base font-bold text-slate-900">Recent Activity</h2>
                        <p class="text-xs text-slate-500">Updates and bookings</p>
                        <div class="mt-2.5 flex flex-wrap gap-x-3 gap-y-1">
                            @foreach ([
                                ['dot' => 'bg-emerald-500', 'label' => 'Ticket Purchased'],
                                ['dot' => 'bg-blue-500', 'label' => 'Event Updated'],
                                ['dot' => 'bg-violet-500', 'label' => 'Event Created'],
                                ['dot' => 'bg-amber-500', 'label' => 'Ticket Refunded'],
                            ] as $legend)
                                <div class="flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $legend['dot'] }}"></span>
                                    <span class="text-[10px] font-medium text-slate-500">{{ $legend['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="max-h-[28rem] space-y-0 overflow-y-auto px-5 py-2">
                        @forelse($recentActivity as $item)
                            @php
                                $iconStyles = match ($item['color']) {
                                    'emerald' => 'bg-emerald-100/90 text-emerald-600 ring-emerald-200/80',
                                    'rose' => 'bg-rose-100/90 text-rose-600 ring-rose-200/80',
                                    'blue' => 'bg-blue-100/90 text-blue-600 ring-blue-200/80',
                                    'indigo' => 'bg-indigo-100/90 text-indigo-600 ring-indigo-200/80',
                                    'amber' => 'bg-amber-100/90 text-amber-600 ring-amber-200/80',
                                    'violet' => 'bg-violet-100/90 text-violet-600 ring-violet-200/80',
                                    'cyan' => 'bg-cyan-100/90 text-cyan-600 ring-cyan-200/80',
                                    default => 'bg-slate-100/90 text-slate-600 ring-slate-200/80',
                                };
                                $titleStyles = match ($item['color']) {
                                    'emerald' => 'text-emerald-700',
                                    'rose' => 'text-rose-700',
                                    'blue' => 'text-blue-700',
                                    'indigo' => 'text-indigo-700',
                                    'amber' => 'text-amber-700',
                                    'violet' => 'text-violet-700',
                                    'cyan' => 'text-cyan-700',
                                    default => 'text-slate-900',
                                };
                            @endphp
                            <a href="{{ $item['url'] }}" class="btn-smooth group relative flex gap-3 rounded-xl py-3.5 hover:bg-white/45">
                                @if(! $loop->last)
                                    <span class="absolute left-4 top-11 bottom-0 w-px bg-white/60"></span>
                                @endif
                                <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1 backdrop-blur-sm {{ $iconStyles }}">
                                    <i class="bi {{ $item['icon'] }} text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-bold {{ $titleStyles }}">{{ $item['title'] }}</p>
                                        <span class="shrink-0 text-[11px] text-slate-400">{{ $item['time'] }}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['description'] }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="py-10 text-center text-sm text-slate-500">No recent activity.</p>
                        @endforelse
                    </div>
                </div>
            </section>
</div>

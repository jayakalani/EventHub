<x-app-layout>
    @php
        $stats = $dashboard['stats'];
        $todaySummary = $dashboard['todaySummary'];
        $kpis = $dashboard['kpis'];
        $kpiFilter = $dashboard['kpiFilter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => []];
        $chartFilter = $dashboard['chartFilter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => []];
        $engagement = $dashboard['engagement'];
        $engagementFilter = $engagement['filter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => $chartFilter['events'] ?? []];
        $revenueGoal = $dashboard['revenueGoal'];
        $statusSummary = $dashboard['statusSummary'];
        $performance = $dashboard['performance'];
        $upcomingEvents = $dashboard['upcomingEvents'];
        $nextUpcomingEvent = $dashboard['nextUpcomingEvent'] ?? null;
        $recentPurchases = $dashboard['recentPurchases'];
        $recentActivity = $dashboard['recentActivity'];
        $totalEvents = max(1, $stats['totalEvents']);
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'there';
        $initials = strtoupper(substr($user?->first_name ?? 'O', 0, 1) . substr($user?->last_name ?? '', 0, 1));
    @endphp

    <div class="organizer-dashboard relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            open: false,
            chartKey: null,
            title: '',
            description: '',
            chartPeriod: @js($dashboard['charts']['defaultPeriod'] ?? 'month'),
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('organizer-chart-expand', {
                        detail: { key, period: this.chartPeriod },
                    }));
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('organizer-chart-collapse'));
            },
            setChartPeriod(period) {
                this.chartPeriod = period;
                window.dispatchEvent(new CustomEvent('organizer-chart-period', {
                    detail: { period },
                }));
            },
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/45 to-cyan-50/55"></div>
            <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-36 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-emerald-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-50"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="glass-card !rounded-xl border-emerald-200/80 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-emerald-800"
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 5000)"
                    x-transition.opacity>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-check-circle-fill text-emerald-600"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- 1. Hero --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/30 blur-2xl"></div>

                    <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                @if ($user?->profile_photo)
                                    <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                        alt="{{ $displayName }}"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white/80 shadow-sm sm:h-10 sm:w-10">
                                @else
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-xs font-bold text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10 sm:text-sm">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $greeting }}, {{ $displayName }}
                                    </p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Organizer Dashboard
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Events, ticket sales, and revenue · {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="organizer.dashboard.export.pdf"
                                :params="request()->only([
                                    'kpi_event',
                                    'goal_event',
                                    'chart_event',
                                    'engagement_event',
                                ])"
                                :charts="[
                                    ['canvasId' => 'organizerRevenueChart', 'title' => 'Analytics — Revenue'],
                                    ['canvasId' => 'organizerTicketSalesChart', 'title' => 'Analytics — Ticket Sales'],
                                ]"
                            />
                            <a href="{{ route('organizer.events.create') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-plus-lg"></i>
                                New Event
                            </a>
                            <a href="{{ route('organizer.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-graph-up-arrow"></i>
                                Reports
                            </a>
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>

                        <div class="grid min-w-0 flex-1 grid-cols-3 gap-2">
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-indigo-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-indigo-100/80 text-sm text-indigo-600 sm:flex">
                                    <i class="bi bi-calendar-event"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ number_format($todaySummary['eventsToday']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Events</p>
                                </div>
                            </div>
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-blue-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-blue-100/80 text-sm text-blue-600 sm:flex">
                                    <i class="bi bi-ticket-perforated"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ number_format($todaySummary['ticketsSold']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Tickets</p>
                                </div>
                            </div>
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-emerald-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-emerald-100/80 text-sm text-emerald-600 sm:flex">
                                    <i class="bi bi-cash-stack"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">LKR {{ number_format($todaySummary['revenue'], 0) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-wrap gap-1.5">
                        @foreach ([
                            ['label' => 'Manage Events', 'route' => route('organizer.events.index'), 'icon' => 'bi-calendar-event'],
                            ['label' => 'Calendar', 'route' => route('organizer.calendar.index'), 'icon' => 'bi-calendar3'],
                            ['label' => 'Hosts', 'route' => route('organizer.hosts'), 'icon' => 'bi-building'],
                            ['label' => 'Attendees', 'route' => route('organizer.reports', ['tab' => 'attendees']), 'icon' => 'bi-people'],
                        ] as $shortcut)
                            <a href="{{ $shortcut['route'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 backdrop-blur-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm">
                                <i class="bi {{ $shortcut['icon'] }}"></i>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- 2. KPI snapshot --}}
            <section class="space-y-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-slate-900">Performance snapshot</h2>
                        <p class="text-xs text-slate-500">
                            @if ($kpiFilter['selectedEventId'])
                                Whole-event totals for
                                <span class="font-semibold text-slate-700">{{ $kpiFilter['selectedEventName'] }}</span>
                            @else
                                All-events monthly overview
                            @endif
                        </p>
                    </div>

                    <form method="GET" action="{{ route('organizer.dashboard') }}" class="sm:w-72">
                        @if (request()->filled('goal_event'))
                            <input type="hidden" name="goal_event" value="{{ request('goal_event') }}">
                        @endif
                        @if (request()->filled('chart_event'))
                            <input type="hidden" name="chart_event" value="{{ request('chart_event') }}">
                        @endif
                        @if (request()->filled('engagement_event'))
                            <input type="hidden" name="engagement_event" value="{{ request('engagement_event') }}">
                        @endif
                        <label for="kpi_event" class="sr-only">Filter KPIs by event</label>
                        <select
                            id="kpi_event"
                            name="kpi_event"
                            class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Events</option>
                            @foreach ($kpiFilter['events'] as $eventOption)
                                <option value="{{ $eventOption['id'] }}"
                                    @selected((int) ($kpiFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </form>
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
                                'rose' => [
                                    'top' => 'border-t-rose-500',
                                    'iconBg' => 'bg-rose-100/70',
                                    'iconText' => 'text-rose-600',
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

                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
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

            {{-- 3. Revenue Goal --}}
            <section class="glass-panel overflow-hidden p-5 sm:p-6"
                x-data="{ editing: {{ $errors->has('revenue_goal') || $errors->has('monthly_revenue_goal') ? 'true' : 'false' }} }">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Revenue Goal</h2>
                            <span class="rounded-full border border-emerald-200/70 bg-emerald-100/80 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 backdrop-blur-sm">
                                {{ $revenueGoal['label'] ?? $revenueGoal['month_label'] ?? now()->format('F Y') }}
                            </span>
                            @if($revenueGoal['achieved'])
                                <span class="rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white shadow-sm">
                                    Goal reached
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $revenueGoal['description'] ?? 'Track progress toward your monthly sales target.' }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <form method="GET" action="{{ route('organizer.dashboard') }}" class="sm:w-64">
                            @if (request()->filled('kpi_event'))
                                <input type="hidden" name="kpi_event" value="{{ request('kpi_event') }}">
                            @endif
                            @if (request()->filled('chart_event'))
                                <input type="hidden" name="chart_event" value="{{ request('chart_event') }}">
                            @endif
                            @if (request()->filled('engagement_event'))
                                <input type="hidden" name="engagement_event" value="{{ request('engagement_event') }}">
                            @endif
                            <label for="goal_event" class="sr-only">Filter revenue goal by event</label>
                            <select
                                id="goal_event"
                                name="goal_event"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">All Events</option>
                                @foreach (($revenueGoal['events'] ?? []) as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($revenueGoal['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <button type="button"
                            @click="editing = !editing"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/70 bg-white/55 px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-sm hover:border-emerald-200 hover:bg-white/80">
                            <i class="bi bi-bullseye"></i>
                            <span x-text="editing ? 'Cancel' : 'Set Goal'"></span>
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <p class="text-2xl font-bold text-slate-900 sm:text-3xl">
                                    LKR {{ number_format($revenueGoal['current'], 0) }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    of LKR {{ number_format($revenueGoal['goal'], 0) }} goal
                                </p>
                            </div>
                            <p class="text-sm font-bold text-emerald-700">
                                {{ $revenueGoal['progress'] }}%
                            </p>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-white/70 ring-1 ring-emerald-100/80">
                            <div class="progress-fill h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"
                                style="--progress: {{ $revenueGoal['progress'] }}%; --progress-delay: 120ms"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            @if($revenueGoal['achieved'])
                                You've hit this {{ ($revenueGoal['mode'] ?? 'monthly') === 'event' ? 'event' : "month's" }} target. Great work!
                            @else
                                LKR {{ number_format($revenueGoal['remaining'], 0) }} remaining to reach your goal.
                            @endif
                        </p>
                    </div>

                    <div class="lg:col-span-4" x-show="editing" x-cloak x-transition>
                        <form method="POST" action="{{ route('organizer.revenue-goal.update') }}" class="rounded-2xl border border-white/70 bg-white/70 p-4 shadow-sm backdrop-blur-md">
                            @csrf
                            @method('PUT')
                            @if (request()->filled('kpi_event'))
                                <input type="hidden" name="kpi_event" value="{{ request('kpi_event') }}">
                            @endif
                            @if (request()->filled('chart_event'))
                                <input type="hidden" name="chart_event" value="{{ request('chart_event') }}">
                            @endif
                            @if (request()->filled('engagement_event'))
                                <input type="hidden" name="engagement_event" value="{{ request('engagement_event') }}">
                            @endif
                            @if (! empty($revenueGoal['selectedEventId']))
                                <input type="hidden" name="goal_event" value="{{ $revenueGoal['selectedEventId'] }}">
                            @endif
                            <label for="revenue_goal" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ ($revenueGoal['mode'] ?? 'monthly') === 'event' ? 'Event goal (LKR)' : 'Monthly goal (LKR)' }}
                            </label>
                            <input id="revenue_goal"
                                type="number"
                                name="revenue_goal"
                                min="1000"
                                step="1000"
                                value="{{ old('revenue_goal', old('monthly_revenue_goal', (int) $revenueGoal['goal'])) }}"
                                class="mt-2 w-full rounded-xl border-slate-300/80 bg-white/80 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                            @error('revenue_goal')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('monthly_revenue_goal')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                class="btn-smooth mt-3 inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 hover:shadow-md">
                                Save Goal
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- 4. Analytics --}}
            <section class="glass-panel p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Analytics</h2>
                        <p class="text-sm text-slate-500">
                            @if ($chartFilter['selectedEventId'])
                                Event analytics for
                                <span class="font-semibold text-slate-700">{{ $chartFilter['selectedEventName'] }}</span>
                            @else
                                Click a chart to open fullscreen
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                        <form method="GET" action="{{ route('organizer.dashboard') }}" class="sm:w-60">
                            @if (request()->filled('kpi_event'))
                                <input type="hidden" name="kpi_event" value="{{ request('kpi_event') }}">
                            @endif
                            @if (request()->filled('goal_event'))
                                <input type="hidden" name="goal_event" value="{{ request('goal_event') }}">
                            @endif
                            @if (request()->filled('engagement_event'))
                                <input type="hidden" name="engagement_event" value="{{ request('engagement_event') }}">
                            @endif
                            <label for="chart_event" class="sr-only">Filter analytics by event</label>
                            <select
                                id="chart_event"
                                name="chart_event"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Events</option>
                                @foreach ($chartFilter['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($chartFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <div class="inline-flex rounded-xl border border-white/70 bg-white/55 p-1 shadow-sm backdrop-blur-md">
                            @foreach ([
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'year' => 'This Year',
                            ] as $key => $label)
                                <button type="button"
                                    @click="setChartPeriod('{{ $key }}')"
                                    :class="chartPeriod === '{{ $key }}'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 hover:bg-white/80'"
                                    class="btn-smooth rounded-lg px-3 py-1.5 text-xs font-semibold sm:px-3.5 sm:text-sm">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <a href="{{ route('organizer.reports') }}"
                            class="btn-smooth text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Full reports →
                        </a>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ([
                        [
                            'key' => 'revenue',
                            'title' => 'Revenue',
                            'modalTitle' => 'Revenue Chart',
                            'modalDesc' => 'Earnings for the selected period',
                            'canvas' => 'organizerRevenueChart',
                            'expandBg' => 'bg-emerald-50/80 text-emerald-600 group-hover:bg-emerald-100',
                        ],
                        [
                            'key' => 'tickets',
                            'title' => 'Ticket Sales',
                            'modalTitle' => 'Ticket Sales Chart',
                            'modalDesc' => 'Confirmed tickets for the selected period',
                            'canvas' => 'organizerTicketSalesChart',
                            'expandBg' => 'bg-blue-50/80 text-blue-600 group-hover:bg-blue-100',
                        ],
                    ] as $chart)
                        <div class="glass-card group p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900">{{ $chart['title'] }}</h3>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900"
                                        data-chart-total="{{ $chart['key'] }}">
                                        —
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-bold"
                                            data-chart-change="{{ $chart['key'] }}">
                                            —
                                        </span>
                                        <span class="text-xs font-medium text-slate-500" data-chart-period-label>
                                            This Month
                                        </span>
                                    </div>
                                </div>
                                <button type="button"
                                    @click="openChart('{{ $chart['key'] }}', '{{ $chart['modalTitle'] }}', '{{ $chart['modalDesc'] }}')"
                                    class="btn-smooth group flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/60 {{ $chart['expandBg'] }}"
                                    title="View fullscreen"
                                    aria-label="View {{ $chart['title'] }} fullscreen">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>

                            <div class="mt-4 h-56 w-full rounded-xl sm:h-64">
                                <canvas id="{{ $chart['canvas'] }}"></canvas>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 5. Engagement --}}
            <section class="glass-panel overflow-hidden p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Event Engagement</h2>
                        <p class="text-xs text-slate-500">
                            @if ($engagementFilter['selectedEventId'])
                                Engagement for
                                <span class="font-semibold text-slate-700">{{ $engagementFilter['selectedEventName'] }}</span>
                            @else
                                Interaction signals across all events
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route('organizer.dashboard') }}" class="sm:w-60">
                            @if (request()->filled('kpi_event'))
                                <input type="hidden" name="kpi_event" value="{{ request('kpi_event') }}">
                            @endif
                            @if (request()->filled('goal_event'))
                                <input type="hidden" name="goal_event" value="{{ request('goal_event') }}">
                            @endif
                            @if (request()->filled('chart_event'))
                                <input type="hidden" name="chart_event" value="{{ request('chart_event') }}">
                            @endif
                            <label for="engagement_event" class="sr-only">Filter engagement by event</label>
                            <select
                                id="engagement_event"
                                name="engagement_event"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Events</option>
                                @foreach ($engagementFilter['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($engagementFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <a href="{{ $engagement['url'] }}"
                            class="btn-smooth inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            View report
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="glass-card !rounded-xl border-amber-100/80 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Satisfaction</p>
                        @if($engagement['reviews_count'] > 0)
                            @php
                                $avg = (float) $engagement['average_rating'];
                                $fullStars = (int) floor($avg);
                                $hasHalf = ($avg - $fullStars) >= 0.25 && ($avg - $fullStars) < 0.75;
                            @endphp
                            <div class="mt-1.5 flex items-center gap-2">
                                <p class="text-xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($avg, 1) }}<span class="text-sm font-semibold text-slate-400">/5</span>
                                </p>
                                <div class="flex items-center gap-0.5 text-amber-400" aria-hidden="true">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $fullStars)
                                            <i class="bi bi-star-fill text-sm"></i>
                                        @elseif($hasHalf && $i === $fullStars + 1)
                                            <i class="bi bi-star-half text-sm"></i>
                                        @else
                                            <i class="bi bi-star text-sm text-amber-200"></i>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ number_format($engagement['reviews_count']) }}
                                {{ $engagement['reviews_count'] === 1 ? 'review' : 'reviews' }}
                            </p>
                        @else
                            <div class="mt-1.5 flex items-center gap-2">
                                <p class="text-xl font-bold tracking-tight text-slate-900">—</p>
                                <div class="flex items-center gap-0.5 text-amber-200" aria-hidden="true">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star text-sm"></i>
                                    @endfor
                                </div>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">No reviews yet</p>
                        @endif
                    </div>

                    @foreach([
                        ['label' => 'Likes', 'value' => $engagement['likes'], 'icon' => 'bi-heart-fill', 'iconBg' => 'bg-rose-100/80 text-rose-600'],
                        ['label' => 'Saved', 'value' => $engagement['saves'], 'icon' => 'bi-bookmark-fill', 'iconBg' => 'bg-indigo-100/80 text-indigo-600'],
                        ['label' => 'Comments', 'value' => $engagement['comments'], 'icon' => 'bi-chat-dots-fill', 'iconBg' => 'bg-blue-100/80 text-blue-600'],
                    ] as $metric)
                        <div class="glass-card !rounded-xl p-4">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm {{ $metric['iconBg'] }}">
                                    <i class="bi {{ $metric['icon'] }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $metric['label'] }}</p>
                                    <p class="text-xl font-bold text-slate-900">{{ number_format($metric['value']) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 6. Performance + status --}}
            <section class="grid gap-5 xl:grid-cols-12">
                <div class="glass-panel overflow-hidden xl:col-span-8">
                    <div class="flex flex-col gap-3 border-b border-white/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Event Performance</h2>
                            <p class="text-sm text-slate-500">Sales, fill rate, and revenue by event</p>
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
                                    <th class="px-5 py-3 sm:px-6">Event</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Sold</th>
                                    <th class="px-3 py-3">Remaining Tickets</th>
                                    <th class="px-3 py-3">Fill</th>
                                    <th class="bg-rose-50/70 px-5 py-3 text-right font-semibold text-rose-700 sm:px-6">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($performance as $row)
                                    @php
                                        $statusClass = match ($row['status']) {
                                            'upcoming' => 'bg-emerald-100 text-emerald-800',
                                            'ongoing' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-slate-200 text-slate-700',
                                            'cancelled' => 'bg-rose-100 text-rose-800',
                                            'unpublished' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                        $fillBarClass = match ($row['status']) {
                                            'upcoming' => 'bg-emerald-500',
                                            'ongoing' => 'bg-blue-500',
                                            'completed' => 'bg-slate-400',
                                            'cancelled' => 'bg-rose-500',
                                            'unpublished' => 'bg-amber-400',
                                            default => 'bg-indigo-500',
                                        };
                                        $remainingClass = match (true) {
                                            $row['remaining'] === 0 => 'text-rose-600',
                                            $row['remaining'] <= 10 => 'text-amber-700',
                                            default => 'text-slate-900',
                                        };
                                    @endphp
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-5 py-3.5 sm:px-6">
                                            <a href="{{ $row['url'] }}" class="group block min-w-[11rem]">
                                                <p class="font-semibold text-slate-900 group-hover:text-indigo-700">
                                                    {{ $row['name'] }}
                                                    @if($row['is_low_inventory'])
                                                        <i class="bi bi-exclamation-triangle-fill text-amber-500" title="Low inventory"></i>
                                                    @endif
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    {{ $row['date'] }}
                                                    @if($row['host']) · {{ $row['host'] }} @endif
                                                </p>
                                            </a>
                                        </td>
                                        <td class="px-3 py-3.5">
                                            <span class="inline-flex rounded-full px-3 py-1.5 text-sm font-semibold capitalize {{ $statusClass }}">
                                                {{ $row['status'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-slate-700">
                                            <span class="font-medium">{{ number_format($row['sold']) }}</span>
                                            <span class="text-slate-400">/ {{ number_format($row['capacity']) }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5">
                                            <span class="font-semibold {{ $remainingClass }}">
                                                {{ number_format($row['remaining']) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3.5">
                                            <div class="flex min-w-[4.5rem] items-center gap-1.5">
                                                <div class="h-1 w-14 overflow-hidden rounded-full bg-slate-100">
                                                    <div class="progress-fill h-full rounded-full {{ $fillBarClass }}"
                                                        style="--progress: {{ min(100, $row['fill_rate']) }}%; --progress-delay: {{ 80 + ($loop->index * 40) }}ms"></div>
                                                </div>
                                                <span class="text-[11px] font-semibold text-slate-600">{{ $row['fill_rate'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap bg-rose-50/50 px-5 py-3.5 text-right sm:px-6">
                                            <span class="font-bold text-rose-700">
                                                LKR {{ number_format($row['revenue'], 0) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-14 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                                <i class="bi bi-calendar-plus text-xl"></i>
                                            </div>
                                            <p class="mt-3 text-sm font-semibold text-slate-800">No events yet</p>
                                            <a href="{{ route('organizer.events.create') }}"
                                                class="mt-2 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                Create your first event
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="space-y-5 xl:col-span-4">
                    <div class="glass-panel p-5 sm:p-6">
                        <h2 class="text-lg font-bold text-slate-900">Event Status</h2>
                        <p class="mt-0.5 text-sm text-slate-500">How your catalog is distributed</p>

                        <div class="mt-5 space-y-2.5">
                            @foreach($statusSummary as $status)
                                @php
                                    $pct = round(($status['count'] / $totalEvents) * 100);
                                    $bar = match ($status['color']) {
                                        'emerald' => 'bg-emerald-500',
                                        'blue' => 'bg-blue-500',
                                        'amber' => 'bg-amber-400',
                                        'rose' => 'bg-rose-500',
                                        'slate' => 'bg-slate-400',
                                        default => 'bg-slate-400',
                                    };
                                    $text = match ($status['color']) {
                                        'emerald' => 'text-emerald-700',
                                        'blue' => 'text-blue-700',
                                        'amber' => 'text-amber-700',
                                        'rose' => 'text-rose-700',
                                        'slate' => 'text-slate-600',
                                        default => 'text-slate-600',
                                    };
                                    $track = match ($status['color']) {
                                        'emerald' => 'bg-emerald-100',
                                        'blue' => 'bg-blue-100',
                                        'amber' => 'bg-amber-100',
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

                    <div class="glass-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Next Up</h2>
                                @if($nextUpcomingEvent)
                                    <p class="mt-0.5 text-sm font-semibold text-indigo-600">{{ $nextUpcomingEvent['day_label'] }}</p>
                                @else
                                    <p class="mt-0.5 text-sm text-slate-500">Nothing scheduled yet</p>
                                @endif
                            </div>
                            <a href="{{ route('organizer.calendar.index') }}"
                                class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                Calendar
                            </a>
                        </div>

                        @if($nextUpcomingEvent)
                            <div class="mt-4">
                                <h3 class="text-base font-bold text-slate-900">{{ $nextUpcomingEvent['name'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    <i class="bi bi-clock"></i> {{ $nextUpcomingEvent['time'] }}
                                    @if($nextUpcomingEvent['place'])
                                        · {{ $nextUpcomingEvent['place'] }}
                                    @endif
                                </p>

                                <div class="mt-4 space-y-2.5">
                                    @foreach($nextUpcomingEvent['categories'] as $category)
                                        @php
                                            $categoryColor = $category['color'] ?? '#6366f1';
                                        @endphp
                                        <div class="btn-smooth flex items-center justify-between gap-3 rounded-xl border border-white/60 bg-white/50 px-3.5 py-2.5 backdrop-blur-sm hover:bg-white/75"
                                            style="border-left: 3px solid {{ $categoryColor }};">
                                            <div class="flex min-w-0 items-center gap-2.5">
                                                <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                                    style="background-color: {{ $categoryColor }}"></span>
                                                <p class="truncate text-sm font-semibold text-slate-800">
                                                    {{ $category['name'] }}
                                                </p>
                                            </div>
                                            <p @class([
                                                'shrink-0 text-sm font-bold',
                                                'text-rose-600' => $category['remaining'] === 0,
                                                'text-amber-700' => $category['remaining'] > 0 && $category['remaining'] <= 10,
                                                'text-slate-800' => $category['remaining'] > 10,
                                            ])>
                                                {{ number_format($category['remaining']) }}
                                                <span class="font-medium text-slate-500">Remaining</span>
                                            </p>
                                        </div>
                                    @endforeach
                                </div>

                                <a href="{{ $nextUpcomingEvent['manage_url'] }}"
                                    class="btn-smooth mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600/95 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 hover:shadow-md">
                                    Manage
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        @else
                            <div class="mt-6 rounded-xl border border-dashed border-white/70 bg-white/40 px-4 py-8 text-center backdrop-blur-sm">
                                <p class="text-sm text-slate-500">No upcoming events to manage.</p>
                                <a href="{{ route('organizer.events.create') }}"
                                    class="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                    Create an event
                                </a>
                            </div>
                        @endif
                    </div>
                </aside>
            </section>

            {{-- 7. Operations --}}
            <section class="grid gap-5 lg:grid-cols-3">
                <div class="glass-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/50 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Upcoming</h2>
                            <p class="text-xs text-slate-500">Next on your schedule</p>
                        </div>
                        <a href="{{ route('organizer.calendar.index') }}"
                            class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-700">Calendar</a>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-white/40 overflow-y-auto">
                        @forelse($upcomingEvents as $event)
                            <a href="{{ $event['url'] }}" class="btn-smooth flex gap-3 px-5 py-4 hover:bg-white/45">
                                <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl border border-white/60 bg-indigo-50/80 text-indigo-700 backdrop-blur-sm">
                                    <span class="text-[10px] font-semibold uppercase leading-none">{{ $event['month'] }}</span>
                                    <span class="mt-0.5 text-base font-bold leading-none">{{ $event['day'] }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $event['name'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        @if($event['time']) {{ $event['time'] }} · @endif
                                        {{ $event['place'] ?? 'Venue TBD' }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ number_format($event['sold']) }}/{{ number_format($event['capacity']) }} sold
                                    </p>
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-10 text-center text-sm text-slate-500">No upcoming events.</p>
                        @endforelse
                    </div>
                </div>

                <div class="glass-panel overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/50 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Sales</h2>
                            <p class="text-xs text-slate-500">Latest ticket purchases</p>
                        </div>
                        <a href="{{ route('organizer.reports', ['tab' => 'attendees']) }}"
                            class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-700">Reports</a>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-white/40 overflow-y-auto">
                        @forelse($recentPurchases as $purchase)
                            <a href="{{ $purchase['url'] }}" class="btn-smooth flex items-start gap-3 px-5 py-4 hover:bg-white/45">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/60 bg-indigo-50/80 text-sm font-bold text-indigo-700 backdrop-blur-sm">
                                    {{ strtoupper(substr($purchase['buyer'], 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $purchase['buyer'] }}</p>
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
                            <div class="p-4">
                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="glass-panel overflow-hidden">
                    <div class="border-b border-white/50 px-5 py-4">
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

        {{-- Fullscreen chart modal --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-white/60 bg-white/90 shadow-2xl backdrop-blur-xl"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                role="dialog"
                aria-modal="true"
                :aria-label="title">
                <div class="flex items-start justify-between gap-4 border-b border-white/60 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-slate-500 hover:bg-white hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full">
                        <canvas id="organizerChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-white/60 px-5 py-3 text-xs text-slate-400 sm:px-6">
                    Press <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-semibold text-slate-600">Esc</kbd> to close
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.organizerDashboardData = @json($dashboard);
            (function () {
                var key = 'organizer-dashboard-scroll';

                function currentScrollY() {
                    return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                }

                function restoreScroll(y) {
                    window.scrollTo(0, y);
                }

                try {
                    var saved = sessionStorage.getItem(key);
                    if (saved !== null) {
                        sessionStorage.removeItem(key);
                        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
                        var y = Number(saved);
                        if (Number.isFinite(y)) {
                            restoreScroll(y);
                            requestAnimationFrame(function () { restoreScroll(y); });
                            window.addEventListener('load', function () { restoreScroll(y); }, { once: true });
                            setTimeout(function () { restoreScroll(y); }, 50);
                            setTimeout(function () { restoreScroll(y); }, 250);
                            setTimeout(function () { restoreScroll(y); }, 600);
                        }
                    }
                } catch (e) {}

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll(
                        '.organizer-dashboard select[name="kpi_event"],' +
                        '.organizer-dashboard select[name="goal_event"],' +
                        '.organizer-dashboard select[name="chart_event"],' +
                        '.organizer-dashboard select[name="engagement_event"]'
                    ).forEach(function (select) {
                        select.addEventListener('change', function () {
                            try {
                                sessionStorage.setItem(key, String(currentScrollY()));
                            } catch (e) {}
                            if (this.form) this.form.submit();
                        });
                    });
                });
            })();
        </script>
        @vite('resources/js/organizer-dashboard.js')
    @endpush
</x-app-layout>

<x-app-layout>
    @php
        $stats = $dashboard['stats'];
        $todaySummary = $dashboard['todaySummary'];
        $kpis = $dashboard['kpis'];
        $revenueGoal = $dashboard['revenueGoal'];
        $statusSummary = $dashboard['statusSummary'];
        $performance = $dashboard['performance'];
        $upcomingEvents = $dashboard['upcomingEvents'];
        $nextUpcomingEvent = $dashboard['nextUpcomingEvent'] ?? null;
        $recentPurchases = $dashboard['recentPurchases'];
        $recentActivity = $dashboard['recentActivity'];
        $engagement = $dashboard['engagement'];
        $totalEvents = max(1, $stats['totalEvents']);
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'there';
        $initials = strtoupper(substr($user?->first_name ?? 'O', 0, 1) . substr($user?->last_name ?? '', 0, 1));
    @endphp

    <div class="py-5 sm:py-6"
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

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm"
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

            {{-- Intro --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="relative bg-gradient-to-br from-slate-50 via-white to-indigo-50/70 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-indigo-100/40"></div>

                    <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                @if ($user?->profile_photo)
                                    <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                        alt="{{ $displayName }}"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow-sm sm:h-10 sm:w-10">
                                @else
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-sm ring-2 ring-white sm:h-10 sm:w-10 sm:text-sm">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $greeting }}, {{ $displayName }} <span aria-hidden="true">👋</span>
                                    </p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Organizer Dashboard
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Manage events, ticket sales, and revenue in one place.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <a href="{{ route('organizer.events.create') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 sm:text-sm">
                                <i class="bi bi-plus-lg"></i>
                                New Event
                            </a>
                            <a href="{{ route('organizer.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 sm:text-sm">
                                <i class="bi bi-graph-up-arrow"></i>
                                Reports
                            </a>
                        </div>
                    </div>

                    {{-- Today's Summary --}}
                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-indigo-100/80 bg-white/80 px-3 py-2.5 sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/80 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>

                        <div class="grid min-w-0 flex-1 grid-cols-3 gap-2">
                            <div class="flex items-center gap-2 rounded-lg bg-indigo-50/70 px-2 py-1.5 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-indigo-100 text-sm text-indigo-600 sm:flex">
                                    <i class="bi bi-calendar-event"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ number_format($todaySummary['eventsToday']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Events</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg bg-blue-50/70 px-2 py-1.5 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-blue-100 text-sm text-blue-600 sm:flex">
                                    <i class="bi bi-ticket-perforated"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ number_format($todaySummary['ticketsSold']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Tickets</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg bg-emerald-50/70 px-2 py-1.5 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-emerald-100 text-sm text-emerald-600 sm:flex">
                                    <i class="bi bi-cash-stack"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">LKR {{ number_format($todaySummary['revenue'], 0) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Compact shortcuts --}}
                    <div class="relative mt-3 flex flex-wrap gap-1.5">
                        @foreach ([
                            ['label' => 'Manage Events', 'route' => route('organizer.events.index'), 'icon' => 'bi-calendar-event'],
                            ['label' => 'Calendar', 'route' => route('organizer.calendar.index'), 'icon' => 'bi-calendar3'],
                            ['label' => 'Hosts', 'route' => route('organizer.hosts'), 'icon' => 'bi-building'],
                            ['label' => 'Attendees', 'route' => route('organizer.reports', ['tab' => 'attendees']), 'icon' => 'bi-people'],
                        ] as $shortcut)
                            <a href="{{ $shortcut['route'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/80 px-2.5 py-1 text-[11px] font-semibold text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                <i class="bi {{ $shortcut['icon'] }}"></i>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- KPI strip --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    @php
                        $accent = match ($kpi['accent']) {
                            'emerald' => [
                                'top' => 'border-t-emerald-500',
                                'left' => 'border-l-emerald-500',
                                'iconBg' => 'bg-emerald-100/70',
                                'iconText' => 'text-emerald-600',
                                'cardBg' => 'bg-emerald-50/40',
                            ],
                            'indigo' => [
                                'top' => 'border-t-indigo-500',
                                'left' => 'border-l-indigo-500',
                                'iconBg' => 'bg-indigo-100/70',
                                'iconText' => 'text-indigo-600',
                                'cardBg' => 'bg-indigo-50/40',
                            ],
                            'blue' => [
                                'top' => 'border-t-blue-500',
                                'left' => 'border-l-blue-500',
                                'iconBg' => 'bg-blue-100/70',
                                'iconText' => 'text-blue-600',
                                'cardBg' => 'bg-blue-50/40',
                            ],
                            'rose' => [
                                'top' => 'border-t-rose-500',
                                'left' => 'border-l-rose-500',
                                'iconBg' => 'bg-rose-100/70',
                                'iconText' => 'text-rose-600',
                                'cardBg' => 'bg-rose-50/40',
                            ],
                            default => [
                                'top' => 'border-t-slate-400',
                                'left' => 'border-l-slate-400',
                                'iconBg' => 'bg-slate-100/70',
                                'iconText' => 'text-slate-600',
                                'cardBg' => 'bg-slate-50/40',
                            ],
                        };
                        $trendPositive = $kpi['trendUp'];
                        $trendClass = $trendPositive ? 'text-emerald-600' : 'text-rose-600';
                        $trendArrow = $trendPositive ? '▲' : '▼';
                    @endphp

                    <div class="group kpi-lift rounded-xl border border-slate-200/80 border-t-[3px] {{ $accent['top'] }} border-l-[3px] {{ $accent['left'] }} {{ $accent['cardBg'] }} px-4 py-3.5 shadow-sm">
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
                            <span class="ml-1 font-bold {{ $trendClass }}">
                                <span aria-hidden="true">{{ $trendArrow }}</span>{{ $kpi['trendLabel'] }}
                            </span>
                        </p>
                    </div>
                @endforeach
            </section>

            {{-- Revenue Goal --}}
            <section class="overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-cyan-50 p-5 shadow-sm sm:p-6"
                x-data="{ editing: {{ $errors->has('monthly_revenue_goal') ? 'true' : 'false' }} }">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">Revenue Goal</h2>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                {{ $revenueGoal['month_label'] }}
                            </span>
                            @if($revenueGoal['achieved'])
                                <span class="rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                                    Goal reached
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Track progress toward your monthly sales target.
                        </p>
                    </div>

                    <button type="button"
                        @click="editing = !editing"
                        class="btn-smooth inline-flex items-center gap-1.5 self-start rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <i class="bi bi-bullseye"></i>
                        <span x-text="editing ? 'Cancel' : 'Set Goal'"></span>
                    </button>
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
                        <div class="h-3 overflow-hidden rounded-full bg-white/80 ring-1 ring-emerald-100">
                            <div class="progress-fill h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500"
                                style="--progress: {{ $revenueGoal['progress'] }}%; --progress-delay: 120ms"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            @if($revenueGoal['achieved'])
                                You've hit this month's target. Great work!
                            @else
                                LKR {{ number_format($revenueGoal['remaining'], 0) }} remaining to reach your goal.
                            @endif
                        </p>
                    </div>

                    <div class="lg:col-span-4" x-show="editing" x-cloak x-transition>
                        <form method="POST" action="{{ route('organizer.revenue-goal.update') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            @csrf
                            @method('PUT')
                            <label for="monthly_revenue_goal" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Monthly goal (LKR)
                            </label>
                            <input id="monthly_revenue_goal"
                                type="number"
                                name="monthly_revenue_goal"
                                min="1000"
                                step="1000"
                                value="{{ old('monthly_revenue_goal', (int) $revenueGoal['goal']) }}"
                                class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required>
                            @error('monthly_revenue_goal')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                class="btn-smooth mt-3 inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                Save Goal
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Analytics charts --}}
            <section class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 via-white to-cyan-50/60 p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Analytics</h2>
                        <p class="text-sm text-slate-500">Click a chart to open fullscreen</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            @foreach ([
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'year' => 'This Year',
                            ] as $key => $label)
                                <button type="button"
                                    @click="setChartPeriod('{{ $key }}')"
                                    :class="chartPeriod === '{{ $key }}'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 hover:bg-slate-50'"
                                    class="btn-smooth rounded-lg px-3 py-1.5 text-xs font-semibold sm:px-3.5 sm:text-sm">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <a href="{{ route('organizer.reports') }}"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Open full reports →
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
                            'expandBg' => 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100',
                            'metricPrefix' => true,
                        ],
                        [
                            'key' => 'tickets',
                            'title' => 'Ticket Sales',
                            'modalTitle' => 'Ticket Sales Chart',
                            'modalDesc' => 'Confirmed tickets for the selected period',
                            'canvas' => 'organizerTicketSalesChart',
                            'expandBg' => 'bg-blue-50 text-blue-600 group-hover:bg-blue-100',
                            'metricPrefix' => false,
                        ],
                    ] as $chart)
                        <div class="rounded-2xl border border-white bg-white/90 p-5 shadow-sm sm:p-6">
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
                                    class="group flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $chart['expandBg'] }} btn-smooth"
                                    title="View fullscreen"
                                    aria-label="View {{ $chart['title'] }} fullscreen">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>

                            <button type="button"
                                @click="openChart('{{ $chart['key'] }}', '{{ $chart['modalTitle'] }}', '{{ $chart['modalDesc'] }}')"
                                class="mt-4 block h-56 w-full cursor-pointer rounded-xl text-left transition hover:bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-64">
                                <canvas id="{{ $chart['canvas'] }}" class="pointer-events-none"></canvas>
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Performance + status --}}
            <section class="grid gap-6 xl:grid-cols-12">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-8">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Event Performance</h2>
                            <p class="text-sm text-slate-500">Sales, fill rate, and revenue by event</p>
                        </div>
                        <a href="{{ route('organizer.events.index') }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Manage events
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 sm:px-6">Event</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Sold</th>
                                    <th class="px-3 py-3">Remaining Tickets</th>
                                    <th class="px-3 py-3">Fill</th>
                                    <th class="px-5 py-3 text-right sm:px-6">Revenue</th>
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
                                    <tr class="transition hover:bg-slate-50/80">
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
                                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-900 sm:px-6">
                                            LKR {{ number_format($row['revenue'], 0) }}
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

                <aside class="space-y-6 xl:col-span-4">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
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

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Upcoming Events</h2>
                                @if($nextUpcomingEvent)
                                    <p class="mt-0.5 text-sm font-semibold text-indigo-600">{{ $nextUpcomingEvent['day_label'] }}</p>
                                @else
                                    <p class="mt-0.5 text-sm text-slate-500">Nothing scheduled yet</p>
                                @endif
                            </div>
                            <a href="{{ route('organizer.calendar.index') }}"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
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
                                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3.5 py-2.5"
                                            style="background-color: {{ $categoryColor }}12; border-left: 3px solid {{ $categoryColor }};">
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
                                    class="btn-smooth mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Manage
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        @else
                            <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
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

            {{-- Engagement insights --}}
            <section class="overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-r from-violet-50/80 via-white to-amber-50/60 p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Event Engagement</h2>
                        <p class="text-xs text-slate-500">Interaction signals beyond ticket sales</p>
                    </div>
                    <a href="{{ $engagement['url'] }}"
                        class="inline-flex items-center gap-1 text-sm font-semibold text-violet-700 hover:text-violet-800">
                        View report
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-amber-100 bg-white/80 px-4 py-3">
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
                        ['label' => 'Likes', 'value' => $engagement['likes'], 'icon' => 'bi-heart-fill', 'wrap' => 'border-rose-100 bg-rose-50/50', 'iconBg' => 'bg-rose-100 text-rose-600'],
                        ['label' => 'Saved', 'value' => $engagement['saves'], 'icon' => 'bi-bookmark-fill', 'wrap' => 'border-indigo-100 bg-indigo-50/50', 'iconBg' => 'bg-indigo-100 text-indigo-600'],
                        ['label' => 'Comments', 'value' => $engagement['comments'], 'icon' => 'bi-chat-dots-fill', 'wrap' => 'border-blue-100 bg-blue-50/50', 'iconBg' => 'bg-blue-100 text-blue-600'],
                    ] as $metric)
                        <div class="rounded-xl border {{ $metric['wrap'] }} px-4 py-3">
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

            {{-- Operations: upcoming, purchases, activity --}}
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Upcoming</h2>
                            <p class="text-xs text-slate-500">Next on your schedule</p>
                        </div>
                        <a href="{{ route('organizer.calendar.index') }}"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Calendar</a>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                        @forelse($upcomingEvents as $event)
                            <a href="{{ $event['url'] }}" class="flex gap-3 px-5 py-4 transition hover:bg-slate-50">
                                <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
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

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Sales</h2>
                            <p class="text-xs text-slate-500">Latest ticket purchases</p>
                        </div>
                        <a href="{{ route('organizer.reports', ['tab' => 'attendees']) }}"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Reports</a>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                        @forelse($recentPurchases as $purchase)
                            <a href="{{ $purchase['url'] }}" class="flex items-start gap-3 px-5 py-4 transition hover:bg-slate-50">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700">
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
                            <p class="px-5 py-10 text-center text-sm text-slate-500">No ticket purchases yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-900">Recent Activity</h2>
                        <p class="text-xs text-slate-500">Updates and bookings</p>
                    </div>
                    <div class="max-h-[28rem] space-y-0 overflow-y-auto px-5 py-2">
                        @forelse($recentActivity as $item)
                            @php
                                $iconStyles = match ($item['color']) {
                                    'emerald' => 'bg-emerald-100 text-emerald-600 ring-emerald-200',
                                    'rose' => 'bg-rose-100 text-rose-600 ring-rose-200',
                                    'blue' => 'bg-blue-100 text-blue-600 ring-blue-200',
                                    'indigo' => 'bg-indigo-100 text-indigo-600 ring-indigo-200',
                                    'amber' => 'bg-amber-100 text-amber-600 ring-amber-200',
                                    'violet' => 'bg-violet-100 text-violet-600 ring-violet-200',
                                    'cyan' => 'bg-cyan-100 text-cyan-600 ring-cyan-200',
                                    default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                };
                            @endphp
                            <a href="{{ $item['url'] }}" class="group relative flex gap-3 py-3.5">
                                @if(! $loop->last)
                                    <span class="absolute left-4 top-11 bottom-0 w-px bg-slate-100"></span>
                                @endif
                                <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1 {{ $iconStyles }}">
                                    <i class="bi {{ $item['icon'] }} text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-700">{{ $item['title'] }}</p>
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
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeChart()"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
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
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full">
                        <canvas id="organizerChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400 sm:px-6">
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
        </script>
        @vite('resources/js/organizer-dashboard.js')
    @endpush
</x-app-layout>

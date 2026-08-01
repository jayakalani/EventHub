<x-app-layout>
    @php
        $admin = $reports['admin'];
        $users = $reports['users'];
        $payments = $reports['payments'];
        $system = $reports['system'];
        $overview = $reports['overview'] ?? [
            'highlights' => [
                'newUsers' => 0,
                'newEvents' => 0,
                'ticketsSold' => 0,
                'pendingOrganizerApprovals' => 0,
            ],
            'kpis' => [
                'totalUsers' => $admin['totalUsers'] ?? 0,
                'usersToday' => 0,
                'roleBreakdown' => $users['usersByRole'] ?? [],
                'totalEvents' => $admin['totalEvents'] ?? 0,
                'eventsThisWeek' => 0,
                'platformRevenue' => $payments['netRevenue'] ?? 0,
                'revenueMoMPercent' => 0,
                'ticketsSold' => $admin['totalTicketsSold'] ?? 0,
                'ticketsToday' => 0,
            ],
            'userGrowth' => $users['registrationTrend'] ?? [],
            'userDistribution' => $users['usersByRole'] ?? [],
            'revenueTrend' => ['labels' => [], 'values' => [], 'formatted' => []],
            'ticketSalesWeekly' => [],
            'recentUsers' => $users['recentUsers'] ?? [],
            'organizerPerformance' => [],
            'recentPayments' => [],
            'recentAuditLogs' => $system['recentAuditLogs'] ?? [],
            'platformStatus' => [],
            'eventsByCategory' => [],
        ];
        $highlights = $overview['highlights'];
        $kpis = $overview['kpis'];
        $roleBreakdown = $kpis['roleBreakdown'] ?? [];
        $revenueMoM = (float) ($kpis['revenueMoMPercent'] ?? 0);
        $revenueUp = $revenueMoM >= 0;
        $revenueTrendFormatted = $overview['revenueTrend']['formatted'] ?? [];
        $ticketSalesWeekly = $overview['ticketSalesWeekly'] ?? [];
        $recentUsers = $overview['recentUsers'] ?? [];
        $organizerPerformance = $overview['organizerPerformance'] ?? [];
        $recentPayments = $overview['recentPayments'] ?? [];
        $recentAuditLogs = $overview['recentAuditLogs'] ?? [];
        $platformStatus = $overview['platformStatus'] ?? [];
        $eventsByCategory = $overview['eventsByCategory'] ?? [];
        $eventsByCategoryMax = max(1, (int) collect($eventsByCategory)->max('count'));

        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'Administrator';
        $initials = strtoupper(substr($user?->first_name ?? 'A', 0, 1) . substr($user?->last_name ?? '', 0, 1));
    @endphp

    <div class="admin-reports relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            activeTab: 'admin',
            open: false,
            chartKey: null,
            title: '',
            description: '',
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('admin-reports-chart-expand', {
                            detail: { key },
                        }));
                    }, 220);
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('admin-reports-chart-collapse'));
            },
            setTab(tab) {
                this.activeTab = tab;
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('admin-reports-tab-changed'));
                    document.getElementById('report-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            scrollTo(id) {
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Welcome header --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-indigo-200/30 blur-2xl"></div>

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
                                        Reports & Analytics
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Welcome back! Platform-wide insights across users, events, and revenue.
                            </p>
                        </div>

                        <div class="flex flex-col items-stretch gap-2 sm:shrink-0 sm:items-end sm:justify-end">
                            <p class="inline-flex items-center justify-end gap-1.5 text-xs font-medium text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                Updated {{ now()->diffForHumans() }}
                            </p>
                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                <a href="{{ route('dashboard') }}"
                                    class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                    <i class="bi bi-speedometer2"></i>
                                    Dashboard
                                </a>
                                <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="admin" />
                            </div>
                        </div>
                    </div>

                    {{-- Today's highlights --}}
                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today's Highlights</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>

                        <div class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ([
                                [
                                    'label' => 'New Users',
                                    'value' => $highlights['newUsers'],
                                    'icon' => 'bi-person-plus',
                                    'bg' => 'bg-indigo-50/60',
                                    'iconBg' => 'bg-indigo-100/80',
                                    'iconColor' => 'text-indigo-600',
                                ],
                                [
                                    'label' => 'New Events',
                                    'value' => $highlights['newEvents'],
                                    'icon' => 'bi-calendar-plus',
                                    'bg' => 'bg-blue-50/60',
                                    'iconBg' => 'bg-blue-100/80',
                                    'iconColor' => 'text-blue-600',
                                ],
                                [
                                    'label' => 'Tickets Sold',
                                    'value' => $highlights['ticketsSold'],
                                    'icon' => 'bi-ticket-perforated',
                                    'bg' => 'bg-cyan-50/60',
                                    'iconBg' => 'bg-cyan-100/80',
                                    'iconColor' => 'text-cyan-600',
                                ],
                                [
                                    'label' => 'Pending Approvals',
                                    'value' => $highlights['pendingOrganizerApprovals'],
                                    'icon' => 'bi-person-check',
                                    'bg' => 'bg-amber-50/60',
                                    'iconBg' => 'bg-amber-100/80',
                                    'iconColor' => 'text-amber-600',
                                ],
                            ] as $item)
                                <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 {{ $item['bg'] }} px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                    <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $item['iconBg'] }} text-sm {{ $item['iconColor'] }} sm:flex">
                                        <i class="bi {{ $item['icon'] }}"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ number_format($item['value']) }}</p>
                                        <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $item['label'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- Platform KPI cards --}}
            <section id="report-kpis" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <button type="button" @click="setTab('users')" class="glass-card kpi-lift border-t-4 border-t-indigo-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Users</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['totalUsers']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-emerald-600">+{{ number_format($kpis['usersToday']) }} Today</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100/70 text-indigo-600 backdrop-blur-sm">
                            <i class="bi bi-people text-lg"></i>
                        </span>
                    </div>
                    @if (count($roleBreakdown) > 0)
                        <div class="mt-3 grid grid-cols-2 gap-1.5 border-t border-white/50 pt-3">
                            @foreach ($roleBreakdown as $role)
                                <div class="rounded-lg border border-white/60 bg-white/40 px-2 py-1.5 backdrop-blur-sm">
                                    <p class="truncate text-[10px] font-medium text-slate-500">{{ $role['label'] }}</p>
                                    <p class="text-sm font-bold text-slate-800">{{ number_format($role['count']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </button>

                <button type="button" @click="scrollTo('report-marketplace')" class="glass-card kpi-lift border-t-4 border-t-blue-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Events</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['totalEvents']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-blue-600">+{{ number_format($kpis['eventsThisWeek']) }} This Week</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100/70 text-blue-600 backdrop-blur-sm">
                            <i class="bi bi-calendar-event text-lg"></i>
                        </span>
                    </div>
                </button>

                <button type="button" @click="setTab('payments')" class="glass-card kpi-lift border-t-4 border-t-emerald-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Platform Revenue</p>
                            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">LKR {{ number_format($kpis['platformRevenue'], 0) }}</p>
                            <p @class([
                                'mt-1 text-xs font-semibold',
                                'text-emerald-600' => $revenueUp,
                                'text-rose-600' => ! $revenueUp,
                            ])>
                                {{ $revenueUp ? '+' : '' }}{{ $revenueMoM }}%
                                <span class="font-medium text-slate-500">vs last month</span>
                            </p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100/70 text-emerald-600 backdrop-blur-sm">
                            <i class="bi bi-cash-stack text-lg"></i>
                        </span>
                    </div>
                </button>

                <button type="button" @click="scrollTo('report-analytics')" class="glass-card kpi-lift border-t-4 border-t-cyan-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tickets Sold</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['ticketsSold']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-cyan-600">+{{ number_format($kpis['ticketsToday']) }} Today</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100/70 text-cyan-600 backdrop-blur-sm">
                            <i class="bi bi-ticket-perforated text-lg"></i>
                        </span>
                    </div>
                </button>
            </section>

            {{-- Sticky section nav --}}
            <nav class="sticky top-16 z-30 sm:top-20" aria-label="Report sections">
                <div class="flex gap-1.5 overflow-x-auto rounded-2xl border border-white/60 bg-white/70 p-1.5 shadow-sm backdrop-blur-xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ([
                        ['id' => 'report-analytics', 'label' => 'Analytics', 'icon' => 'bi-graph-up'],
                        ['id' => 'report-marketplace', 'label' => 'Marketplace', 'icon' => 'bi-shop'],
                        ['id' => 'report-activity', 'label' => 'Activity', 'icon' => 'bi-lightning'],
                        ['id' => 'report-health', 'label' => 'Health', 'icon' => 'bi-heart-pulse'],
                        ['id' => 'report-details', 'label' => 'Deep Dive', 'icon' => 'bi-layers'],
                    ] as $nav)
                        <button type="button"
                            @click="scrollTo('{{ $nav['id'] }}')"
                            class="btn-smooth inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">
                            <i class="bi {{ $nav['icon'] }} text-[11px] opacity-80"></i>
                            {{ $nav['label'] }}
                        </button>
                    @endforeach
                </div>
            </nav>

            {{-- Analytics --}}
            <div id="report-analytics" class="scroll-mt-28 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Analytics</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Growth & performance</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Users, revenue, and ticket sales at a glance · click charts for fullscreen</p>
                </div>

            {{-- User growth + distribution --}}
            <section class="grid gap-4 lg:grid-cols-5">
                <div class="glass-card chart-expand-hit group p-4 sm:p-5 lg:col-span-3"
                    @click="openChart('userGrowth', 'User Growth', 'New registrations over the last 6 months')">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Growth</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">User Growth</h2>
                            <p class="mt-0.5 text-sm text-slate-500">New registrations over the last 6 months</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View User Growth fullscreen"
                            @click.stop="openChart('userGrowth', 'User Growth', 'New registrations over the last 6 months')">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <div class="h-72">
                        <canvas id="adminOverviewUserGrowthChart" class="pointer-events-none"></canvas>
                    </div>
                </div>

                <div class="glass-card chart-expand-hit group p-4 sm:p-5 lg:col-span-2"
                    @click="openChart('userDistribution', 'User Distribution', 'Platform role makeup')">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-600">Composition</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">User Distribution</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Platform role makeup</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-cyan-50/70 text-cyan-600 backdrop-blur hover:bg-cyan-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View User Distribution fullscreen"
                            @click.stop="openChart('userDistribution', 'User Distribution', 'Platform role makeup')">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <div class="h-56">
                        <canvas id="adminOverviewUserDistributionChart" class="pointer-events-none"></canvas>
                    </div>
                    @if (count($roleBreakdown) > 0)
                        <div class="mt-4 space-y-2 border-t border-white/50 pt-3" @click.stop>
                            @foreach ($roleBreakdown as $role)
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">{{ $role['label'] }}</span>
                                    <span class="tabular-nums text-slate-500">
                                        {{ number_format($role['count']) }}
                                        <span class="ml-1 font-semibold text-slate-800">{{ $role['percent'] }}%</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Revenue + ticket sales trends --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="glass-card chart-expand-hit group p-4 sm:p-5"
                    @click="openChart('revenueTrend', 'Revenue Trend', 'Monthly platform revenue')">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Revenue</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">Revenue Trend</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Monthly platform revenue</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-emerald-50/70 text-emerald-600 backdrop-blur hover:bg-emerald-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            @click.stop="openChart('revenueTrend', 'Revenue Trend', 'Monthly platform revenue')">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <div class="h-64">
                        <canvas id="adminOverviewRevenueTrendChart" class="pointer-events-none"></canvas>
                    </div>
                    @if (count($revenueTrendFormatted) > 0)
                        <div class="mt-4 grid grid-cols-3 gap-2 border-t border-white/50 pt-3 sm:grid-cols-6" @click.stop>
                            @foreach ($revenueTrendFormatted as $point)
                                <div class="btn-smooth rounded-lg border border-white/60 bg-emerald-50/50 px-2 py-1.5 text-center backdrop-blur-sm hover:bg-emerald-50/80">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $point['month'] }}</p>
                                    <p class="mt-0.5 text-xs font-bold text-emerald-700">{{ $point['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="glass-card chart-expand-hit group p-4 sm:p-5"
                    @click="openChart('ticketSalesWeekly', 'Ticket Sales Trend', 'Confirmed ticket sales over the last 4 weeks')">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Tickets</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">Ticket Sales Trend</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Confirmed ticket sales over the last 4 weeks</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-blue-50/70 text-blue-600 backdrop-blur hover:bg-blue-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            @click.stop="openChart('ticketSalesWeekly', 'Ticket Sales Trend', 'Confirmed ticket sales over the last 4 weeks')">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <div class="h-64">
                        <canvas id="adminOverviewTicketSalesChart" class="pointer-events-none"></canvas>
                    </div>
                    @if (count($ticketSalesWeekly) > 0)
                        <div class="mt-4 grid grid-cols-4 gap-2 border-t border-white/50 pt-3" @click.stop>
                            @foreach ($ticketSalesWeekly as $week)
                                <div class="btn-smooth rounded-lg border border-white/60 bg-blue-50/50 px-2 py-1.5 text-center backdrop-blur-sm hover:bg-blue-50/80">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $week['label'] }}</p>
                                    <p class="mt-0.5 text-sm font-bold text-blue-700">{{ number_format($week['count']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
            </div>

            {{-- Marketplace: categories + organizers --}}
            <div id="report-marketplace" class="scroll-mt-28 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Marketplace</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Events & organizers</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Category mix and top-performing organizers</p>
                </div>

                <section class="grid gap-4 lg:grid-cols-5">
                <div class="glass-card chart-expand-hit group p-4 sm:p-5 lg:col-span-3"
                    @click="openChart('eventsByCategory', 'Event Category Analytics', 'Number of events by category')">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Categories</p>
                            <h2 class="mt-0.5 text-base font-bold text-slate-900">Event Category Analytics</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Number of events by category</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            @click.stop="openChart('eventsByCategory', 'Event Category Analytics', 'Number of events by category')">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <div class="h-64">
                        <canvas id="adminOverviewEventsByCategoryChart" class="pointer-events-none"></canvas>
                    </div>
                    @if (count($eventsByCategory) > 0)
                        <div class="mt-4 space-y-2 border-t border-white/50 pt-3" @click.stop>
                            @foreach ($eventsByCategory as $category)
                                @php
                                    $barWidth = min(100, round(((int) $category['count'] / $eventsByCategoryMax) * 100, 1));
                                @endphp
                                <div class="flex items-center gap-3">
                                    <span class="w-24 shrink-0 truncate text-xs font-medium text-slate-600 sm:w-28">{{ $category['label'] }}</span>
                                    <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-white/60">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-500"
                                            style="width: {{ $barWidth }}%"></div>
                                    </div>
                                    <span class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-slate-800">{{ number_format($category['count']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="glass-card overflow-hidden !p-0 lg:col-span-2">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Organizer Performance</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Top organizers by revenue</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Events</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Tickets</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($organizerPerformance as $index => $organizer)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-center gap-2.5">
                                                <span @class([
                                                    'flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold',
                                                    'bg-amber-100 text-amber-700' => $index === 0,
                                                    'bg-slate-200 text-slate-700' => $index === 1,
                                                    'bg-orange-100 text-orange-700' => $index === 2,
                                                    'bg-slate-100 text-slate-600' => $index > 2,
                                                ])>
                                                    {{ $index + 1 }}
                                                </span>
                                                <span class="font-semibold text-slate-900">{{ $organizer['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['events']) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['ticketsSold']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700 sm:px-5">{{ $organizer['revenueLabel'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                </section>
            </div>

            {{-- Activity --}}
            <div id="report-activity" class="scroll-mt-28 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">Activity</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Recent activity</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Latest registrations, payments, and audit events</p>
                </div>

            {{-- Recent users + payments --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent User Registrations</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Latest users on the platform</p>
                        </div>
                        <a href="{{ route('admin.users') }}"
                            class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View All Users →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">User</th>
                                    <th class="px-4 py-2.5 sm:px-5">Role</th>
                                    <th class="px-4 py-2.5 sm:px-5">Registered</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($recentUsers as $recentUser)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 font-medium text-slate-900 sm:px-5">{{ $recentUser['name'] }}</td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <span class="inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                                {{ $recentUser['role'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 sm:px-5">{{ $recentUser['joined'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Payments</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Latest platform transactions</p>
                        </div>
                        <button type="button"
                            @click="setTab('payments')"
                            class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View All Payments →
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Customer</th>
                                    <th class="px-4 py-2.5 sm:px-5">Event</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Amount</th>
                                    <th class="px-4 py-2.5 sm:px-5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($recentPayments as $payment)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 font-medium text-slate-900 sm:px-5">{{ $payment['customer'] }}</td>
                                        <td class="max-w-[10rem] truncate px-4 py-3 text-slate-600 sm:px-5">{{ $payment['event'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900 sm:px-5">LKR {{ number_format($payment['amount'], 0) }}</td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <span @class([
                                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                'bg-emerald-100 text-emerald-700' => $payment['status'] === 'completed',
                                                'bg-amber-100 text-amber-700' => $payment['status'] === 'pending',
                                                'bg-purple-100 text-purple-700' => $payment['status'] === 'refunded',
                                                'bg-rose-100 text-rose-700' => in_array($payment['status'], ['failed', 'cancelled'], true),
                                            ])>
                                                {{ $payment['statusLabel'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Audit Logs</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Latest platform activity</p>
                        </div>
                        <a href="{{ route('admin.audit-logs') }}"
                            class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View Audit Logs →
                        </a>
                    </div>
                    <div class="grid divide-y divide-white/40 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-3">
                        @forelse ($recentAuditLogs as $log)
                            <div class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-white/45 sm:px-5">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-white/50 text-slate-500 backdrop-blur-sm">
                                    <i class="bi bi-clock-history text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900">{{ $log['action'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $log['user'] }}
                                        @if (! empty($log['model']) && $log['model'] !== 'N/A')
                                            · {{ $log['model'] }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-[11px] font-medium text-slate-400">{{ $log['time'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full p-4">
                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                            </div>
                        @endforelse
                    </div>
            </section>
            </div>

            {{-- Health --}}
            <div id="report-health" class="scroll-mt-28 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Health</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Platform status</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Health of critical services</p>
                </div>

                <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @forelse ($platformStatus as $service)
                            <div @class([
                                'btn-smooth glass-card flex items-center gap-3 p-4 hover:-translate-y-1',
                                'border-emerald-200/60' => $service['online'],
                                'border-rose-200/60' => ! $service['online'],
                            ])>
                                <span @class([
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm',
                                    'bg-emerald-100 text-emerald-700' => $service['online'],
                                    'bg-rose-100 text-rose-700' => ! $service['online'],
                                ])>
                                    <i class="bi {{ $service['icon'] }}"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span aria-hidden="true" class="text-xs">{{ $service['online'] ? '🟢' : '🔴' }}</span>
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $service['label'] }}</p>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $service['detail'] }}</p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $service['online'],
                                    'bg-rose-100 text-rose-700' => ! $service['online'],
                                ])>
                                    {{ $service['status'] }}
                                </span>
                            </div>
                        @empty
                            <p class="col-span-full py-8 text-center text-sm text-slate-500">Status checks unavailable.</p>
                        @endforelse
                </section>
            </div>

            {{-- Detailed report tabs --}}
            <div id="report-details" class="scroll-mt-28 space-y-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deep dive</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Detailed reports</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Drill into admin, users, payments, and system data</p>
                </div>
            <div class="glass-panel sticky top-[7.5rem] z-20 !rounded-2xl p-2 sm:top-32">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        'admin' => ['label' => 'Admin Reports', 'icon' => 'bi-speedometer2', 'desc' => 'Platform overview'],
                        'users' => ['label' => 'User Reports', 'icon' => 'bi-people', 'desc' => 'User insights'],
                        'payments' => ['label' => 'Payment Reports', 'icon' => 'bi-credit-card', 'desc' => 'Sales & revenue'],
                        'system' => ['label' => 'System Reports', 'icon' => 'bi-activity', 'desc' => 'Usage & logs'],
                    ] as $key => $tab)
                        <button type="button"
                            @click="setTab('{{ $key }}')"
                            :class="activeTab === '{{ $key }}'
                                ? 'bg-indigo-600/95 text-white shadow-sm shadow-indigo-500/25'
                                : 'bg-white/45 text-slate-600 backdrop-blur hover:bg-white/70 hover:text-slate-900'"
                            class="btn-smooth group rounded-xl p-3 text-left transition-all duration-200 hover:-translate-y-0.5 sm:p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg"
                                    :class="activeTab === '{{ $key }}' ? 'bg-white/20' : 'border border-white/60 bg-white/60'">
                                    <i class="bi {{ $tab['icon'] }} text-base"
                                        :class="activeTab === '{{ $key }}' ? 'text-white' : 'text-indigo-600'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ $tab['label'] }}</p>
                                    <p class="truncate text-xs"
                                        :class="activeTab === '{{ $key }}' ? 'text-indigo-100' : 'text-slate-500'">
                                        {{ $tab['desc'] }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Admin Reports --}}
            <div x-show="activeTab === 'admin'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Event status mix and platform summary metrics</p>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="admin" />
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="glass-card chart-expand-hit group p-5 sm:p-6"
                        @click="openChart('eventsStatus', 'Events by Status', 'Current event distribution')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Events by Status</h3>
                                <p class="mt-1 text-sm text-slate-500">Current event distribution</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-blue-50/70 text-blue-600" @click.stop="openChart('eventsStatus', 'Events by Status', 'Current event distribution')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-72">
                            <canvas id="adminEventsStatusChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>

                    <div class="glass-card p-5 sm:p-6">
                        <h3 class="text-lg font-bold text-slate-900">Platform Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Key oversight metrics</p>
                        <dl class="mt-6 space-y-3">
                            @foreach ([
                                ['Hosts', $admin['totalHosts']],
                                ['Categories', $admin['totalCategories']],
                                ['Gross Revenue', 'LKR ' . number_format($admin['totalRevenue'], 2)],
                                ['Refunds Deducted', 'LKR ' . number_format($admin['totalRevenue'] - $admin['netRevenue'], 2)],
                            ] as [$label, $value])
                                <div class="btn-smooth flex items-center justify-between rounded-xl border border-white/60 bg-white/40 px-4 py-3 backdrop-blur-sm hover:bg-white/70">
                                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                                    <dd class="text-sm font-bold text-slate-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
                {{-- Keep hidden canvases for charts still registered in JS but moved out of overview tabs --}}
                <div class="hidden" aria-hidden="true">
                    <canvas id="adminPlatformGrowthChart"></canvas>
                    <canvas id="adminTopCategoriesChart"></canvas>
                </div>
            </div>

            {{-- User Reports --}}
            <div x-show="activeTab === 'users'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Account health and latest registrations</p>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="users" />
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['label' => 'Total Users', 'value' => $users['totalUsers'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-people-fill'],
                        ['label' => 'Active Users', 'value' => $users['activeUsers'], 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-person-check'],
                        ['label' => 'Verified', 'value' => $users['verifiedUsers'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-patch-check'],
                        ['label' => 'New This Month', 'value' => $users['newUsersThisMonth'], 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'icon' => 'bi-person-plus'],
                    ] as $card)
                        <div class="glass-card kpi-lift p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($card['value']) }}</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $card['bg'] }} backdrop-blur-sm">
                                    <i class="bi {{ $card['icon'] }} text-lg {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="glass-card chart-expand-hit group p-5 sm:p-6"
                        @click="openChart('userStatus', 'Account Status Breakdown', 'Active, verified, and locked accounts')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Account Status Breakdown</h3>
                                <p class="mt-1 text-sm text-slate-500">Active, verified, and locked accounts</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-cyan-50/70 text-cyan-600" @click.stop="openChart('userStatus', 'Account Status Breakdown', 'Active, verified, and locked accounts')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-64">
                            <canvas id="userStatusChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>

                    <div class="glass-card overflow-hidden !p-0">
                        <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-5 py-4 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Recent Users</h3>
                                <p class="mt-1 text-sm text-slate-500">Latest registrations</p>
                            </div>
                            <a href="{{ route('admin.users') }}" class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800">Manage users →</a>
                        </div>
                        <div class="max-h-80 divide-y divide-white/40 overflow-y-auto">
                            @forelse($users['recentUsers'] as $recentUser)
                                <div class="btn-smooth flex items-center justify-between gap-4 px-5 py-4 hover:bg-white/45 sm:px-6">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $recentUser['name'] }}</p>
                                        <p class="truncate text-sm text-slate-500">{{ $recentUser['email'] }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                            {{ ucfirst($recentUser['role']) }}
                                        </span>
                                        <p class="mt-1 text-xs text-slate-400">{{ $recentUser['joined'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4">
                                    <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="hidden" aria-hidden="true">
                    <canvas id="userRoleChart"></canvas>
                    <canvas id="userRegistrationChart"></canvas>
                </div>
            </div>

            {{-- Payment Reports --}}
            <div x-show="activeTab === 'payments'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Payment status, methods, and recent transactions</p>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="payments" />
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['label' => 'Total Revenue', 'value' => 'LKR ' . number_format($payments['totalRevenue'], 2), 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-graph-up-arrow'],
                        ['label' => 'Net Revenue', 'value' => 'LKR ' . number_format($payments['netRevenue'], 2), 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-wallet2'],
                        ['label' => 'Tickets Sold', 'value' => number_format($payments['ticketsSold']), 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-ticket-perforated-fill'],
                        ['label' => 'Total Refunded', 'value' => 'LKR ' . number_format($payments['totalRefunded'], 2), 'color' => 'text-rose-600', 'bg' => 'bg-rose-100', 'icon' => 'bi-arrow-counterclockwise'],
                    ] as $card)
                        <div class="glass-card kpi-lift p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $card['value'] }}</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $card['bg'] }} backdrop-blur-sm">
                                    <i class="bi {{ $card['icon'] }} text-lg {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="glass-card chart-expand-hit group p-5 sm:p-6"
                        @click="openChart('paymentStatus', 'Payment Status', 'Breakdown by transaction status')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Payment Status</h3>
                                <p class="mt-1 text-sm text-slate-500">Breakdown by transaction status</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600" @click.stop="openChart('paymentStatus', 'Payment Status', 'Breakdown by transaction status')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-64">
                            <canvas id="paymentStatusChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>

                    <div class="glass-card chart-expand-hit group p-5 sm:p-6"
                        @click="openChart('paymentMethod', 'Payment Methods', 'Stripe vs wallet usage')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Payment Methods</h3>
                                <p class="mt-1 text-sm text-slate-500">Stripe vs wallet usage</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-cyan-50/70 text-cyan-600" @click.stop="openChart('paymentMethod', 'Payment Methods', 'Stripe vs wallet usage')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-64">
                            <canvas id="paymentMethodChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>
                </div>

                <div class="glass-card overflow-hidden !p-0">
                    <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-5 py-4 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $payments['pendingPayments'] }} pending · {{ $payments['pendingRefunds'] }} refund requests
                            </p>
                        </div>
                    </div>
                    <div class="max-h-80 divide-y divide-white/40 overflow-y-auto">
                        @forelse($payments['recentPayments'] as $payment)
                            <div class="btn-smooth flex items-center justify-between gap-4 px-5 py-4 hover:bg-white/45 sm:px-6">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $payment['user'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $payment['reference'] }} · {{ ucfirst($payment['method']) }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-bold text-slate-900">LKR {{ number_format($payment['amount'], 2) }}</p>
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        'bg-emerald-100 text-emerald-700' => $payment['status'] === 'completed',
                                        'bg-amber-100 text-amber-700' => $payment['status'] === 'pending',
                                        'bg-rose-100 text-rose-700' => in_array($payment['status'], ['failed', 'cancelled']),
                                    ])>
                                        {{ ucfirst($payment['status']) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4">
                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="hidden" aria-hidden="true">
                    <canvas id="paymentRevenueChart"></canvas>
                </div>
            </div>

            {{-- System Reports --}}
            <div x-show="activeTab === 'system'" x-cloak class="space-y-6">
                <x-report-section-header title="System Reports" description="Platform performance, usage trends, and activity logs">
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="system" />
                </x-report-section-header>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['label' => 'Audit Log Entries', 'value' => $system['totalAuditLogs'], 'color' => 'text-purple-600', 'bg' => 'bg-purple-100', 'icon' => 'bi-journal-text'],
                        ['label' => 'Activity Today', 'value' => $system['auditLogsToday'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-lightning'],
                        ['label' => 'This Week', 'value' => $system['auditLogsThisWeek'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-calendar-week'],
                        ['label' => 'Support Tickets', 'value' => $system['totalInquiries'] + $system['totalComplaints'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-100', 'icon' => 'bi-headset'],
                    ] as $card)
                        <div class="glass-card kpi-lift p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($card['value']) }}</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $card['bg'] }} backdrop-blur-sm">
                                    <i class="bi {{ $card['icon'] }} text-lg {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 xl:grid-cols-3">
                    <div class="glass-card chart-expand-hit group p-5 sm:p-6 xl:col-span-2"
                        @click="openChart('systemActivity', 'System Activity Trend', 'Audit log volume over the last 6 months')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">System Activity Trend</h3>
                                <p class="mt-1 text-sm text-slate-500">Audit log volume over the last 6 months</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-purple-50/70 text-purple-600" @click.stop="openChart('systemActivity', 'System Activity Trend', 'Audit log volume over the last 6 months')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-72">
                            <canvas id="systemActivityChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>

                    <div class="glass-card chart-expand-hit group p-5 sm:p-6"
                        @click="openChart('systemAuditActions', 'Top Audit Actions', 'Most frequent system actions')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Top Audit Actions</h3>
                                <p class="mt-1 text-sm text-slate-500">Most frequent system actions</p>
                            </div>
                            <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600" @click.stop="openChart('systemAuditActions', 'Top Audit Actions', 'Most frequent system actions')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-6 h-72">
                            <canvas id="systemAuditActionChart" class="pointer-events-none"></canvas>
                        </div>
                    </div>
                </div>

                <div class="glass-card overflow-hidden !p-0">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Activity Logs</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $system['totalInquiries'] }} inquiries · {{ $system['totalComplaints'] }} complaints
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="system" />
                            <a href="{{ route('admin.audit-logs') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100">
                                View All Logs
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                                    <th class="hidden px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Model</th>
                                    <th class="hidden px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">IP</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($system['recentAuditLogs'] as $log)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ $log['user'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $log['action'] }}</td>
                                        <td class="hidden px-6 py-4 text-sm text-slate-500 sm:table-cell">{{ $log['model'] }}</td>
                                        <td class="hidden px-6 py-4 text-sm text-slate-500 md:table-cell">{{ $log['ip'] }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-400">{{ $log['time'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>

        </div>

        {{-- Fullscreen chart modal --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/50 bg-white/85 shadow-2xl shadow-indigo-500/10 backdrop-blur-2xl"
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
                <div class="flex items-start justify-between gap-4 border-b border-white/50 bg-white/40 px-5 py-4 backdrop-blur-sm sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-slate-500 backdrop-blur hover:bg-white hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full">
                        <canvas id="adminReportsChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-white/50 bg-white/30 px-5 py-3 text-xs text-slate-400 backdrop-blur-sm sm:px-6">
                    Press <kbd class="rounded border border-slate-200/80 bg-white/70 px-1.5 py-0.5 font-semibold text-slate-600">Esc</kbd> to close · click chart cards to expand
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
            window.adminReportData = @json($reports);
        </script>
        @vite('resources/js/admin-reports.js')
    @endpush
</x-app-layout>

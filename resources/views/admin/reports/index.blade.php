<x-app-layout>
    @php
        $admin = $reports['admin'];
        $users = $reports['users'];
        $payments = $reports['payments'];
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
        $ticketSalesTrend = $overview['ticketSalesTrend'] ?? [
            'weekly' => $ticketSalesWeekly,
            'monthly' => [],
            'yearly' => [],
        ];
        $recentUsers = $overview['recentUsers'] ?? [];
        $organizerPerformance = $overview['organizerPerformance'] ?? [];
        $recentPayments = $overview['recentPayments'] ?? [];
        $platformStatus = $overview['platformStatus'] ?? [];
        $eventsByCategory = $overview['eventsByCategory'] ?? [];
        $eventsByCategoryMax = max(1, (int) collect($eventsByCategory)->max('count'));

        $scopeFilter = $reports['scopeFilter'] ?? [
            'scope' => 'global',
            'organizers' => [],
            'events' => [],
            'selectedOrganizerId' => null,
            'selectedOrganizerName' => null,
            'selectedEventId' => null,
            'selectedEventName' => null,
        ];
        $isScoped = ($scopeFilter['scope'] ?? 'global') !== 'global';
        $scopeCaption = match ($scopeFilter['scope'] ?? 'global') {
            'event' => 'Filtered to event: '.($scopeFilter['selectedEventName'] ?? '—'),
            'organizer' => 'Filtered to organizer: '.($scopeFilter['selectedOrganizerName'] ?? '—'),
            default => 'Platform-wide insights across users, events, and revenue.',
        };
        $exportFilters = array_filter([
            'organizer' => $scopeFilter['selectedOrganizerId'] ?? null,
            'event' => $scopeFilter['selectedEventId'] ?? null,
        ], fn ($value) => filled($value));

        $userGrowthCounts = collect($overview['userGrowth'] ?? $users['registrationTrend'] ?? []);
        $userGrowthLabels = collect($reports['chartLabelsShort'] ?? []);
        $peakRegistrationCount = (int) ($userGrowthCounts->max() ?? 0);
        $peakRegistrationIndex = $userGrowthCounts
            ->filter(fn ($count) => (int) $count === $peakRegistrationCount)
            ->keys()
            ->last();
        $peakRegistrationMonth = $peakRegistrationIndex !== null
            ? ($userGrowthLabels[$peakRegistrationIndex] ?? '—')
            : '—';

        $tabs = [
            'overview' => ['label' => 'Overview', 'icon' => 'bi-graph-up', 'desc' => 'Growth & marketplace'],
            'activity' => ['label' => 'Activity', 'icon' => 'bi-lightning', 'desc' => 'Recent platform events'],
            'admin' => ['label' => 'Admin', 'icon' => 'bi-speedometer2', 'desc' => 'Events & summary'],
            'users' => ['label' => 'Users', 'icon' => 'bi-people', 'desc' => 'Accounts & roles'],
            'payments' => ['label' => 'Payments', 'icon' => 'bi-credit-card', 'desc' => 'Sales & revenue'],
        ];
    @endphp

    <script>
        window.adminReportTicketSalesTrend = @json($ticketSalesTrend);
        window.adminReportsPage = function adminReportsPage() {
            return {
                activeTab: 'overview',
                open: false,
                chartKey: null,
                title: '',
                description: '',
                ticketSalesRange: 'weekly',
                ticketSalesTrend: window.adminReportTicketSalesTrend || {
                    weekly: [],
                    monthly: [],
                    yearly: [],
                },
                ticketSalesCaptions: {
                    weekly: 'Confirmed ticket sales over the last 12 weeks',
                    monthly: 'Confirmed ticket sales over the last 12 months',
                    yearly: 'Confirmed ticket sales over the last 5 years',
                },
                get ticketSalesPoints() {
                    return this.ticketSalesTrend[this.ticketSalesRange] ?? [];
                },
                get ticketSalesCaption() {
                    return this.ticketSalesCaptions[this.ticketSalesRange]
                        ?? this.ticketSalesCaptions.weekly;
                },
                setTicketSalesRange(range) {
                    if (this.ticketSalesRange === range) return;
                    this.ticketSalesRange = range;
                    window.dispatchEvent(new CustomEvent('admin-reports-ticket-range', {
                        detail: { range },
                    }));
                },
                openChart(key, title, description) {
                    this.chartKey = key;
                    this.title = title;
                    this.description = description;
                    this.open = true;
                    document.body.classList.add('overflow-hidden');
                    this.$nextTick(() => {
                        setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('admin-reports-chart-expand', {
                                detail: { key, range: this.ticketSalesRange },
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
                        document.getElementById('report-panels')?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                    });
                },
            };
        };
    </script>

    <div class="admin-reports relative isolate py-5 sm:py-6"
        x-data="adminReportsPage()"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Page header --}}
            <section class="glass-panel !rounded-2xl px-4 py-4 sm:px-6 sm:py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Analytics</p>
                        <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Reports
                        </h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500">
                            {{ $scopeCaption }}
                        </p>
                    </div>

                    <div class="flex flex-col items-stretch gap-2 sm:items-end">
                        <p class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 sm:justify-end">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            Updated {{ now()->diffForHumans() }}
                        </p>
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                            <x-report-export-buttons
                                excel-route="admin.reports.export.excel"
                                pdf-route="admin.reports.export.pdf"
                                scope="admin"
                                section="admin"
                                :filters="$exportFilters"
                                filter-form-id="admin-reports-scope-filter" />
                        </div>
                    </div>
                </div>

                {{-- Organizer / event scope filter --}}
                <form id="admin-reports-scope-filter"
                    method="GET"
                    action="{{ route('admin.reports') }}"
                    class="mt-4 flex flex-col gap-2 border-t border-white/60 pt-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="min-w-0 flex-1 sm:max-w-xs">
                        <label for="reports_organizer" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Organizer
                        </label>
                        <select
                            id="reports_organizer"
                            name="organizer"
                            class="block w-full rounded-xl border-white/70 bg-white/70 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All organizers</option>
                            @foreach ($scopeFilter['organizers'] as $organizerOption)
                                <option value="{{ $organizerOption['id'] }}"
                                    @selected((int) ($scopeFilter['selectedOrganizerId'] ?? 0) === (int) $organizerOption['id'])>
                                    {{ $organizerOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($scopeFilter['selectedOrganizerId'])
                        <div class="min-w-0 flex-1 sm:max-w-xs">
                            <label for="reports_event" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                Event
                            </label>
                            <select
                                id="reports_event"
                                name="event"
                                class="block w-full rounded-xl border-white/70 bg-white/70 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All events</option>
                                @foreach ($scopeFilter['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($scopeFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($isScoped)
                        <a href="{{ route('admin.reports') }}"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white/80 sm:mb-0.5">
                            <i class="bi bi-x-circle"></i>
                            Clear filters
                        </a>
                    @endif
                </form>

                {{-- Today's highlights --}}
                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-white/60 pt-4 sm:grid-cols-4 sm:gap-3">
                    @foreach (array_values(array_filter([
                        [
                            'label' => $isScoped ? 'Buyers Today' : 'New Users',
                            'value' => $highlights['newUsers'],
                            'icon' => 'bi-person-plus',
                            'tone' => 'indigo',
                        ],
                        [
                            'label' => 'New Events',
                            'value' => $highlights['newEvents'],
                            'icon' => 'bi-calendar-plus',
                            'tone' => 'blue',
                        ],
                        [
                            'label' => 'Tickets Sold',
                            'value' => $highlights['ticketsSold'],
                            'icon' => 'bi-ticket-perforated',
                            'tone' => 'cyan',
                        ],
                        $isScoped ? null : [
                            'label' => 'Pending Approvals',
                            'value' => $highlights['pendingOrganizerApprovals'],
                            'icon' => 'bi-person-check',
                            'tone' => 'amber',
                        ],
                        $isScoped ? [
                            'label' => 'Net Revenue',
                            'value' => $payments['netRevenue'],
                            'icon' => 'bi-cash-stack',
                            'tone' => 'amber',
                            'money' => true,
                        ] : null,
                    ])) as $item)
                        <div class="flex items-center gap-2.5 rounded-xl border border-white/60 bg-white/40 px-3 py-2.5 backdrop-blur-sm">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm',
                                'bg-indigo-100/80 text-indigo-600' => $item['tone'] === 'indigo',
                                'bg-blue-100/80 text-blue-600' => $item['tone'] === 'blue',
                                'bg-cyan-100/80 text-cyan-600' => $item['tone'] === 'cyan',
                                'bg-amber-100/80 text-amber-600' => $item['tone'] === 'amber',
                            ])>
                                <i class="bi {{ $item['icon'] }}"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold tabular-nums text-slate-900">
                                    @if (! empty($item['money']))
                                        LKR {{ number_format((float) $item['value'], 0) }}
                                    @else
                                        {{ number_format($item['value']) }}
                                    @endif
                                </p>
                                <p class="truncate text-[11px] font-medium text-slate-500">{{ $item['label'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- KPI cards --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <button type="button" @click="setTab('users')" class="glass-card kpi-lift border-t-4 border-t-indigo-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpis['usersLabel'] ?? 'Total Users' }}</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['totalUsers']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-emerald-600">
                                +{{ number_format($kpis['usersToday']) }}
                                {{ strtolower($kpis['usersSubLabel'] ?? 'today') }}
                            </p>
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

                <button type="button" @click="setTab('admin')" class="glass-card kpi-lift border-t-4 border-t-blue-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Events</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['totalEvents']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-blue-600">+{{ number_format($kpis['eventsThisWeek']) }} this week</p>
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

                <button type="button" @click="setTab('overview')" class="glass-card kpi-lift border-t-4 border-t-cyan-500 p-4 text-left sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tickets Sold</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['ticketsSold']) }}</p>
                            <p class="mt-1 text-xs font-semibold text-cyan-600">+{{ number_format($kpis['ticketsToday']) }} today</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100/70 text-cyan-600 backdrop-blur-sm">
                            <i class="bi bi-ticket-perforated text-lg"></i>
                        </span>
                    </div>
                </button>
            </section>

            {{-- Primary tab navigation (sticky under main nav) --}}
            <nav id="report-panels" class="sticky top-16 z-40 -mx-4 scroll-mt-28 px-4 py-2 sm:top-[4.5rem] sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" aria-label="Report sections">
                <div class="overflow-x-auto rounded-2xl border border-white/70 bg-white/90 p-1.5 shadow-md shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-1 sm:grid sm:min-w-0 sm:grid-cols-5 sm:gap-1.5">
                        @foreach ($tabs as $key => $tab)
                            <button type="button"
                                @click="setTab('{{ $key }}')"
                                :class="activeTab === '{{ $key }}'
                                    ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/20'
                                    : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                                class="btn-smooth inline-flex items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition sm:flex-col sm:gap-1 sm:px-2 sm:py-3 lg:flex-row lg:gap-2 lg:px-3">
                                <i class="bi {{ $tab['icon'] }} text-sm opacity-90"></i>
                                <span>{{ $tab['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </nav>

            {{-- Overview (no x-cloak so charts can measure on first paint) --}}
            <div x-show="activeTab === 'overview'" class="space-y-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Growth & marketplace</h2>
                        <p class="text-sm text-slate-500">Users, revenue, tickets, categories, and organizers · click charts to expand</p>
                    </div>
                </div>

                <section class="grid gap-4 lg:grid-cols-5">
                    <div class="glass-card chart-expand-hit group p-4 sm:p-5 lg:col-span-3"
                        @click="openChart('userGrowth', 'User Growth', 'New registrations over the last 6 months')">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Growth</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">User Growth</h3>
                                <p class="mt-0.5 text-sm text-slate-500">New registrations over the last 6 months</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90"
                                title="View fullscreen"
                                aria-label="View User Growth fullscreen"
                                @click.stop="openChart('userGrowth', 'User Growth', 'New registrations over the last 6 months')">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <div class="mb-4 rounded-xl border border-indigo-100/80 bg-indigo-50/50 px-3.5 py-3" @click.stop>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Highest Registration Month</p>
                            <div class="mt-1 flex items-baseline gap-2">
                                <p class="text-xl font-bold tracking-tight text-slate-900">{{ $peakRegistrationMonth }}</p>
                                <p class="text-sm font-semibold text-slate-500">
                                    {{ number_format($peakRegistrationCount) }}
                                    {{ $peakRegistrationCount === 1 ? 'User' : 'Users' }}
                                </p>
                            </div>
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
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">User Distribution</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Platform role makeup</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-cyan-50/70 text-cyan-600 backdrop-blur hover:bg-cyan-100/90"
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

                <section class="grid gap-4 lg:grid-cols-2">
                    <div class="glass-card chart-expand-hit group p-4 sm:p-5"
                        @click="openChart('revenueTrend', 'Revenue Trend', 'Monthly platform revenue')">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Revenue</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">Revenue Trend</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Monthly platform revenue</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-emerald-50/70 text-emerald-600 backdrop-blur hover:bg-emerald-100/90"
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
                                    <div class="rounded-lg border border-white/60 bg-emerald-50/50 px-2 py-1.5 text-center backdrop-blur-sm">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $point['month'] }}</p>
                                        <p class="mt-0.5 text-xs font-bold text-emerald-700">{{ $point['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="glass-card chart-expand-hit group p-4 sm:p-5"
                        @click="openChart('ticketSalesTrend', 'Ticket Sales Trend', ticketSalesCaption)">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Tickets</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">Ticket Sales Trend</h3>
                                <p class="mt-0.5 text-sm text-slate-500" x-text="ticketSalesCaption"></p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-blue-50/70 text-blue-600 backdrop-blur hover:bg-blue-100/90"
                                title="View fullscreen"
                                @click.stop="openChart('ticketSalesTrend', 'Ticket Sales Trend', ticketSalesCaption)">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>

                        <div class="mb-4 inline-flex rounded-xl border border-white/70 bg-white/55 p-1 shadow-sm backdrop-blur" @click.stop>
                            <template x-for="option in [
                                { key: 'weekly', label: 'Weekly' },
                                { key: 'monthly', label: 'Monthly' },
                                { key: 'yearly', label: 'Yearly' },
                            ]" :key="option.key">
                                <button type="button"
                                    @click="setTicketSalesRange(option.key)"
                                    :class="ticketSalesRange === option.key
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 hover:bg-white/80 hover:text-slate-900'"
                                    class="btn-smooth rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                    x-text="option.label">
                                </button>
                            </template>
                        </div>

                        <div class="h-64">
                            <canvas id="adminOverviewTicketSalesChart" class="pointer-events-none"></canvas>
                        </div>

                        <div class="mt-4 grid gap-2 border-t border-white/50 pt-3"
                            :class="{
                                'grid-cols-4 sm:grid-cols-6': ticketSalesRange === 'weekly',
                                'grid-cols-4 sm:grid-cols-6': ticketSalesRange === 'monthly',
                                'grid-cols-3 sm:grid-cols-5': ticketSalesRange === 'yearly',
                            }"
                            @click.stop>
                            <template x-for="point in ticketSalesPoints" :key="point.label + '-' + point.count">
                                <div class="rounded-lg border border-white/60 bg-blue-50/50 px-2 py-1.5 text-center backdrop-blur-sm">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500" x-text="point.label"></p>
                                    <p class="mt-0.5 text-sm font-bold text-blue-700" x-text="Number(point.count).toLocaleString()"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-5">
                    <div class="glass-card chart-expand-hit group p-4 sm:p-5 lg:col-span-3"
                        @click="openChart('eventsByCategory', 'Event Category Analytics', 'Number of events by category')">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Categories</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">Event Category Analytics</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Number of events by category</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90"
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
                            <h3 class="text-base font-bold text-slate-900">Organizer Performance</h3>
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

                <section>
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-slate-900">Platform status</h3>
                        <p class="text-sm text-slate-500">Health of critical services</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @forelse ($platformStatus as $service)
                            <div @class([
                                'btn-smooth glass-card flex items-center gap-3 p-4 hover:-translate-y-0.5',
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
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $service['label'] }}</p>
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
                    </div>
                </section>
            </div>

            {{-- Activity --}}
            <div x-show="activeTab === 'activity'" x-cloak class="space-y-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recent activity</h2>
                    <p class="text-sm text-slate-500">Latest registrations and payments</p>
                </div>

                <section class="grid gap-4 lg:grid-cols-2">
                    <div class="glass-card overflow-hidden !p-0">
                        <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Recent User Registrations</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Latest users on the platform</p>
                            </div>
                            <a href="{{ route('admin.users') }}"
                                class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                View all →
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
                                <h3 class="text-base font-bold text-slate-900">Recent Payments</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Latest platform transactions</p>
                            </div>
                            <button type="button"
                                @click="setTab('payments')"
                                class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                View all →
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
            </div>

            {{-- Admin Reports --}}
            <div x-show="activeTab === 'admin'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Admin reports</h2>
                        <p class="text-sm text-slate-500">Event status mix and platform summary metrics</p>
                    </div>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="admin" :filters="$exportFilters" filter-form-id="admin-reports-scope-filter" />
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
                                ['Artists', $admin['totalArtists']],
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
                <div class="hidden" aria-hidden="true">
                    <canvas id="adminPlatformGrowthChart"></canvas>
                    <canvas id="adminTopCategoriesChart"></canvas>
                </div>
            </div>

            {{-- User Reports --}}
            <div x-show="activeTab === 'users'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">User reports</h2>
                        <p class="text-sm text-slate-500">
                            @if ($isScoped)
                                Platform account metrics (not limited by organizer/event filter)
                            @else
                                Account health and latest registrations
                            @endif
                        </p>
                    </div>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="users" :filters="$exportFilters" filter-form-id="admin-reports-scope-filter" />
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
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Payment reports</h2>
                        <p class="text-sm text-slate-500">Payment status, methods, and recent transactions</p>
                    </div>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" scope="admin" section="payments" :filters="$exportFilters" filter-form-id="admin-reports-scope-filter" />
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
                    <div class="border-b border-white/50 bg-white/30 px-5 py-4 backdrop-blur-sm sm:px-6">
                        <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $payments['pendingPayments'] }} pending · {{ $payments['pendingRefunds'] }} refund requests
                        </p>
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

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('admin-reports-scope-filter');
                if (!form) return;

                form.querySelectorAll('select[name="organizer"], select[name="event"]').forEach(function (select) {
                    select.addEventListener('change', function () {
                        if (this.name === 'organizer') {
                            const eventSelect = form.querySelector('select[name="event"]');
                            if (eventSelect) {
                                eventSelect.selectedIndex = 0;
                            }
                        }
                        form.submit();
                    });
                });
            });
        </script>
        @vite('resources/js/admin-reports.js')
    @endpush
</x-app-layout>

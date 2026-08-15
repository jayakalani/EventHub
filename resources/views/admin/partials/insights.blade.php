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
        $recentPayments = $overview['recentPayments'] ?? [];
        $platformStatus = $overview['platformStatus'] ?? [];

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
                    const map = { events: 'admin' };
                    this.activeTab = map[tab] || tab;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('admin-reports-tab-changed'));
                    });
                },
                syncFromDashboard(section) {
                    const allowed = ['overview', 'activity', 'events', 'users', 'payments', 'admin'];
                    if (!allowed.includes(section)) return;
                    const map = { events: 'admin' };
                    const tab = map[section] || section;
                    if (this.activeTab === tab) return;
                    this.activeTab = tab;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('admin-reports-tab-changed'));
                    });
                },
                init() {
                    const hash = window.location.hash.replace(/^#/, '');
                    const map = { events: 'admin', insights: 'overview', reports: 'overview', admin: 'admin' };
                    if (map[hash] || ['overview', 'activity', 'users', 'payments'].includes(hash)) {
                        this.activeTab = map[hash] || hash;
                    }
                },
            };
        };
    </script>

    <div id="insights"
        class="admin-reports space-y-5 scroll-mt-24"
        x-data="adminReportsPage()"
        @keydown.escape.window="if (open) closeChart()"
        @admin-dashboard-section-changed.window="syncFromDashboard($event.detail.section)">

            
            {{-- Overview (no x-cloak so charts can measure on first paint) --}}
            <div x-show="activeTab === 'overview'" class="space-y-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Growth & marketplace</h2>
                        <p class="text-sm text-slate-500">Users, revenue, and ticket trends · click charts to expand</p>
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

                <div class="hidden" aria-hidden="true">
                    <canvas id="adminOverviewEventsByCategoryChart"></canvas>
                </div>

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
                    <p class="text-sm text-slate-500">Latest platform payments</p>
                </div>

                <section>
                    <div class="glass-card overflow-hidden !p-0">
                        <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Recent Payments</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Latest platform transactions</p>
                            </div>
                            <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('admin-open-section', { detail: { section: 'payments' }}))"
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

            {{-- Events --}}
            <div x-show="activeTab === 'admin'" x-cloak class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Events</h2>
                        <p class="text-sm text-slate-500">Event status mix and platform summary metrics</p>
                    </div>
                    <a href="{{ route('admin.events.index') }}"
                        class="btn-smooth inline-flex items-center gap-1.5 self-start text-xs font-semibold text-indigo-600 hover:text-indigo-800 sm:self-auto">
                        Open events list →
                    </a>
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

            {{-- Users --}}
            <div x-show="activeTab === 'users'" x-cloak class="space-y-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Users</h2>
                    <p class="text-sm text-slate-500">
                        @if ($isScoped)
                            Platform account metrics (not limited by organizer/event filter)
                        @else
                            Account health and status breakdown
                        @endif
                    </p>
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
                <div class="hidden" aria-hidden="true">
                    <canvas id="userRoleChart"></canvas>
                    <canvas id="userRegistrationChart"></canvas>
                </div>
            </div>

            {{-- Payments --}}
            <div x-show="activeTab === 'payments'" x-cloak class="space-y-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Payments</h2>
                    <p class="text-sm text-slate-500">Payment status, methods, and recent transactions</p>
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


        {{-- Fullscreen chart modal --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/50 bg-white/85 shadow-2xl shadow-indigo-500/10 backdrop-blur-2xl"
                x-show="open"
                @click.stop
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


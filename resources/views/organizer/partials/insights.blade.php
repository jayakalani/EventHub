    @php
        $sales = $reports['ticketSales'];
        $revenue = $reports['revenue'];
        $attendees = $reports['attendees'];
        $engagement = $reports['engagement'];
        $salesByCategory = $reports['salesByCategory'] ?? [];
        $eventPerformance = $reports['eventPerformance'] ?? [];
        $eventsByStatus = $reports['eventsByStatus'] ?? [];
        $attendance = $reports['attendance'] ?? [
            'ticketsEligible' => 0,
            'checkedIn' => 0,
            'noShows' => 0,
            'awaitingCheckIn' => 0,
            'attendanceRate' => null,
            'eventsWithTickets' => 0,
            'eventsFinalized' => 0,
            'peakTiming' => null,
            'byEvent' => [],
            'checkInTiming' => [],
            'breakdown' => [],
        ];
        $recentTransactions = $reports['recentTransactions'] ?? [];
        $postponedEventsCount = (int) (collect($eventsByStatus)->firstWhere('key', 'postponed')['count'] ?? 0);
        $filterOptions = $reports['filterOptions'] ?? ['events' => [], 'statuses' => []];
        $activeFilters = $reports['filters'] ?? ['from' => null, 'to' => null, 'event_id' => null, 'status' => null];

        $avgTicketsPerEvent = $sales['totalEvents'] > 0
            ? round($sales['totalTicketsSold'] / $sales['totalEvents'], 1)
            : 0;
        $hasActiveFilters = filled($activeFilters['from'] ?? null)
            || filled($activeFilters['to'] ?? null)
            || filled($activeFilters['event_id'] ?? null)
            || filled($activeFilters['status'] ?? null);
        $hasReportData = ((int) ($sales['totalTicketsSold'] ?? 0) > 0)
            || ((float) ($revenue['grossRevenue'] ?? 0) > 0)
            || count($eventPerformance) > 0
            || count($recentTransactions) > 0
            || ((int) ($attendance['ticketsEligible'] ?? 0) > 0)
            || ((int) ($attendance['checkedIn'] ?? 0) > 0);
        $ticketTypeTrend = $reports['ticketTypeTrend'] ?? [];
        $conversionFunnel = $reports['conversionFunnel'] ?? [];
        $salesVelocity = $reports['salesVelocity'] ?? [
            'windowDays' => 30,
            'labels' => [],
            'tickets' => [],
            'cumulative' => [],
            'totalInWindow' => 0,
            'peak' => null,
            'finalWeekShare' => null,
            'earlyShare' => null,
            'midShare' => null,
        ];
        $funnelByLabel = collect($conversionFunnel)->keyBy('label');
        $funnelViews = (int) ($funnelByLabel->get('Views')['count'] ?? 0);
        $funnelPurchases = (int) ($funnelByLabel->get('Purchases')['count'] ?? 0);
        $overallConversion = $funnelViews > 0
            ? round(($funnelPurchases / $funnelViews) * 100, 1)
            : null;

        $summaryTrends = $reports['summaryTrends'] ?? [
            'netRevenue' => ['percent' => 0, 'up' => true, 'label' => 'vs last month'],
            'ticketsSold' => ['percent' => 0, 'up' => true, 'label' => 'vs last month'],
            'events' => ['percent' => 0, 'up' => true, 'label' => 'vs last month'],
            'attendees' => ['percent' => 0, 'up' => true, 'label' => 'vs last month'],
            'engagement' => ['percent' => 0, 'up' => true, 'label' => 'vs last month'],
        ];

        $opsQuery = $filterQuery ?? [];

        $filterQueryBase = array_filter(array_merge($opsQuery, [
            'event_id' => $activeFilters['event_id'] ?? null,
            'status' => $activeFilters['status'] ?? null,
        ]), fn ($value) => filled($value));

        // Passed to Excel/PDF so exports match the on-screen filtered (or unfiltered) dataset.
        $exportFilters = array_filter([
            'from' => $activeFilters['from'] ?? null,
            'to' => $activeFilters['to'] ?? null,
            'event_id' => $activeFilters['event_id'] ?? null,
            'status' => $activeFilters['status'] ?? null,
        ], fn ($value) => filled($value));

        $datePresets = [
            [
                'key' => '7d',
                'label' => 'Last 7 days',
                'from' => now()->subDays(6)->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => '30d',
                'label' => 'Last 30 days',
                'from' => now()->subDays(29)->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'month',
                'label' => 'This month',
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'last_month',
                'label' => 'Last month',
                'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            [
                'key' => 'year',
                'label' => 'This year',
                'from' => now()->startOfYear()->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'all',
                'label' => 'All time',
                'from' => null,
                'to' => null,
            ],
        ];

        $activePreset = collect($datePresets)->first(function (array $preset) use ($activeFilters) {
            if (($preset['key'] ?? '') === 'all') {
                return blank($activeFilters['from'] ?? null) && blank($activeFilters['to'] ?? null);
            }

            return ($activeFilters['from'] ?? null) === ($preset['from'] ?? null)
                && ($activeFilters['to'] ?? null) === ($preset['to'] ?? null);
        });
        $activePresetKey = $activePreset['key'] ?? null;

        $salesDeepLink = array_filter([
            'event_id' => $activeFilters['event_id'] ?? null,
            'from_date' => $activeFilters['from'] ?? null,
            'to_date' => $activeFilters['to'] ?? null,
        ], fn ($value) => filled($value));

        $salesHeatmap = $reports['salesHeatmap'] ?? [
            'month_label' => now()->format('F Y'),
            'start_weekday' => 0,
            'max_sales' => 1,
            'days' => [],
        ];
        $heatmapMax = max(1, (int) ($salesHeatmap['max_sales'] ?? 1));

        $peakSalesHeatmap = $reports['peakSalesHeatmap'] ?? [
            'day_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'hour_labels' => [],
            'matrix' => [],
            'max_sales' => 1,
            'peak' => null,
        ];
        $peakHeatmapMax = max(1, (int) ($peakSalesHeatmap['max_sales'] ?? 1));

        $demographics = $attendees['demographics'] ?? ['age' => [], 'gender' => [], 'location' => [], 'province' => [], 'available' => ['age' => false, 'gender' => false, 'location' => false, 'province' => false, 'any' => false]];
        $demographicsAvailable = $demographics['available'] ?? [
            'age' => false,
            'gender' => false,
            'location' => false,
            'province' => false,
            'any' => false,
        ];
        $topCustomers = $attendees['topCustomers'] ?? [];
        $engagementVsSalesRows = collect($engagement['engagementVsSales'] ?? [])->take(8)->values();
        $engagementInsightClass = fn (string $insight) => match ($insight) {
            'Strong on both' => 'bg-emerald-50 text-emerald-700',
            'High interest, low sales' => 'bg-amber-50 text-amber-800',
            'Interest, no sales yet' => 'bg-amber-50 text-amber-800',
            'Sales without much buzz' => 'bg-sky-50 text-sky-800',
            'Selling with little buzz' => 'bg-slate-100 text-slate-600',
            'Needs more promotion' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-500',
        };
        $revenueFillRows = collect($eventPerformance ?? [])
            ->sortByDesc('fill_rate')
            ->take(8)
            ->values();
        $revenueFillInsightClass = fn (string $insight) => match ($insight) {
            'Strong seller' => 'bg-emerald-50 text-emerald-700',
            'Making money, lots of unsold seats' => 'bg-amber-50 text-amber-800',
            'Filling up, lower revenue' => 'bg-sky-50 text-sky-800',
            'Revenue recorded, no capacity set' => 'bg-slate-100 text-slate-600',
            'Needs more promotion' => 'bg-rose-50 text-rose-700',
            default => 'bg-slate-100 text-slate-500',
        };
        $eventComparison = $reports['eventComparison'] ?? [];
        $refundAnalytics = $reports['refundAnalytics'] ?? [
            'grossRevenue' => 0,
            'totalRefunded' => 0,
            'refundCount' => 0,
            'refundRate' => 0,
            'refundTrend' => [],
            'byEvent' => [],
            'byCategory' => [],
        ];
        $reviewQuality = $engagement['reviewQuality'] ?? [
            'averageRating' => null,
            'totalRatings' => 0,
            'averageTrend' => [],
            'countTrend' => [],
            'distribution' => [],
            'lowRatedEvents' => [],
            'responseRate' => null,
            'topRatedEvents' => [],
        ];

        $navSections = [
            ['id' => 'revenue', 'label' => 'Revenue', 'icon' => 'bi-cash-stack'],
            ['id' => 'tickets', 'label' => 'Tickets', 'icon' => 'bi-ticket-perforated'],
            ['id' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event'],
            ['id' => 'attendance', 'label' => 'Attendance', 'icon' => 'bi-person-check'],
            ['id' => 'audience', 'label' => 'Audience', 'icon' => 'bi-people'],
            ['id' => 'engagement', 'label' => 'Engagement', 'icon' => 'bi-heart'],
            ['id' => 'activity', 'label' => 'Activity', 'icon' => 'bi-activity'],
        ];

        $tab = $tab ?? 'revenue';
        $loadedTabs = $loadedTabs ?? [$tab];
    @endphp

    <div id="insights"
        class="space-y-3 scroll-mt-20"
        x-data="{
            performanceQuery: '',
            attendanceQuery: '',
            transactionQuery: '',
            customerQuery: '',
            activeSection: @js($tab ?? 'revenue'),
            loadedTabs: @js($loadedTabs ?? ['revenue']),
            tabLoading: null,
            top5Metric: 'revenue',
            compareIds: [],
            open: false,
            chartKey: null,
            title: '',
            description: '',
            toggleCompare(id) {
                const index = this.compareIds.indexOf(id);
                if (index >= 0) {
                    this.compareIds.splice(index, 1);
                    return;
                }
                if (this.compareIds.length >= 3) {
                    this.compareIds.shift();
                }
                this.compareIds.push(id);
            },
            get comparedEvents() {
                const all = window.organizerReportData?.eventComparison ?? [];
                return this.compareIds
                    .map((id) => all.find((event) => Number(event.id) === Number(id)))
                    .filter(Boolean);
            },
            buildTabUrl(id) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', id);
                url.hash = id;
                const form = document.getElementById('organizer-reports-filters');
                if (form) {
                    const formData = new FormData(form);
                    ['from', 'to', 'status', 'focus_event'].forEach((key) => {
                        const value = formData.get(key);
                        if (value) {
                            url.searchParams.set(key, String(value));
                        } else {
                            url.searchParams.delete(key);
                        }
                    });
                    const focus = formData.get('focus_event');
                    if (focus) {
                        url.searchParams.set('event_id', String(focus));
                    } else {
                        url.searchParams.delete('event_id');
                    }
                }
                return url;
            },
            async setTab(id) {
                if (this.loadedTabs.includes(id)) {
                    this.activeSection = id;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
                            detail: { tab: id },
                        }));
                    });
                    history.replaceState(null, '', this.buildTabUrl(id));
                    return;
                }

                this.tabLoading = id;
                window.location.assign(this.buildTabUrl(id).toString());
            },
            init() {
                const allowed = ['revenue', 'tickets', 'events', 'attendance', 'audience', 'engagement', 'activity'];
                const hash = window.location.hash.replace(/^#/, '');
                const initial = allowed.includes(this.activeSection) ? this.activeSection : 'revenue';
                this.activeSection = allowed.includes(hash) ? hash : initial;

                if (allowed.includes(hash) && !this.loadedTabs.includes(hash)) {
                    this.setTab(hash);
                    return;
                }

                const all = window.organizerReportData?.eventComparison ?? [];
                this.compareIds = all.slice(0, Math.min(3, all.length)).map((event) => event.id);
                window.organizerCompareIds = [...this.compareIds];
                this.$watch('compareIds', (ids) => {
                    window.organizerCompareIds = [...ids];
                    window.dispatchEvent(new CustomEvent('organizer-reports-compare-changed'));
                });
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
                        detail: { tab: this.activeSection },
                    }));
                });
            },
            syncFromDashboard(section) {
                const allowed = ['revenue', 'tickets', 'events', 'attendance', 'audience', 'engagement', 'activity'];
                if (! allowed.includes(section)) {
                    return;
                }
                if (this.activeSection === section) {
                    return;
                }
                if (this.loadedTabs.includes(section)) {
                    this.activeSection = section;
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
                            detail: { tab: section },
                        }));
                    });
                }
            },
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('organizer-reports-chart-expand', {
                            detail: { key },
                        }));
                    }, 220);
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('organizer-reports-chart-collapse'));
            },
            matches(haystack, query) {
                if (!query) return true;
                return String(haystack || '').toLowerCase().includes(query.toLowerCase());
            }
        }"
        @keydown.escape.window="if (open) closeChart()"
        @organizer-dashboard-section-changed.window="syncFromDashboard($event.detail.section)">

        
            @if (! $hasReportData)
                <x-report-empty-state
                    class="!min-h-[10rem] border-slate-200 bg-white shadow-sm"
                    :hint="$hasActiveFilters
                        ? 'Try another date range or event.'
                        : 'Once tickets are sold, charts will appear here.'"
                />
            @endif

            {{-- Revenue tab --}}
            <div x-show="activeSection === 'revenue'"
                x-cloak
                role="tabpanel"
                class="space-y-3">
            <section id="report-revenue" class="report-section p-4">
                <div class="mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Revenue</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Revenue performance</h2>
                    <p class="mt-1 text-sm text-slate-500">Trend, monthly comparison, growth, and refund impact</p>
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-5"
                        @click="openChart('revenueTrend', 'Revenue trend', 'Income over time from confirmed sales on your events')">
                        @php
                            $revenueTrendMeta = $summaryTrends['netRevenue'] ?? ['percent' => 0, 'up' => true, 'label' => 'vs last month'];
                            $revenueTrendUp = (bool) ($revenueTrendMeta['up'] ?? true);
                            $revenueTrendPercent = abs((float) ($revenueTrendMeta['percent'] ?? 0));
                            $revenueTrendArrow = $revenueTrendUp ? '▲' : '▼';
                            $revenueTrendPrefix = $revenueTrendUp ? '+' : '−';
                            $revenueTrendTone = $revenueTrendUp ? 'text-emerald-600' : 'text-rose-600';
                            $revenueTrendBadge = $revenueTrendUp
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                : 'bg-rose-50 text-rose-700 ring-1 ring-rose-100';
                        @endphp
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900">Revenue Trend</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Income over time from confirmed sales</p>

                                <div class="mt-4 flex flex-wrap items-end gap-x-5 gap-y-2">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Total Revenue</p>
                                        <p class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                            LKR {{ number_format($revenue['netRevenue'], 0) }}
                                        </p>
                                    </div>
                                    <div class="pb-1">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-semibold {{ $revenueTrendBadge }}">
                                            <span aria-hidden="true" class="{{ $revenueTrendTone }}">{{ $revenueTrendArrow }}</span>
                                            {{ $revenueTrendPrefix }}{{ number_format($revenueTrendPercent, $revenueTrendPercent == floor($revenueTrendPercent) ? 0 : 1) }}%
                                        </span>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ ucfirst($revenueTrendMeta['label'] ?? 'vs last month') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View revenue trend fullscreen"
                                @click.stop="openChart('revenueTrend', 'Revenue trend', 'Income over time from confirmed sales on your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="overviewRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('monthlyRevenue', 'Monthly revenue', 'Confirmed ticket revenue by month for clearer month-to-month comparison')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Monthly revenue</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Bar comparison across months</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View monthly revenue fullscreen"
                                @click.stop="openChart('monthlyRevenue', 'Monthly revenue', 'Confirmed ticket revenue by month for clearer month-to-month comparison')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="monthlyRevenueBarChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-3"
                        @click="openChart('cumulativeRevenue', 'Cumulative revenue', 'Running total of confirmed sales showing growth over time')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Cumulative revenue</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Growth over time</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View cumulative revenue fullscreen"
                                @click.stop="openChart('cumulativeRevenue', 'Cumulative revenue', 'Running total of confirmed sales showing growth over time')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="cumulativeRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-5"
                        @click="openChart('refundsVsSales', 'Refunds vs confirmed sales', 'Stacked view of confirmed sales and approved refunds by month')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Refunds vs sales</h3>
                                <p class="mt-0.5 text-sm text-slate-500">How much revenue was returned</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 opacity-80 transition group-hover:bg-rose-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View refunds vs sales fullscreen"
                                @click.stop="openChart('refundsVsSales', 'Refunds vs confirmed sales', 'Stacked view of confirmed sales and approved refunds by month')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="refundsVsSalesChart"></canvas>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>
                                Confirmed sales
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span>
                                Refunds
                            </span>
                            <span class="ml-auto font-semibold text-rose-600">
                                LKR {{ number_format($revenue['totalRefunded'], 0) }} refunded
                                @if (($revenue['refundRate'] ?? null) !== null)
                                    · {{ number_format((float) $revenue['refundRate'], 1) }}% of gross
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Deeper refund analytics --}}
                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Refund leakage</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">Where refunds come from</h3>
                        <p class="mt-1 text-sm text-slate-500">Rate, event &amp; category breakdown, and monthly trend</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-xl border border-rose-100 bg-rose-50/50 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-400">Refund rate</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-rose-700">
                                {{ number_format((float) ($refundAnalytics['refundRate'] ?? 0), 1) }}%
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">of gross revenue</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Refunded</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">
                                LKR {{ number_format((float) ($refundAnalytics['totalRefunded'] ?? 0), 0) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Refund tickets</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">
                                {{ number_format((int) ($refundAnalytics['refundCount'] ?? 0)) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Gross in filter</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">
                                LKR {{ number_format((float) ($refundAnalytics['grossRevenue'] ?? 0), 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4"
                            @click="openChart('refundsByEvent', 'Refunds by event', 'Approved refund amounts by event')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">By event</h4>
                                    <p class="mt-0.5 text-xs text-slate-500">Highest leakage first</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"
                                    @click.stop="openChart('refundsByEvent', 'Refunds by event', 'Approved refund amounts by event')">
                                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-4 h-56">
                                <canvas id="refundsByEventChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4"
                            @click="openChart('refundsByCategory', 'Refunds by ticket category', 'Approved refund amounts by ticket type')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">By ticket category</h4>
                                    <p class="mt-0.5 text-xs text-slate-500">Which types get refunded</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"
                                    @click.stop="openChart('refundsByCategory', 'Refunds by ticket category', 'Approved refund amounts by ticket type')">
                                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-4 h-56">
                                <canvas id="refundsByCategoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            </div>

            {{-- Tickets tab --}}
            <div x-show="activeSection === 'tickets'" x-cloak role="tabpanel">
            <section id="report-tickets" class="report-section p-4">
                <div class="mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Tickets</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Ticket sales</h2>
                    <p class="mt-1 text-sm text-slate-500">Volume over time, category mix, conversion, and pre-event sales velocity</p>
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-3"
                        @click="openChart('ticketSalesOverTime', 'Ticket sales over time', 'Confirmed ticket sales by month — spikes often follow promotions')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Sales over time</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Monthly volume · spikes after promotions</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 opacity-80 transition group-hover:bg-blue-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View ticket sales over time fullscreen"
                                @click.stop="openChart('ticketSalesOverTime', 'Ticket sales over time', 'Confirmed ticket sales by month — spikes often follow promotions')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="ticketSalesOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('salesByCategory', 'Ticket sales by category', 'Which ticket types sell best on your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Category mix</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Which ticket types sell best</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View category sales fullscreen"
                                @click.stop="openChart('salesByCategory', 'Ticket sales by category', 'Which ticket types sell best on your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-52">
                            <canvas id="salesByCategoryChart"></canvas>
                        </div>
                        @if (count($salesByCategory) > 0)
                            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                                @foreach ($salesByCategory as $category)
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="font-medium text-slate-700">{{ $category['label'] }}</span>
                                        <span class="tabular-nums text-slate-500">
                                            {{ number_format($category['count']) }}
                                            <span class="ml-1 font-semibold text-slate-800">{{ $category['percentage'] }}%</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-report-empty-state class="mt-4 !min-h-[6rem]" />
                        @endif
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('ticketTypeTrend', 'Ticket type trend', 'How ticket categories like Regular and VIP change month to month')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Type trend</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Regular vs VIP by month</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View ticket type trend fullscreen"
                                @click.stop="openChart('ticketTypeTrend', 'Ticket type trend', 'How ticket categories like Regular and VIP change month to month')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="ticketTypeTrendChart"></canvas>
                        </div>
                        @if (count($ticketTypeTrend) === 0)
                            <x-report-empty-state class="mt-2 !min-h-[5rem]" />
                        @endif
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-3"
                        @click="openChart('conversionFunnel', 'Conversion funnel', 'Views to saves to cart to purchases for your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Conversion funnel</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Views → saves → cart → purchases</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 opacity-80 transition group-hover:bg-cyan-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View conversion funnel fullscreen"
                                @click.stop="openChart('conversionFunnel', 'Conversion funnel', 'Views to saves to cart to purchases for your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-56">
                            <canvas id="conversionFunnelChart"></canvas>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            @foreach ($conversionFunnel as $stage)
                                <span class="rounded-lg border border-slate-200 bg-white px-2 py-1">
                                    <span class="font-semibold text-slate-700">{{ $stage['label'] }}:</span>
                                    {{ number_format($stage['count']) }}
                                    @if ($stage['rate'] !== null)
                                        <span class="text-indigo-600">({{ $stage['rate'] }}%)</span>
                                    @endif
                                </span>
                            @endforeach
                            @if ($overallConversion !== null)
                                <span class="ml-auto font-semibold text-emerald-600">
                                    {{ $overallConversion }}% view → purchase
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                        @click="openChart('salesVelocity', 'Tickets per day before event', 'Confirmed tickets sold each day in the T-30 → T-0 window')">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900">Tickets per day</h3>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    Daily sales velocity · T-30 → T-0
                                </p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 opacity-80 transition group-hover:bg-blue-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View tickets per day fullscreen"
                                @click.stop="openChart('salesVelocity', 'Tickets per day before event', 'Confirmed tickets sold each day in the T-30 → T-0 window')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">In window</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-slate-900">
                                    {{ number_format((int) ($salesVelocity['totalInWindow'] ?? 0)) }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Peak day</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-blue-700">
                                    {{ $salesVelocity['peak']['label'] ?? '—' }}
                                </p>
                                @if (! empty($salesVelocity['peak']))
                                    <p class="text-[11px] text-slate-500">{{ number_format((int) $salesVelocity['peak']['count']) }} tickets</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 h-72">
                            <canvas id="salesVelocityChart"></canvas>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Each bar is tickets sold that many days before the event. A late spike (T-7 → T-0) often means boost ads earlier.
                        </p>
                    </div>

                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                        @click="openChart('salesVelocityCumulative', 'Cumulative tickets before event', 'Running total of confirmed tickets in the T-30 → T-0 window')">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-900">Cumulative tickets</h3>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    Running total · T-30 → T-0
                                </p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View cumulative tickets fullscreen"
                                @click.stop="openChart('salesVelocityCumulative', 'Cumulative tickets before event', 'Running total of confirmed tickets in the T-30 → T-0 window')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Final week</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-emerald-700">
                                    {{ $salesVelocity['finalWeekShare'] !== null ? number_format((float) $salesVelocity['finalWeekShare'], 1).'%' : '—' }}
                                </p>
                                <p class="text-[11px] text-slate-500">T-7 → T-0</p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Early window</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-indigo-700">
                                    {{ $salesVelocity['earlyShare'] !== null ? number_format((float) $salesVelocity['earlyShare'], 1).'%' : '—' }}
                                </p>
                                <p class="text-[11px] text-slate-500">T-30 → T-15</p>
                            </div>
                        </div>

                        <div class="mt-3 h-72">
                            <canvas id="salesVelocityCumulativeChart"></canvas>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            How sales build toward event day. Strong early sales may mean you can ease off paid push.
                        </p>
                    </div>
                    </div>
            </section>
            </div>

            {{-- Events tab --}}
            <div x-show="activeSection === 'events'" x-cloak role="tabpanel" class="space-y-3">
            <section id="report-events" class="space-y-3">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($eventsByStatus as $statusRow)
                        <div @class([
                            'rounded-xl border px-3 py-3',
                            'border-orange-200 bg-orange-50/60' => ($statusRow['key'] ?? '') === 'postponed',
                            'border-slate-200 bg-white' => ($statusRow['key'] ?? '') !== 'postponed',
                        ])>
                            <p class="text-xs font-medium text-slate-500">{{ $statusRow['label'] }} Events</p>
                            <p @class([
                                'mt-0.5 text-xl font-bold tabular-nums',
                                'text-orange-700' => ($statusRow['key'] ?? '') === 'postponed',
                                'text-slate-900' => ($statusRow['key'] ?? '') !== 'postponed',
                            ])>
                                {{ number_format($statusRow['count']) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="report-section p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Events</p>
                            <h2 class="mt-0.5 text-lg font-bold text-slate-900">Event tables live on Performance</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Fill rates and sales by event are on the Performance tab · charts and comparisons stay here
                            </p>
                        </div>
                        <button type="button"
                            @click="$dispatch('organizer-open-performance')"
                            class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 sm:text-sm">
                            <i class="bi bi-speedometer2"></i>
                            Open Performance
                        </button>
                    </div>
                </div>
                {{-- Comparisons --}}
                <div class="report-section p-4">
                    <div class="mb-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Comparison</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">How events stack up</h3>
                        <p class="mt-1 text-sm text-slate-500">Revenue ranking, fill profitability, and when tickets sell</p>
                    </div>

                    {{-- Event vs event picker --}}
                    <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50/40 p-3 sm:p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">Event vs event</h4>
                                <p class="mt-0.5 text-xs text-slate-500">Pick 2–3 events · revenue, fill rate, conversion, rating</p>
                            </div>
                            <p class="text-[11px] font-semibold text-indigo-700" x-text="compareIds.length + ' selected'"></p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($eventComparison as $cmpEvent)
                                <button type="button"
                                    @click="toggleCompare({{ (int) $cmpEvent['id'] }})"
                                    :class="compareIds.includes({{ (int) $cmpEvent['id'] }})
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-200'"
                                    class="btn-smooth inline-flex items-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition">
                                    {{ \Illuminate\Support\Str::limit($cmpEvent['name'], 28) }}
                                </button>
                            @empty
                                <p class="text-sm text-slate-500">No events available to compare.</p>
                            @endforelse
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" x-show="comparedEvents.length">
                            <template x-for="event in comparedEvents" :key="event.id">
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <p class="truncate text-sm font-bold text-slate-900" x-text="event.name"></p>
                                    <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="event.status"></p>
                                    <dl class="mt-3 space-y-2 text-sm">
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-500">Revenue</dt>
                                            <dd class="font-semibold tabular-nums text-emerald-600"
                                                x-text="'LKR ' + Number(event.revenue || 0).toLocaleString()"></dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-500">Fill rate</dt>
                                            <dd class="font-semibold tabular-nums text-slate-800" x-text="(event.fill_rate ?? 0) + '%'"></dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-500">Conversion</dt>
                                            <dd class="font-semibold tabular-nums text-slate-800"
                                                x-text="event.conversion_rate == null ? '—' : (event.conversion_rate + '%')"></dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-500">Rating</dt>
                                            <dd class="font-semibold tabular-nums text-amber-600"
                                                x-text="event.rating == null ? '—' : (event.rating + ' ★')"></dd>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <dt class="text-slate-500">Tickets</dt>
                                            <dd class="font-semibold tabular-nums text-slate-800"
                                                x-text="Number(event.tickets_sold || 0).toLocaleString()"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </template>
                        </div>

                        <div class="report-chart chart-expand-hit group relative mt-4 rounded-xl border border-white bg-white/80 p-4"
                            x-show="comparedEvents.length >= 2"
                            @click="openChart('eventCompareMetrics', 'Event comparison', 'Fill rate, conversion, and rating side by side')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">Metric comparison</h4>
                                    <p class="mt-0.5 text-xs text-slate-500">Fill % · conversion % · rating (×20 for scale)</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                    @click.stop="openChart('eventCompareMetrics', 'Event comparison', 'Fill rate, conversion, and rating side by side')">
                                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-4 h-64">
                                <canvas id="eventCompareMetricsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                            @click="openChart('revenuePerEvent', 'Revenue per event', 'Quick revenue comparison across your events')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Revenue per event</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">Quick comparison across events</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                    title="View fullscreen"
                                    aria-label="View revenue per event fullscreen"
                                    @click.stop="openChart('revenuePerEvent', 'Revenue per event', 'Quick revenue comparison across your events')">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>
                            <div class="mt-3 h-72">
                                <canvas id="revenuePerEventChart"></canvas>
                            </div>
                        </div>

                        <div class="report-chart rounded-xl border border-slate-100 p-3 sm:p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Top 5 events</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">Rank by revenue or tickets</p>
                                </div>
                                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs font-semibold">
                                    <button type="button"
                                        @click="top5Metric = 'revenue'"
                                        :class="top5Metric === 'revenue' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                        class="btn-smooth rounded-md px-2.5 py-1.5">
                                        Revenue
                                    </button>
                                    <button type="button"
                                        @click="top5Metric = 'tickets'"
                                        :class="top5Metric === 'tickets' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                        class="btn-smooth rounded-md px-2.5 py-1.5">
                                        Tickets
                                    </button>
                                </div>
                            </div>
                            <div class="relative mt-3 h-72">
                                <div class="absolute inset-0 chart-expand-hit group transition-opacity"
                                    :class="top5Metric === 'revenue' ? 'z-10 opacity-100' : 'z-0 opacity-0 pointer-events-none'"
                                    @click="openChart('top5Revenue', 'Top 5 events by revenue', 'Highest-earning events ranked by confirmed ticket revenue')">
                                    <button type="button"
                                        class="btn-smooth absolute right-0 top-0 z-10 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        aria-label="View top 5 by revenue fullscreen"
                                        @click.stop="openChart('top5Revenue', 'Top 5 events by revenue', 'Highest-earning events ranked by confirmed ticket revenue')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                    <canvas id="top5EventsRevenueChart"></canvas>
                                </div>
                                <div class="absolute inset-0 chart-expand-hit group transition-opacity"
                                    :class="top5Metric === 'tickets' ? 'z-10 opacity-100' : 'z-0 opacity-0 pointer-events-none'"
                                    @click="openChart('top5Tickets', 'Top 5 events by tickets', 'Highest-selling events ranked by confirmed ticket count')">
                                    <button type="button"
                                        class="btn-smooth absolute right-0 top-0 z-10 flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 opacity-80 transition group-hover:bg-blue-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        aria-label="View top 5 by tickets fullscreen"
                                        @click.stop="openChart('top5Tickets', 'Top 5 events by tickets', 'Highest-selling events ranked by confirmed ticket count')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                    <canvas id="top5EventsTicketsChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                            @click="openChart('revenueFillScatter', 'Fill rate by event', 'How full each event is. Revenue and tickets are in the table below.')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Fill rate by event</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">How full each event is · seats sold vs capacity</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                    title="View fullscreen"
                                    aria-label="View fill rate by event fullscreen"
                                    @click.stop="openChart('revenueFillScatter', 'Fill rate by event', 'How full each event is. Revenue and tickets are in the table below.')">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>
                            <div class="mt-3 h-72">
                                <canvas id="revenueFillScatterChart"></canvas>
                            </div>
                            @if ($revenueFillRows->isNotEmpty())
                                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100" @click.stop>
                                    <table class="min-w-full text-left text-xs">
                                        <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                            <tr>
                                                <th class="px-3 py-2">Event</th>
                                                <th class="px-3 py-2 text-right">Revenue</th>
                                                <th class="px-3 py-2 text-right">Fill</th>
                                                <th class="px-3 py-2 text-right">Tickets</th>
                                                <th class="px-3 py-2">What this means</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($revenueFillRows as $row)
                                                <tr>
                                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $row['name'] }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums text-slate-700">LKR {{ number_format((float) ($row['revenue'] ?? 0), 0) }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((float) ($row['fill_rate'] ?? 0), 1) }}%</td>
                                                    <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((int) ($row['tickets_sold'] ?? 0)) }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $revenueFillInsightClass((string) ($row['insight'] ?? '')) }}">
                                                            {{ $row['insight'] ?? '—' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            <p class="mt-2 text-[11px] text-slate-500">
                                Fill rate uses ticket-category capacity. A high-revenue event with a low fill rate still has many unsold seats.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3 sm:p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Peak sales time</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">When tickets sell most (hour × day)</p>
                                </div>
                                @if (! empty($peakSalesHeatmap['peak']))
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                                        Peak: {{ $peakSalesHeatmap['peak']['day'] }} {{ $peakSalesHeatmap['peak']['hour'] }}
                                        · {{ number_format($peakSalesHeatmap['peak']['count']) }} tickets
                                    </span>
                                @endif
                            </div>

                            @if ((int) ($peakSalesHeatmap['max_sales'] ?? 0) > 0)
                                <div class="mt-4 overflow-x-auto">
                                    <div class="min-w-[36rem]">
                                        <div class="mb-1 grid gap-1" style="grid-template-columns: 3.25rem repeat(7, minmax(0, 1fr));">
                                            <span></span>
                                            @foreach ($peakSalesHeatmap['day_labels'] as $dayLabel)
                                                <span class="text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $dayLabel }}</span>
                                            @endforeach
                                        </div>

                                        <div class="space-y-1">
                                            @foreach ($peakSalesHeatmap['matrix'] as $hour => $dayCounts)
                                                @if ($hour % 2 === 0)
                                                    <div class="grid gap-1" style="grid-template-columns: 3.25rem repeat(7, minmax(0, 1fr));">
                                                        <span class="flex items-center text-[10px] font-medium text-slate-400">{{ sprintf('%02d:00', $hour) }}</span>
                                                        @foreach ($dayCounts as $dayIndex => $count)
                                                            @php
                                                                $intensity = $count > 0 ? $count / $peakHeatmapMax : 0;
                                                                $tone = match (true) {
                                                                    $intensity <= 0 => 'bg-slate-100 text-slate-400',
                                                                    $intensity < 0.25 => 'bg-yellow-300 text-yellow-950',
                                                                    $intensity < 0.5 => 'bg-amber-400 text-amber-950',
                                                                    $intensity < 0.75 => 'bg-orange-500 text-white',
                                                                    default => 'bg-rose-600 text-white ring-1 ring-rose-700/40',
                                                                };
                                                                $dayName = $peakSalesHeatmap['day_labels'][$dayIndex] ?? '';
                                                            @endphp
                                                            <div class="flex h-6 items-center justify-center rounded-sm {{ $tone }} text-[9px] font-bold"
                                                                title="{{ $dayName }} {{ sprintf('%02d:00', $hour) }} · {{ number_format($count) }} tickets">
                                                                @if ($count > 0)
                                                                    {{ $count }}
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3 text-[11px] text-slate-500">
                                    <div class="flex items-center gap-1">
                                        <span>Less</span>
                                        <span class="h-3 w-3 rounded-sm bg-slate-100"></span>
                                        <span class="h-3 w-3 rounded-sm bg-yellow-300"></span>
                                        <span class="h-3 w-3 rounded-sm bg-amber-400"></span>
                                        <span class="h-3 w-3 rounded-sm bg-orange-500"></span>
                                        <span class="h-3 w-3 rounded-sm bg-rose-600"></span>
                                        <span>More</span>
                                    </div>
                                    <span>Even hours shown · max {{ number_format($peakHeatmapMax) }} / slot</span>
                                </div>
                            @else
                                <x-report-empty-state class="mt-4 !min-h-[10rem]" />
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Daily calendar --}}
                <div class="report-section p-4">
                    <div class="mb-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">Timing</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">Daily sales calendar</h3>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $salesHeatmap['month_label'] }} · tickets sold by day</p>
                    </div>

                    <div class="mx-auto max-w-xl">
                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                                <span>{{ $weekday }}</span>
                            @endforeach
                        </div>

                        <div class="mt-2 grid grid-cols-7 gap-1">
                            @for ($i = 0; $i < ($salesHeatmap['start_weekday'] ?? 0); $i++)
                                <div class="aspect-square rounded-md bg-transparent"></div>
                            @endfor

                            @foreach (($salesHeatmap['days'] ?? []) as $day)
                                @php
                                    $intensity = (float) ($day['intensity'] ?? 0);
                                    $tone = match (true) {
                                        $intensity <= 0 => 'bg-slate-100 text-slate-400',
                                        $intensity < 0.25 => 'bg-emerald-100 text-emerald-700',
                                        $intensity < 0.5 => 'bg-emerald-200 text-emerald-800',
                                        $intensity < 0.75 => 'bg-emerald-400 text-white',
                                        default => 'bg-emerald-600 text-white',
                                    };
                                @endphp
                                <div class="group relative aspect-square rounded-md {{ $tone }} transition hover:ring-2 hover:ring-indigo-300"
                                    title="{{ $day['date'] }} · {{ number_format($day['count']) }} tickets · LKR {{ number_format($day['revenue'], 0) }}">
                                    <span class="flex h-full items-center justify-center text-[11px] font-semibold">
                                        {{ $day['day'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 text-[11px] text-slate-500">
                            <div class="flex items-center gap-1">
                                <span>Less</span>
                                <span class="h-3 w-3 rounded-sm bg-slate-100"></span>
                                <span class="h-3 w-3 rounded-sm bg-emerald-100"></span>
                                <span class="h-3 w-3 rounded-sm bg-emerald-200"></span>
                                <span class="h-3 w-3 rounded-sm bg-emerald-400"></span>
                                <span class="h-3 w-3 rounded-sm bg-emerald-600"></span>
                                <span>More</span>
                            </div>
                            <span>Peak day ≤ {{ number_format($heatmapMax) }} tickets</span>
                        </div>
                    </div>
                </div>
            </section>
            </div>

            {{-- Attendance tab --}}
            <div x-show="activeSection === 'attendance'" x-cloak role="tabpanel" class="space-y-3">
            <section id="report-attendance" class="space-y-3">
                <div class="report-section p-4">
                    <div class="mb-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-teal-600">Attendance</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Check-in &amp; attendance</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Attendance rate on completed events · no-shows · when guests arrive relative to start time
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach ([
                            [
                                'label' => 'Attendance rate',
                                'value' => $attendance['attendanceRate'] !== null
                                    ? number_format((float) $attendance['attendanceRate'], 1) . '%'
                                    : '—',
                                'hint' => ((int) ($attendance['eventsFinalized'] ?? 0)) > 0
                                    ? number_format((int) $attendance['eventsFinalized']) . ' completed events'
                                    : 'Based on eligible tickets',
                                'tone' => 'text-teal-700',
                            ],
                            [
                                'label' => 'Checked in',
                                'value' => number_format((int) ($attendance['checkedIn'] ?? 0)),
                                'hint' => number_format((int) ($attendance['ticketsEligible'] ?? 0)) . ' eligible tickets',
                                'tone' => 'text-emerald-700',
                            ],
                            [
                                'label' => 'No-shows',
                                'value' => number_format((int) ($attendance['noShows'] ?? 0)),
                                'hint' => 'Completed events only',
                                'tone' => 'text-rose-700',
                            ],
                            [
                                'label' => 'Awaiting check-in',
                                'value' => number_format((int) ($attendance['awaitingCheckIn'] ?? 0)),
                                'hint' => 'Upcoming & ongoing',
                                'tone' => 'text-amber-700',
                            ],
                        ] as $kpi)
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3 sm:px-4">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $kpi['label'] }}</p>
                                <p class="mt-1 text-xl font-bold tabular-nums {{ $kpi['tone'] }} sm:text-2xl">{{ $kpi['value'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $kpi['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (! empty($attendance['peakTiming']))
                        <p class="mt-4 rounded-xl border border-teal-100 bg-teal-50/70 px-3 py-2.5 text-sm text-teal-900">
                            <span class="font-semibold">Peak check-in window:</span>
                            {{ $attendance['peakTiming']['label'] }}
                            <span class="text-teal-700/80">· {{ number_format((int) $attendance['peakTiming']['count']) }} check-ins</span>
                        </p>
                    @endif
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    <div class="report-section report-chart chart-expand-hit group relative p-4 lg:col-span-2"
                        @click="openChart('attendanceBreakdown', 'Attendance breakdown', 'Checked in, no-shows on completed events, and tickets still awaiting entry')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Attendance mix</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Checked in vs no-shows vs awaiting</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 opacity-80 transition group-hover:bg-teal-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View attendance mix fullscreen"
                                @click.stop="openChart('attendanceBreakdown', 'Attendance breakdown', 'Checked in, no-shows on completed events, and tickets still awaiting entry')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="attendanceBreakdownChart"></canvas>
                        </div>
                    </div>

                    <div class="report-section report-chart chart-expand-hit group relative p-4 lg:col-span-3"
                        @click="openChart('checkInTiming', 'Check-in timing', 'When guests check in relative to each event start time')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Check-in timing</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Relative to event start (−2h → +2h)</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View check-in timing fullscreen"
                                @click.stop="openChart('checkInTiming', 'Check-in timing', 'When guests check in relative to each event start time')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="checkInTimingChart"></canvas>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Events without a scheduled date are excluded from timing. Use the scanner on event day to capture arrivals.
                        </p>
                    </div>
                </div>

                <div class="report-section report-chart chart-expand-hit group relative p-4"
                    @click="openChart('attendanceByEvent', 'Attendance by event', 'Checked in vs no-shows or awaiting check-in for each event')">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Attendance by event</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Checked in stacked against no-shows / awaiting</p>
                        </div>
                        <button type="button"
                            class="btn-smooth flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                            title="View fullscreen"
                            aria-label="View attendance by event fullscreen"
                            @click.stop="openChart('attendanceByEvent', 'Attendance by event', 'Checked in vs no-shows or awaiting check-in for each event')">
                            <i class="bi bi-arrows-fullscreen text-sm"></i>
                        </button>
                    </div>
                    <div class="mt-3 h-80">
                        <canvas id="attendanceByEventChart"></canvas>
                    </div>
                </div>

                <div class="report-section p-4">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-teal-600">Per event</p>
                            <h2 class="mt-0.5 text-lg font-bold text-slate-900">Attendance detail</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                No-shows finalize when an event is marked completed
                                · {{ number_format((int) ($attendance['eventsWithTickets'] ?? 0)) }} events with tickets
                            </p>
                        </div>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input type="search" x-model="attendanceQuery" placeholder="Filter events…"
                                class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-52">
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                        <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Date</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tickets</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Checked in</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">No-shows</th>
                                        <th class="hidden px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">Awaiting</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Rate</th>
                                        <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 lg:table-cell">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($attendance['byEvent'] ?? [] as $row)
                                        <tr class="transition hover:bg-teal-50/40"
                                            x-show="matches(@js($row['name']), attendanceQuery)">
                                            <td class="px-5 py-3.5 text-sm font-semibold text-slate-900">{{ $row['name'] }}</td>
                                            <td class="hidden px-5 py-3.5 text-sm text-slate-500 sm:table-cell">{{ $row['date'] }}</td>
                                            <td class="px-5 py-3.5 text-right text-sm tabular-nums text-slate-700">{{ number_format($row['tickets']) }}</td>
                                            <td class="px-5 py-3.5 text-right text-sm font-semibold tabular-nums text-emerald-600">{{ number_format($row['checked_in']) }}</td>
                                            <td class="px-5 py-3.5 text-right text-sm tabular-nums">
                                                @if ($row['attendance_final'])
                                                    <span class="font-semibold text-rose-600">{{ number_format($row['no_shows']) }}</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="hidden px-5 py-3.5 text-right text-sm tabular-nums md:table-cell">
                                                @if (! $row['attendance_final'])
                                                    <span class="font-semibold text-amber-600">{{ number_format($row['awaiting_check_in']) }}</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span @class([
                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    'bg-emerald-100 text-emerald-700' => $row['attendance_rate'] >= 75,
                                                    'bg-amber-100 text-amber-700' => $row['attendance_rate'] >= 40 && $row['attendance_rate'] < 75,
                                                    'bg-rose-100 text-rose-700' => $row['attendance_rate'] < 40,
                                                ])>
                                                    {{ number_format($row['attendance_rate'], 1) }}%
                                                </span>
                                            </td>
                                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                                <span @class([
                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    'bg-blue-100 text-blue-700' => ($row['status_key'] ?? '') === 'upcoming',
                                                    'bg-emerald-100 text-emerald-700' => ($row['status_key'] ?? '') === 'ongoing',
                                                    'bg-amber-100 text-amber-800' => ($row['status_key'] ?? '') === 'postponed',
                                                    'bg-slate-100 text-slate-600' => ($row['status_key'] ?? '') === 'completed',
                                                    'bg-rose-100 text-rose-700' => ($row['status_key'] ?? '') === 'cancelled',
                                                    'bg-indigo-100 text-indigo-700' => ! in_array(($row['status_key'] ?? ''), ['upcoming', 'ongoing', 'postponed', 'completed', 'cancelled'], true),
                                                ])>
                                                    {{ $row['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-8">
                                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            </div>

            {{-- Engagement tab --}}
            <div x-show="activeSection === 'engagement'" x-cloak role="tabpanel" class="space-y-3">
            <section id="report-engagement" class="report-section p-4">
                <div class="mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Engagement</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Engagement signals</h2>
                    <p class="mt-1 text-sm text-slate-500">Interaction mix, trends over time, and correlation with ticket sales</p>
                </div>

                <div class="grid gap-3 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('engagement', 'Engagement analytics', 'Likes, saves, comments, and ratings on your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Signal breakdown</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Likes, saves, comments, ratings</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 opacity-80 transition group-hover:bg-rose-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement fullscreen"
                                @click.stop="openChart('engagement', 'Engagement analytics', 'Likes, saves, comments, and ratings on your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            @foreach ([
                                ['label' => 'Likes', 'value' => number_format($engagement['totalLikes']), 'tone' => 'bg-rose-50 text-rose-700'],
                                ['label' => 'Saves', 'value' => number_format($engagement['totalSaves'] ?? 0), 'tone' => 'bg-indigo-50 text-indigo-700'],
                                ['label' => 'Comments', 'value' => number_format($engagement['totalComments']), 'tone' => 'bg-blue-50 text-blue-700'],
                                ['label' => 'Ratings', 'value' => number_format($engagement['totalRatings']), 'tone' => 'bg-amber-50 text-amber-700'],
                            ] as $metric)
                                <div class="rounded-xl {{ $metric['tone'] }} px-3 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide opacity-80">{{ $metric['label'] }}</p>
                                    <p class="text-lg font-bold">{{ $metric['value'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 h-44">
                            <canvas id="overviewEngagementBarChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-3"
                        @click="openChart('engagementOverTime', 'Engagement signals over time', 'Monthly likes, saves, comments, and ratings across your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Over time</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Monthly signal trends</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 opacity-80 transition group-hover:bg-rose-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement over time fullscreen"
                                @click.stop="openChart('engagementOverTime', 'Engagement signals over time', 'Monthly likes, saves, comments, and ratings across your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="engagementOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('engagementBeforeEvent', 'Engagement before event day', 'Signals in the 28 days leading up to each event, overlaid with ticket sales')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Before event day</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Does buzz grow as the date approaches?</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement before event day fullscreen"
                                @click.stop="openChart('engagementBeforeEvent', 'Engagement before event day', 'Signals in the 28 days leading up to each event, overlaid with ticket sales')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="engagementBeforeEventChart"></canvas>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            X-axis is days relative to event date (−28 → event day). Rising curves near the right mean engagement builds before showtime.
                        </p>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-3 sm:p-4 lg:col-span-3"
                        @click="openChart('engagementVsSales', 'Tickets vs engagement by event', 'Blue bars are tickets sold. Pink bars are likes + saves + comments + ratings.')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Tickets vs engagement</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Which events turn interest into ticket sales</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View tickets vs engagement fullscreen"
                                @click.stop="openChart('engagementVsSales', 'Tickets vs engagement by event', 'Blue bars are tickets sold. Pink bars are likes + saves + comments + ratings.')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="engagementVsSalesChart"></canvas>
                        </div>
                        @if ($engagementVsSalesRows->isNotEmpty())
                            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100" @click.stop>
                                <table class="min-w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                        <tr>
                                            <th class="px-3 py-2">Event</th>
                                            <th class="px-3 py-2 text-right">Tickets</th>
                                            <th class="px-3 py-2 text-right">Engagement</th>
                                            <th class="px-3 py-2">What this means</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($engagementVsSalesRows as $row)
                                            <tr>
                                                <td class="px-3 py-2 font-medium text-slate-800">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((int) $row['tickets_sold']) }}</td>
                                                <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((int) $row['engagement']) }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $engagementInsightClass((string) ($row['insight'] ?? '')) }}">
                                                        {{ $row['insight'] ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        <p class="mt-2 text-[11px] text-slate-500">
                            Engagement = likes + saves + comments + ratings. High interest with low sales often means the listing is seen but not converting.
                        </p>
                    </div>
                </div>

                {{-- Review quality --}}
                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">Review quality</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">Ratings &amp; review health</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Average score trend, score mix, and low-rated events
                            @if ($reviewQuality['responseRate'] === null)
                                · reply rate available once organizer responses ship
                            @endif
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-xl border border-amber-100 bg-amber-50/50 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-500">Avg rating</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-amber-700">
                                {{ $reviewQuality['averageRating'] !== null ? number_format((float) $reviewQuality['averageRating'], 1).'/5' : '—' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ratings</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900">
                                {{ number_format((int) ($reviewQuality['totalRatings'] ?? 0)) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Low-rated events</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-rose-700">
                                {{ number_format(count($reviewQuality['lowRatedEvents'] ?? [])) }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">&lt; 3.5 ★ · 2+ ratings</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Response rate</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-slate-400">—</p>
                            <p class="mt-0.5 text-xs text-slate-500">Coming with replies</p>
                        </div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-5">
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 lg:col-span-3"
                            @click="openChart('ratingTrend', 'Average rating trend', 'Monthly average star rating across your events')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">Average score trend</h4>
                                    <p class="mt-0.5 text-xs text-slate-500">Monthly avg ★ (bars = rating volume)</p>
                                </div>
                                <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"
                                    @click.stop="openChart('ratingTrend', 'Average rating trend', 'Monthly average star rating across your events')">
                                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-4 h-64">
                                <canvas id="ratingTrendChart"></canvas>
                            </div>
                        </div>
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 lg:col-span-2"
                            @click="openChart('ratingDistribution', 'Rating distribution', 'How many 1–5 star ratings you received')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">Score mix</h4>
                                    <p class="mt-0.5 text-xs text-slate-500">1★ → 5★</p>
                                </div>
                                <button type="button" class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"
                                    @click.stop="openChart('ratingDistribution', 'Rating distribution', 'How many 1–5 star ratings you received')">
                                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                                </button>
                            </div>
                            <div class="mt-4 h-64">
                                <canvas id="ratingDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    @if (count($reviewQuality['lowRatedEvents'] ?? []) > 0)
                        <div class="overflow-hidden rounded-xl border border-rose-100">
                            <div class="border-b border-rose-100 bg-rose-50/60 px-4 py-3">
                                <h4 class="text-sm font-bold text-rose-800">Low-rated events</h4>
                                <p class="text-xs text-rose-700/80">Worth a follow-up — average below 3.5 with at least 2 ratings</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-rose-50">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-slate-500">Event</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-slate-500">Avg</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-slate-500">Ratings</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($reviewQuality['lowRatedEvents'] as $lowEvent)
                                            <tr>
                                                <td class="px-4 py-2.5 text-sm font-semibold text-slate-900">{{ $lowEvent['name'] }}</td>
                                                <td class="px-4 py-2.5 text-right text-sm font-bold text-rose-600">{{ number_format($lowEvent['rating'], 1) }} ★</td>
                                                <td class="px-4 py-2.5 text-right text-sm tabular-nums text-slate-600">{{ number_format($lowEvent['ratings_count']) }}</td>
                                                <td class="px-4 py-2.5 text-sm text-slate-500">{{ $lowEvent['status'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
            </div>

            {{-- Audience tab --}}
            <div x-show="activeSection === 'audience'" x-cloak role="tabpanel">
            <section id="report-audience" class="report-section p-4">
                <div class="mb-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-600">Audience</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Audience insights</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($demographicsAvailable['any'] ?? false)
                            Demographics, loyalty, and top buyers
                        @else
                            Loyalty, top buyers, and purchase patterns
                        @endif
                    </p>
                </div>

                @if ($demographicsAvailable['any'] ?? false)
                    <div class="grid gap-3 lg:grid-cols-3">
                        @if ($demographicsAvailable['age'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                                @click="openChart('demographicsAge', 'Age groups', 'Attendee age distribution from confirmed ticket buyers')">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Age groups</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">From buyer profiles</p>
                                    </div>
                                    <button type="button"
                                        class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        @click.stop="openChart('demographicsAge', 'Age groups', 'Attendee age distribution from confirmed ticket buyers')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-4 h-56">
                                    <canvas id="demographicsAgeChart"></canvas>
                                </div>
                            </div>
                        @endif

                        @if ($demographicsAvailable['gender'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                                @click="openChart('demographicsGender', 'Gender', 'Attendee gender split from confirmed ticket buyers')">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Gender</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">From buyer profiles</p>
                                    </div>
                                    <button type="button"
                                        class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 opacity-80 transition group-hover:bg-rose-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        @click.stop="openChart('demographicsGender', 'Gender', 'Attendee gender split from confirmed ticket buyers')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-4 h-56">
                                    <canvas id="demographicsGenderChart"></canvas>
                                </div>
                            </div>
                        @endif

                        @if ($demographicsAvailable['province'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                                @click="openChart('demographicsProvince', 'Province', 'Audience by Sri Lanka’s 9 provinces, mapped from profile addresses')">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Province</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">Sri Lanka’s 9 provinces (from profile address)</p>
                                    </div>
                                    <button type="button"
                                        class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600 opacity-80 transition group-hover:bg-violet-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        @click.stop="openChart('demographicsProvince', 'Province', 'Audience by Sri Lanka’s 9 provinces, mapped from profile addresses')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-4 h-56">
                                    <canvas id="demographicsProvinceChart"></canvas>
                                </div>
                            </div>
                        @endif

                        @if ($demographicsAvailable['location'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4 lg:col-span-3"
                                @click="openChart('demographicsLocation', 'Location', 'Audience by Sri Lanka’s 25 districts, mapped from profile addresses')">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Location</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">Sri Lanka’s 25 districts (from profile address)</p>
                                    </div>
                                    <button type="button"
                                        class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 opacity-80 transition group-hover:bg-cyan-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        @click.stop="openChart('demographicsLocation', 'Location', 'Audience by Sri Lanka’s 25 districts, mapped from profile addresses')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-4 h-[40rem]">
                                    <canvas id="demographicsLocationChart"></canvas>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-dashed border-indigo-200 bg-indigo-50/30 p-3 sm:p-4"
                        @click="openChart('audienceEngagementVsSales', 'Tickets vs engagement by event', 'Blue bars are tickets sold. Pink bars are likes + saves + comments + ratings.')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Fallback insight</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">Tickets vs engagement</h3>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    Demographics are limited — compare tickets sold with likes, saves, comments, and ratings.
                                </p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View tickets vs engagement fullscreen"
                                @click.stop="openChart('audienceEngagementVsSales', 'Tickets vs engagement by event', 'Blue bars are tickets sold. Pink bars are likes + saves + comments + ratings.')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-72">
                            <canvas id="audienceEngagementVsSalesChart"></canvas>
                        </div>
                        @if ($engagementVsSalesRows->isNotEmpty())
                            <div class="mt-4 overflow-x-auto rounded-xl border border-white/80 bg-white/80" @click.stop>
                                <table class="min-w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                        <tr>
                                            <th class="px-3 py-2">Event</th>
                                            <th class="px-3 py-2 text-right">Tickets</th>
                                            <th class="px-3 py-2 text-right">Engagement</th>
                                            <th class="px-3 py-2">What this means</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($engagementVsSalesRows as $row)
                                            <tr>
                                                <td class="px-3 py-2 font-medium text-slate-800">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((int) $row['tickets_sold']) }}</td>
                                                <td class="px-3 py-2 text-right tabular-nums text-slate-700">{{ number_format((int) $row['engagement']) }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $engagementInsightClass((string) ($row['insight'] ?? '')) }}">
                                                        {{ $row['insight'] ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        <p class="mt-2 text-[11px] text-slate-500">
                            Complete age, gender, and address on attendee profiles for richer demographics. Until then, this shows which events convert interest into sales.
                        </p>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4 lg:col-span-2"
                        @click="openChart('repeatVsNew', 'Repeat vs new attendees', 'Loyalty split of unique confirmed buyers')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Repeat vs new</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Customer loyalty split</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                @click.stop="openChart('repeatVsNew', 'Repeat vs new attendees', 'Loyalty split of unique confirmed buyers')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-3 h-64">
                            <canvas id="repeatVsNewChart"></canvas>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-indigo-500"></span>
                                New: {{ number_format($attendees['newAttendees'] ?? 0) }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>
                                Repeat: {{ number_format($attendees['repeatAttendees'] ?? 0) }}
                            </span>
                            <span class="ml-auto font-semibold text-slate-600">
                                {{ number_format($attendees['returningRate'] ?? 0, 1) }}% returning
                            </span>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-100 bg-white lg:col-span-3">
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <i class="bi bi-trophy-fill"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Top customers</h3>
                                    <p class="text-sm text-slate-500">Who buys the most tickets</p>
                                </div>
                            </div>
                            <div class="relative">
                                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                <input type="search" x-model="customerQuery" placeholder="Filter buyers…"
                                    class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-48">
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @forelse ($topCustomers as $index => $customer)
                                <div class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-indigo-50/35"
                                    x-show="matches(@js($customer['name'] . ' ' . $customer['email']), customerQuery)">
                                    <span @class([
                                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold shadow-sm',
                                        'bg-amber-400 text-amber-950' => $index === 0,
                                        'bg-slate-300 text-slate-800' => $index === 1,
                                        'bg-orange-300 text-orange-950' => $index === 2,
                                        'bg-slate-100 text-slate-600' => $index > 2,
                                    ])>
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $customer['name'] }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ $customer['email'] }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-bold tabular-nums text-slate-900">{{ number_format($customer['tickets']) }} tickets</p>
                                        <p class="text-xs font-semibold tabular-nums text-emerald-600">LKR {{ number_format($customer['spend'], 0) }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $customer['last_purchase'] ?? '' }}</p>
                                    </div>
                                </div>
                            @empty
                                <x-report-empty-state class="!min-h-[8rem] m-4 border-0 bg-transparent shadow-none" />
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-3 sm:p-4"
                        @click="openChart('attendeesByEvent', 'Attendee insights', 'Attendees by event for your listings')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Attendee insights</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Customer behavior for your events</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View attendee chart fullscreen"
                                @click.stop="openChart('attendeesByEvent', 'Attendee insights', 'Attendees by event for your listings')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ([
                                [
                                    'label' => 'Unique attendees',
                                    'value' => number_format($attendees['totalAttendees']),
                                    'hint' => number_format($attendees['confirmedBookings']) . ' confirmed',
                                ],
                                [
                                    'label' => 'Confirmation rate',
                                    'value' => ($attendees['confirmationRate'] ?? 0) . '%',
                                    'hint' => number_format($attendees['totalBookings']) . ' total bookings',
                                ],
                                [
                                    'label' => 'Returning buyers',
                                    'value' => number_format($attendees['repeatBuyers'] ?? 0),
                                    'hint' => ($attendees['returningRate'] ?? 0) . '% of attendees',
                                ],
                                [
                                    'label' => 'Avg spend / guest',
                                    'value' => 'LKR ' . number_format($attendees['avgSpendPerAttendee'] ?? 0, 0),
                                    'hint' => ($attendees['avgTicketsPerAttendee'] ?? 0) . ' tickets avg',
                                ],
                            ] as $insight)
                                <div class="rounded-xl border border-slate-100 bg-white p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $insight['label'] }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $insight['value'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $insight['hint'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 h-44">
                            <canvas id="overviewAttendeesByEventChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>
            </div>

            {{-- Activity tab --}}
            <div x-show="activeSection === 'activity'" x-cloak role="tabpanel">
            <section id="report-activity" class="report-section overflow-hidden p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Activity</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Sales activity</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Recent purchases are on the Performance tab · full history is in Sales
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="#performance"
                            @click.prevent="window.dispatchEvent(new CustomEvent('organizer-open-performance'))"
                            class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 sm:text-sm">
                            <i class="bi bi-speedometer2"></i>
                            Performance
                        </a>
                        <a href="{{ route('organizer.sales.index', $salesDeepLink) }}"
                            class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 sm:text-sm">
                            <i class="bi bi-receipt"></i>
                            Sales detail
                        </a>
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

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-indigo-500/20"
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
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full rounded-2xl border border-slate-100 bg-slate-50/50 p-3">
                        <canvas id="organizerReportChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400 sm:px-6">
                    Press <kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-semibold text-slate-600">Esc</kbd> to close
                </div>
            </div>
        </div>
    </div>


<x-app-layout>
    @php
        $sales = $reports['ticketSales'];
        $revenue = $reports['revenue'];
        $attendees = $reports['attendees'];
        $engagement = $reports['engagement'];
        $salesByCategory = $reports['salesByCategory'] ?? [];
        $eventPerformance = $reports['eventPerformance'] ?? [];
        $recentTransactions = $reports['recentTransactions'] ?? [];
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
            || count($recentTransactions) > 0;
        $ticketTypeTrend = $reports['ticketTypeTrend'] ?? [];
        $conversionFunnel = $reports['conversionFunnel'] ?? [];
        $funnelViews = (int) ($conversionFunnel[0]['count'] ?? 0);
        $funnelPurchases = (int) ($conversionFunnel[2]['count'] ?? 0);
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

        $filterQueryBase = array_filter([
            'event_id' => $activeFilters['event_id'] ?? null,
            'status' => $activeFilters['status'] ?? null,
        ], fn ($value) => filled($value));

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
                'key' => 'year',
                'label' => 'This Year',
                'from' => now()->startOfYear()->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];

        $activePreset = collect($datePresets)->first(
            fn ($preset) => ($activeFilters['from'] ?? null) === $preset['from']
                && ($activeFilters['to'] ?? null) === $preset['to']
        );
        $activePresetKey = $activePreset['key'] ?? null;

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

        $demographics = $attendees['demographics'] ?? ['age' => [], 'gender' => [], 'location' => [], 'available' => ['age' => false, 'gender' => false, 'location' => false, 'any' => false]];
        $demographicsAvailable = $demographics['available'] ?? [
            'age' => false,
            'gender' => false,
            'location' => false,
            'any' => false,
        ];
        $topCustomers = $attendees['topCustomers'] ?? [];

        $navSections = [
            ['id' => 'revenue', 'label' => 'Revenue', 'icon' => 'bi-cash-stack'],
            ['id' => 'tickets', 'label' => 'Tickets', 'icon' => 'bi-ticket-perforated'],
            ['id' => 'events', 'label' => 'Events', 'icon' => 'bi-calendar-event'],
            ['id' => 'audience', 'label' => 'Audience', 'icon' => 'bi-people'],
            ['id' => 'engagement', 'label' => 'Engagement', 'icon' => 'bi-heart'],
            ['id' => 'activity', 'label' => 'Activity', 'icon' => 'bi-activity'],
        ];
    @endphp

    <div class="py-5 sm:py-6"
        x-data="{
            performanceQuery: '',
            transactionQuery: '',
            customerQuery: '',
            activeSection: 'revenue',
            top5Metric: 'revenue',
            open: false,
            chartKey: null,
            title: '',
            description: '',
            setTab(id) {
                this.activeSection = id;
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
                        detail: { tab: id },
                    }));
                });
            },
            init() {
                const allowed = ['revenue', 'tickets', 'events', 'audience', 'engagement', 'activity'];
                const hash = window.location.hash.replace(/^#/, '');
                if (allowed.includes(hash)) {
                    this.activeSection = hash;
                }
                this.$watch('activeSection', (id) => {
                    if (allowed.includes(id)) {
                        history.replaceState(null, '', '#' + id);
                    }
                });
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', {
                        detail: { tab: this.activeSection },
                    }));
                });
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
        @keydown.escape.window="if (open) closeChart()">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Control panel: title, filters, exports --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-indigo-50/50 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Analytics</p>
                            <h1 class="mt-0.5 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Organizer Reports</h1>
                            <p class="mt-1 text-sm text-slate-500">Performance insights for your events · click charts for fullscreen</p>
                        </div>
                        <div class="flex flex-col items-stretch gap-2 sm:items-end">
                            <p class="inline-flex items-center justify-end gap-1.5 text-xs font-medium text-slate-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                Updated {{ now()->diffForHumans() }}
                            </p>
                            <div class="flex flex-col items-stretch gap-1.5 sm:items-end">
                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <a href="{{ route('dashboard') }}"
                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 sm:text-sm">
                                        <i class="bi bi-speedometer2"></i>
                                        Dashboard
                                    </a>
                                    <x-report-export-buttons
                                        excel-route="organizer.reports.export.excel"
                                        pdf-route="organizer.reports.export.pdf"
                                        section="full"
                                        :filters="$exportFilters"
                                        filter-form-id="organizer-reports-filters"
                                        class="!gap-2"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-500 sm:text-right">
                                    @if ($hasActiveFilters)
                                        Excel exports the <span class="font-semibold text-slate-700">filtered</span> report (event, dates, status).
                                    @else
                                        Excel exports <span class="font-semibold text-slate-700">all</span> report data (no filters applied).
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date range</p>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($datePresets as $preset)
                                <a href="{{ route('organizer.reports', array_merge($filterQueryBase, ['from' => $preset['from'], 'to' => $preset['to']])) }}"
                                    class="btn-smooth inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition
                                        {{ $activePresetKey === $preset['key']
                                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700' }}">
                                    {{ $preset['label'] }}
                                </a>
                            @endforeach
                            @if ($hasActiveFilters)
                                <a href="{{ route('organizer.reports') }}"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Clear all</a>
                            @endif
                        </div>
                    </div>

                    <form id="organizer-reports-filters" method="GET" action="{{ route('organizer.reports') }}" class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                        <div>
                            <label for="from" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">From</label>
                            <input type="date" id="from" name="from" value="{{ $activeFilters['from'] }}"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="to" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">To</label>
                            <input type="date" id="to" name="to" value="{{ $activeFilters['to'] }}"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                            <label for="event_id" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Event</label>
                            <select id="event_id" name="event_id"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All events</option>
                                @foreach ($filterOptions['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}" @selected((string) ($activeFilters['event_id'] ?? '') === (string) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">Status</label>
                            <select id="status" name="status"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                @foreach ($filterOptions['statuses'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected(($activeFilters['status'] ?? '') === $statusOption)>
                                        {{ ucfirst($statusOption) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                <i class="bi bi-funnel"></i>
                                Apply
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            {{-- KPI strip --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    [
                        'label' => 'Net revenue',
                        'value' => 'LKR ' . number_format($revenue['netRevenue'], 0),
                        'hint' => 'After refunds',
                        'trend' => $summaryTrends['netRevenue'],
                        'icon' => 'bi-cash-stack',
                        'top' => 'border-t-emerald-500',
                        'left' => 'border-l-emerald-500',
                        'cardBg' => 'bg-emerald-50/40',
                        'iconBg' => 'bg-emerald-100/70',
                        'iconText' => 'text-emerald-600',
                        'tab' => 'revenue',
                    ],
                    [
                        'label' => 'Tickets sold',
                        'value' => number_format($sales['totalTicketsSold']),
                        'hint' => $avgTicketsPerEvent . ' avg / event',
                        'trend' => $summaryTrends['ticketsSold'],
                        'icon' => 'bi-ticket-perforated',
                        'top' => 'border-t-blue-500',
                        'left' => 'border-l-blue-500',
                        'cardBg' => 'bg-blue-50/40',
                        'iconBg' => 'bg-blue-100/70',
                        'iconText' => 'text-blue-600',
                        'tab' => 'tickets',
                    ],
                    [
                        'label' => 'Your events',
                        'value' => number_format($sales['totalEvents']),
                        'hint' => number_format($sales['eventsWithSales']) . ' with sales',
                        'trend' => $summaryTrends['events'],
                        'icon' => 'bi-calendar-event',
                        'top' => 'border-t-indigo-500',
                        'left' => 'border-l-indigo-500',
                        'cardBg' => 'bg-indigo-50/40',
                        'iconBg' => 'bg-indigo-100/70',
                        'iconText' => 'text-indigo-600',
                        'tab' => 'events',
                    ],
                    [
                        'label' => 'Attendees',
                        'value' => number_format($attendees['totalAttendees']),
                        'hint' => ($attendees['confirmationRate'] ?? 0) . '% confirmed',
                        'trend' => $summaryTrends['attendees'],
                        'icon' => 'bi-people',
                        'top' => 'border-t-cyan-500',
                        'left' => 'border-l-cyan-500',
                        'cardBg' => 'bg-cyan-50/40',
                        'iconBg' => 'bg-cyan-100/70',
                        'iconText' => 'text-cyan-600',
                        'tab' => 'audience',
                    ],
                    [
                        'label' => 'Engagement',
                        'value' => $engagement['averageRating'] ? $engagement['averageRating'] . '/5' : '—',
                        'hint' => number_format($engagement['totalLikes']) . ' likes · ' . number_format($engagement['totalSaves'] ?? 0) . ' saves',
                        'trend' => $summaryTrends['engagement'],
                        'icon' => 'bi-heart',
                        'top' => 'border-t-rose-500',
                        'left' => 'border-l-rose-500',
                        'cardBg' => 'bg-rose-50/40',
                        'iconBg' => 'bg-rose-100/70',
                        'iconText' => 'text-rose-600',
                        'tab' => 'engagement',
                    ],
                ] as $kpi)
                    @php
                        $trend = $kpi['trend'];
                        $trendUp = (bool) ($trend['up'] ?? true);
                        $trendPercent = (float) ($trend['percent'] ?? 0);
                        $trendClass = $trendUp ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
                        $trendIcon = $trendUp ? 'bi-arrow-up-short' : 'bi-arrow-down-short';
                        $trendPrefix = $trendUp ? '+' : '−';
                    @endphp
                    <button type="button"
                        @click="setTab('{{ $kpi['tab'] }}')"
                        class="kpi-lift rounded-xl border border-slate-200/80 border-t-[3px] {{ $kpi['top'] }} border-l-[3px] {{ $kpi['left'] }} {{ $kpi['cardBg'] }} bg-white px-4 py-3.5 text-left shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-slate-500">{{ $kpi['label'] }}</p>
                                <p class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md {{ $trendClass }} px-1.5 py-0.5 text-[11px] font-semibold">
                                        <i class="bi {{ $trendIcon }} text-sm leading-none"></i>
                                        {{ $trendPrefix }}{{ number_format($trendPercent, 1) }}%
                                    </span>
                                    <span class="text-[11px] text-slate-400">{{ $trend['label'] ?? 'vs last month' }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $kpi['hint'] }}</p>
                            </div>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kpi['iconBg'] }} {{ $kpi['iconText'] }}">
                                <i class="bi {{ $kpi['icon'] }}"></i>
                            </span>
                        </div>
                    </button>
                @endforeach
            </section>

            {{-- Tabbed section nav --}}
            <nav class="report-nav sticky top-16 z-30 sm:top-20"
                aria-label="Report sections"
                role="tablist">
                <div class="flex gap-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($navSections as $nav)
                        <button type="button"
                            role="tab"
                            :aria-selected="activeSection === '{{ $nav['id'] }}'"
                            @click="setTab('{{ $nav['id'] }}')"
                            :class="activeSection === '{{ $nav['id'] }}' ? 'is-active' : ''"
                            class="report-nav-pill inline-flex items-center gap-1.5 text-slate-700">
                            <i class="bi {{ $nav['icon'] }} text-[11px] opacity-80"></i>
                            {{ $nav['label'] }}
                        </button>
                    @endforeach
                </div>
            </nav>

            @if (! $hasReportData)
                <x-report-empty-state
                    class="!min-h-[10rem] border-slate-200 bg-white shadow-sm"
                    :hint="$hasActiveFilters
                        ? 'Try another date range or event.'
                        : 'Once tickets are sold, insights will appear here.'"
                />
            @endif

            {{-- Revenue tab --}}
            <div x-show="activeSection === 'revenue'"
                x-cloak
                role="tabpanel"
                class="space-y-5">
            <section id="report-revenue" class="report-section p-5 sm:p-6">
                <div class="mb-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Revenue</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Revenue performance</h2>
                    <p class="mt-1 text-sm text-slate-500">Trend, monthly comparison, growth, and refund impact</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-5"
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
                                        <p class="mt-1 text-xs text-slate-500">Compared to Previous Month</p>
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
                        <div class="mt-5 h-72">
                            <canvas id="overviewRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
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
                        <div class="mt-5 h-64">
                            <canvas id="monthlyRevenueBarChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
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
                        <div class="mt-5 h-64">
                            <canvas id="cumulativeRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-5"
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
                        <div class="mt-5 h-64">
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
                            </span>
                        </div>
                    </div>
                </div>
            </section>
            </div>

            {{-- Tickets tab --}}
            <div x-show="activeSection === 'tickets'" x-cloak role="tabpanel">
            <section id="report-tickets" class="report-section p-5 sm:p-6">
                <div class="mb-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Tickets</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Ticket sales</h2>
                    <p class="mt-1 text-sm text-slate-500">Volume over time, category mix, type trends, and conversion</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
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
                        <div class="mt-5 h-72">
                            <canvas id="ticketSalesOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
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
                        <div class="mt-5 h-52">
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
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
                        <div class="mt-5 h-64">
                            <canvas id="ticketTypeTrendChart"></canvas>
                        </div>
                        @if (count($ticketTypeTrend) === 0)
                            <x-report-empty-state class="mt-2 !min-h-[5rem]" />
                        @endif
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
                        @click="openChart('conversionFunnel', 'Conversion funnel', 'Views to saves to purchases for your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Conversion funnel</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Views → saves → purchases</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 opacity-80 transition group-hover:bg-cyan-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View conversion funnel fullscreen"
                                @click.stop="openChart('conversionFunnel', 'Conversion funnel', 'Views to saves to purchases for your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-56">
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
            </section>
            </div>

            {{-- Events tab --}}
            <div x-show="activeSection === 'events'" x-cloak role="tabpanel" class="space-y-5">
            <section id="report-events" class="space-y-5">
                {{-- Performance table --}}
                <div class="report-section p-5 sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Events</p>
                            <h2 class="mt-0.5 text-lg font-bold text-slate-900">Event performance</h2>
                            <p class="mt-1 text-sm text-slate-500">Tickets, revenue, fill rate, rating, and status</p>
                        </div>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input type="search" x-model="performanceQuery" placeholder="Filter events…"
                                class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-52">
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Tickets Sold</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Revenue</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Fill Rate</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Rating</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($eventPerformance as $event)
                                        <tr class="transition hover:bg-indigo-50/40"
                                            x-show="matches(@js($event['name']), performanceQuery)">
                                            <td class="px-5 py-3.5 text-sm font-semibold text-slate-900">{{ $event['name'] }}</td>
                                            <td class="px-5 py-3.5 text-right text-sm tabular-nums text-slate-700">{{ number_format($event['tickets_sold']) }}</td>
                                            <td class="px-5 py-3.5 text-right text-sm font-bold tabular-nums text-emerald-600">LKR {{ number_format($event['revenue'], 2) }}</td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span @class([
                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    'bg-emerald-100 text-emerald-700' => $event['fill_rate'] >= 75,
                                                    'bg-amber-100 text-amber-700' => $event['fill_rate'] >= 25 && $event['fill_rate'] < 75,
                                                    'bg-slate-100 text-slate-600' => $event['fill_rate'] < 25,
                                                ])>
                                                    {{ $event['fill_rate'] }}%
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 text-right text-sm font-semibold text-amber-600">
                                                @if ($event['rating'])
                                                    {{ number_format($event['rating'], 1) }} ★
                                                @else
                                                    <span class="font-medium text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span @class([
                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    'bg-blue-100 text-blue-700' => ($event['status_key'] ?? '') === 'upcoming',
                                                    'bg-emerald-100 text-emerald-700' => ($event['status_key'] ?? '') === 'ongoing',
                                                    'bg-slate-100 text-slate-600' => ($event['status_key'] ?? '') === 'completed',
                                                    'bg-rose-100 text-rose-700' => ($event['status_key'] ?? '') === 'cancelled',
                                                    'bg-indigo-100 text-indigo-700' => ! in_array(($event['status_key'] ?? ''), ['upcoming', 'ongoing', 'completed', 'cancelled'], true),
                                                ])>
                                                    {{ $event['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-8">
                                                <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Comparisons --}}
                <div class="report-section p-5 sm:p-6">
                    <div class="mb-5">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Comparison</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">How events stack up</h3>
                        <p class="mt-1 text-sm text-slate-500">Revenue ranking, fill profitability, and when tickets sell</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
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
                            <div class="mt-5 h-72">
                                <canvas id="revenuePerEventChart"></canvas>
                            </div>
                        </div>

                        <div class="report-chart rounded-xl border border-slate-100 p-4 sm:p-5">
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
                            <div class="relative mt-5 h-72">
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

                        <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
                            @click="openChart('revenueFillScatter', 'Revenue vs fill rate', 'Events that are both profitable and well attended sit toward the top-right')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Revenue vs fill rate</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">Profitable and well-attended events</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 opacity-80 transition group-hover:bg-indigo-100 group-hover:opacity-100"
                                    title="View fullscreen"
                                    aria-label="View revenue vs fill rate fullscreen"
                                    @click.stop="openChart('revenueFillScatter', 'Revenue vs fill rate', 'Events that are both profitable and well attended sit toward the top-right')">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>
                            <div class="mt-5 h-72">
                                <canvas id="revenueFillScatterChart"></canvas>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">
                                Top-right = high revenue + high fill. Bottom-left = low on both.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-4 sm:p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Peak sales time</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">When tickets sell most (hour × day)</p>
                                </div>
                                @if (! empty($peakSalesHeatmap['peak']))
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
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
                                                                    $intensity < 0.25 => 'bg-indigo-100 text-indigo-700',
                                                                    $intensity < 0.5 => 'bg-indigo-200 text-indigo-800',
                                                                    $intensity < 0.75 => 'bg-indigo-400 text-white',
                                                                    default => 'bg-indigo-600 text-white',
                                                                };
                                                                $dayName = $peakSalesHeatmap['day_labels'][$dayIndex] ?? '';
                                                            @endphp
                                                            <div class="flex h-5 items-center justify-center rounded-sm {{ $tone }} text-[9px] font-semibold"
                                                                title="{{ $dayName }} {{ sprintf('%02d:00', $hour) }} · {{ number_format($count) }} tickets">
                                                                @if ($count > 0 && $intensity >= 0.5)
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
                                        <span class="h-3 w-3 rounded-sm bg-indigo-100"></span>
                                        <span class="h-3 w-3 rounded-sm bg-indigo-200"></span>
                                        <span class="h-3 w-3 rounded-sm bg-indigo-400"></span>
                                        <span class="h-3 w-3 rounded-sm bg-indigo-600"></span>
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
                <div class="report-section p-5 sm:p-6">
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

            {{-- Engagement tab --}}
            <div x-show="activeSection === 'engagement'" x-cloak role="tabpanel" class="space-y-5">
            <section id="report-engagement" class="report-section p-5 sm:p-6">
                <div class="mb-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Engagement</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Engagement signals</h2>
                    <p class="mt-1 text-sm text-slate-500">Interaction mix, trends over time, and correlation with ticket sales</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
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
                        <div class="mt-5 h-72">
                            <canvas id="engagementOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
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
                        <div class="mt-5 h-72">
                            <canvas id="engagementBeforeEventChart"></canvas>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            X-axis is days relative to event date (−28 → event day). Rising curves near the right mean engagement builds before showtime.
                        </p>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
                        @click="openChart('engagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Engagement vs ticket sales</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Do likes and comments drive purchases?</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement vs ticket sales fullscreen"
                                @click.stop="openChart('engagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-72">
                            <canvas id="engagementVsSalesChart"></canvas>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            Engagement score = likes + saves + comments + ratings. Top-right = high interaction and high sales.
                        </p>
                    </div>
                </div>
            </section>
            </div>

            {{-- Audience tab --}}
            <div x-show="activeSection === 'audience'" x-cloak role="tabpanel">
            <section id="report-audience" class="report-section p-5 sm:p-6">
                <div class="mb-5">
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
                    <div class="grid gap-4 lg:grid-cols-3">
                        @if ($demographicsAvailable['age'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
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
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
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

                        @if ($demographicsAvailable['location'] ?? false)
                            <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
                                @click="openChart('demographicsLocation', 'Location', 'Attendee locations parsed from profile addresses')">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900">Location</h3>
                                        <p class="mt-0.5 text-sm text-slate-500">From profile address</p>
                                    </div>
                                    <button type="button"
                                        class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 opacity-80 transition group-hover:bg-cyan-100 group-hover:opacity-100"
                                        title="View fullscreen"
                                        @click.stop="openChart('demographicsLocation', 'Location', 'Attendee locations parsed from profile addresses')">
                                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                                    </button>
                                </div>
                                <div class="mt-4 h-56">
                                    <canvas id="demographicsLocationChart"></canvas>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-dashed border-indigo-200 bg-indigo-50/30 p-4 sm:p-5"
                        @click="openChart('audienceEngagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Fallback insight</p>
                                <h3 class="mt-0.5 text-base font-bold text-slate-900">Engagement vs ticket sales</h3>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    Demographics are limited — this correlation shows whether engagement drives purchases.
                                </p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement vs ticket sales fullscreen"
                                @click.stop="openChart('audienceEngagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-80">
                            <canvas id="audienceEngagementVsSalesChart"></canvas>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            Top-right events have strong engagement and strong ticket sales. Encourage attendees to complete age, gender, and address on their profiles for richer demographics.
                        </p>
                    </div>
                @endif

                <div class="mt-4 grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5 lg:col-span-2"
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
                        <div class="mt-5 h-64">
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
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
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
                    <div class="report-chart chart-expand-hit group relative rounded-xl border border-slate-100 p-4 sm:p-5"
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
            <section id="report-activity" class="report-section overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Activity</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Recent transactions</h2>
                        <p class="mt-1 text-sm text-slate-500">Latest ticket purchases on your events</p>
                    </div>
                    <div class="relative">
                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search" x-model="transactionQuery" placeholder="Filter…"
                            class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 sm:table-cell">Category</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 md:table-cell">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($recentTransactions as $tx)
                                <tr class="transition hover:bg-indigo-50/35"
                                    x-show="matches(@js($tx['customer'] . ' ' . $tx['event'] . ' ' . $tx['category']), transactionQuery)">
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-semibold text-slate-900">{{ $tx['customer'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $tx['email'] }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm text-slate-700">{{ $tx['event'] }}</td>
                                    <td class="hidden px-5 py-3.5 text-sm text-slate-500 sm:table-cell">{{ $tx['category'] }}</td>
                                    <td class="px-5 py-3.5 text-right text-sm font-bold tabular-nums text-emerald-600">
                                        LKR {{ number_format($tx['amount'], 2) }}
                                    </td>
                                    <td class="hidden px-5 py-3.5 md:table-cell">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => in_array($tx['status_key'], ['confirmed', 'completed'], true),
                                            'bg-amber-100 text-amber-700' => $tx['status_key'] === 'pending',
                                            'bg-rose-100 text-rose-700' => in_array($tx['status_key'], ['cancelled', 'refunded', 'booking_cancelled', 'event_cancelled', 'failed'], true),
                                            'bg-slate-100 text-slate-600' => ! in_array($tx['status_key'], ['confirmed', 'completed', 'pending', 'cancelled', 'refunded', 'booking_cancelled', 'event_cancelled', 'failed'], true),
                                        ])>
                                            {{ $tx['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-xs text-slate-400">{{ $tx['relative'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8">
                                        <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </div>

        {{-- Fullscreen chart modal --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-indigo-500/20"
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

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.organizerReportData = @json($reports);
        </script>
        @vite('resources/js/organizer-reports.js')
    @endpush
</x-app-layout>

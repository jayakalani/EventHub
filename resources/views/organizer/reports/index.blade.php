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
        $topPerformingEvents = collect($eventPerformance)
            ->sortByDesc(fn ($event) => ((float) $event['revenue']) + ((int) $event['tickets_sold'] * 100) + (((float) ($event['fill_rate'] ?? 0)) * 10))
            ->take(5)
            ->values()
            ->all();
        $topPerformerMaxRevenue = max(1, (float) collect($topPerformingEvents)->max('revenue'));

        $ticketTypeTrend = $reports['ticketTypeTrend'] ?? [];
        $conversionFunnel = $reports['conversionFunnel'] ?? [];
        $funnelViews = (int) ($conversionFunnel[0]['count'] ?? 0);
        $funnelPurchases = (int) ($conversionFunnel[2]['count'] ?? 0);
        $overallConversion = $funnelViews > 0
            ? round(($funnelPurchases / $funnelViews) * 100, 1)
            : null;

        $salesHeatmap = $reports['salesHeatmap'] ?? [
            'month_label' => now()->format('F Y'),
            'start_weekday' => 0,
            'max_sales' => 1,
            'days' => [],
        ];
        $heatmapMax = max(1, (int) ($salesHeatmap['max_sales'] ?? 1));

        $demographics = $attendees['demographics'] ?? ['age' => [], 'gender' => [], 'location' => []];
        $topCustomers = $attendees['topCustomers'] ?? [];

        $navSections = [
            ['id' => 'overview', 'label' => 'Overview'],
            ['id' => 'revenue', 'label' => 'Revenue'],
            ['id' => 'tickets', 'label' => 'Tickets'],
            ['id' => 'events', 'label' => 'Events'],
            ['id' => 'engagement', 'label' => 'Engagement'],
            ['id' => 'audience', 'label' => 'Audience'],
            ['id' => 'activity', 'label' => 'Activity'],
        ];
    @endphp

    <div class="py-5 sm:py-6"
        x-data="{
            performanceQuery: '',
            transactionQuery: '',
            customerQuery: '',
            activeSection: 'overview',
            open: false,
            chartKey: null,
            title: '',
            description: '',
            goSection(id) {
                this.activeSection = id;
                document.getElementById('report-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },
            init() {
                const ids = ['overview', 'revenue', 'tickets', 'events', 'engagement', 'audience', 'activity'];
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (!visible?.target?.id) return;
                    const id = visible.target.id.replace(/^report-/, '');
                    if (ids.includes(id)) this.activeSection = id;
                }, { rootMargin: '-20% 0px -55% 0px', threshold: [0.1, 0.25, 0.5] });

                this.$nextTick(() => {
                    ids.forEach((id) => {
                        const el = document.getElementById('report-' + id);
                        if (el) observer.observe(el);
                    });
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
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="relative bg-gradient-to-br from-slate-50 via-white to-indigo-50/70 px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-indigo-100/40"></div>

                    <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Analytics</p>
                            <h1 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                Reports
                            </h1>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Insights for events you created — click any chart to view fullscreen.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 sm:text-sm">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                            <x-report-export-buttons
                                excel-route="organizer.reports.export.excel"
                                pdf-route="organizer.reports.export.pdf"
                                section="overview"
                                class="!gap-2" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- Filters --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Filters</h2>
                        <p class="text-sm text-slate-500">Narrow by date, event, or booking status</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('organizer.reports') }}"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Clear filters</a>
                    @endif
                </div>

                <form method="GET" action="{{ route('organizer.reports') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                    <div>
                        <label for="from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" id="from" name="from" value="{{ $activeFilters['from'] }}"
                            class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" id="to" name="to" value="{{ $activeFilters['to'] }}"
                            class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="xl:col-span-2">
                        <label for="event_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Your event</label>
                        <select id="event_id" name="event_id"
                            class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All your events</option>
                            @foreach ($filterOptions['events'] as $eventOption)
                                <option value="{{ $eventOption['id'] }}" @selected((string) ($activeFilters['event_id'] ?? '') === (string) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            @foreach ($filterOptions['statuses'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected(($activeFilters['status'] ?? '') === $statusOption)>
                                    {{ ucfirst($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="btn-smooth inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md">
                            <i class="bi bi-funnel"></i>
                            Apply filters
                        </button>
                    </div>
                </form>
            </section>

            {{-- KPI strip --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    [
                        'label' => 'Net revenue',
                        'value' => 'LKR ' . number_format($revenue['netRevenue'], 0),
                        'hint' => 'After refunds',
                        'icon' => 'bi-cash-stack',
                        'top' => 'border-t-emerald-500',
                        'left' => 'border-l-emerald-500',
                        'cardBg' => 'bg-emerald-50/40',
                        'iconBg' => 'bg-emerald-100/70',
                        'iconText' => 'text-emerald-600',
                    ],
                    [
                        'label' => 'Tickets sold',
                        'value' => number_format($sales['totalTicketsSold']),
                        'hint' => $avgTicketsPerEvent . ' avg / event',
                        'icon' => 'bi-ticket-perforated',
                        'top' => 'border-t-blue-500',
                        'left' => 'border-l-blue-500',
                        'cardBg' => 'bg-blue-50/40',
                        'iconBg' => 'bg-blue-100/70',
                        'iconText' => 'text-blue-600',
                    ],
                    [
                        'label' => 'Your events',
                        'value' => number_format($sales['totalEvents']),
                        'hint' => number_format($sales['eventsWithSales']) . ' with sales',
                        'icon' => 'bi-calendar-event',
                        'top' => 'border-t-indigo-500',
                        'left' => 'border-l-indigo-500',
                        'cardBg' => 'bg-indigo-50/40',
                        'iconBg' => 'bg-indigo-100/70',
                        'iconText' => 'text-indigo-600',
                    ],
                    [
                        'label' => 'Attendees',
                        'value' => number_format($attendees['totalAttendees']),
                        'hint' => ($attendees['confirmationRate'] ?? 0) . '% confirmed',
                        'icon' => 'bi-people',
                        'top' => 'border-t-cyan-500',
                        'left' => 'border-l-cyan-500',
                        'cardBg' => 'bg-cyan-50/40',
                        'iconBg' => 'bg-cyan-100/70',
                        'iconText' => 'text-cyan-600',
                    ],
                    [
                        'label' => 'Engagement',
                        'value' => $engagement['averageRating'] ? $engagement['averageRating'] . '/5' : '—',
                        'hint' => number_format($engagement['totalLikes']) . ' likes · ' . number_format($engagement['totalSaves'] ?? 0) . ' saves',
                        'icon' => 'bi-heart',
                        'top' => 'border-t-rose-500',
                        'left' => 'border-l-rose-500',
                        'cardBg' => 'bg-rose-50/40',
                        'iconBg' => 'bg-rose-100/70',
                        'iconText' => 'text-rose-600',
                    ],
                ] as $kpi)
                    <div class="kpi-lift rounded-xl border border-slate-200/80 border-t-[3px] {{ $kpi['top'] }} border-l-[3px] {{ $kpi['left'] }} {{ $kpi['cardBg'] }} bg-white px-4 py-3.5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-slate-500">{{ $kpi['label'] }}</p>
                                <p class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $kpi['hint'] }}</p>
                            </div>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $kpi['iconBg'] }} {{ $kpi['iconText'] }}">
                                <i class="bi {{ $kpi['icon'] }}"></i>
                            </span>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Sticky section nav --}}
            <nav class="report-nav"
                aria-label="Report sections">
                <div class="flex gap-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($navSections as $nav)
                        <button type="button"
                            @click="goSection('{{ $nav['id'] }}')"
                            :class="activeSection === '{{ $nav['id'] }}' ? 'is-active' : ''"
                            class="report-nav-pill">
                            {{ $nav['label'] }}
                        </button>
                    @endforeach
                </div>
            </nav>

            {{-- 1. Overview --}}
            <section id="report-overview" class="report-section scroll-mt-32 p-5 sm:p-6">
                <div class="mb-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Sales</p>
                    <h2 class="mt-0.5 text-lg font-bold text-slate-900">Sales overview</h2>
                    <p class="mt-1 text-sm text-slate-500">Revenue trend and ticket category mix at a glance</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-3"
                        @click="openChart('revenueTrend', 'Revenue trend', 'Income over time from confirmed sales on your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Revenue trend</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Income over time from confirmed sales</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
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
                        @click="openChart('salesByCategory', 'Ticket sales by category', 'Which ticket types sell best on your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Ticket sales by category</h3>
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
                            <p class="mt-4 text-center text-sm text-slate-500">No category sales for this filter.</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 2. Revenue --}}
            <section id="report-revenue" class="report-section scroll-mt-32 p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Revenue</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Revenue analytics</h2>
                        <p class="mt-1 text-sm text-slate-500">Monthly comparison, cumulative growth, and refund impact</p>
                    </div>
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="revenue" />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('refundsVsSales', 'Refunds vs confirmed sales', 'Stacked view of confirmed sales and approved refunds by month')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Refunds vs sales</h3>
                                <p class="mt-0.5 text-sm text-slate-500">How much was lost to refunds</p>
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

            {{-- 3. Tickets --}}
            <section id="report-tickets" class="report-section scroll-mt-32 p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">Tickets</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Ticket sales analytics</h2>
                        <p class="mt-1 text-sm text-slate-500">Sales spikes, ticket-type trends, and conversion from interest to purchase</p>
                    </div>
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="tickets" />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('ticketSalesOverTime', 'Ticket sales over time', 'Confirmed ticket sales by month — spikes often follow promotions')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Ticket sales over time</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Spikes after promotions</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 opacity-80 transition group-hover:bg-blue-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View ticket sales over time fullscreen"
                                @click.stop="openChart('ticketSalesOverTime', 'Ticket sales over time', 'Confirmed ticket sales by month — spikes often follow promotions')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-64">
                            <canvas id="ticketSalesOverTimeChart"></canvas>
                        </div>
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('ticketTypeTrend', 'Ticket type trend', 'How ticket categories like Regular and VIP change month to month')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Ticket type trend</h3>
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
                            <p class="mt-2 text-center text-xs text-slate-500">No ticket-type sales in this period.</p>
                        @endif
                    </div>

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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

            {{-- 4. Events --}}
            <section id="report-events" class="scroll-mt-32 space-y-5">
                <div class="report-section p-5 sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Events</p>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">Event performance</h2>
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700">Key report</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Tickets sold, revenue, fill rate, rating, and status</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                <input type="search" x-model="performanceQuery" placeholder="Filter events…"
                                    class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-52">
                            </div>
                            <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="events" />
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
                                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">No event performance data for this filter.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Top performing + engagement breakdown --}}
                <div class="grid gap-5 lg:grid-cols-5">
                    <div class="report-section overflow-hidden lg:col-span-3">
                        <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-amber-50/70 via-white to-indigo-50/40 px-5 py-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <i class="bi bi-trophy-fill"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Top performing events</h3>
                                <p class="text-sm text-slate-500">Leaderboard for your events</p>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @forelse ($topPerformingEvents as $index => $event)
                                @php
                                    $rank = $index + 1;
                                    $barWidth = min(100, round(((float) $event['revenue'] / $topPerformerMaxRevenue) * 100, 1));
                                    $rankTone = match ($rank) {
                                        1 => 'bg-amber-400 text-amber-950',
                                        2 => 'bg-slate-300 text-slate-800',
                                        3 => 'bg-orange-300 text-orange-950',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <div class="px-5 py-4 transition hover:bg-indigo-50/35">
                                    <div class="flex items-center gap-3 sm:gap-4">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold shadow-sm {{ $rankTone }}">
                                            @if ($rank <= 3)
                                                <i class="bi bi-award-fill text-sm"></i>
                                            @else
                                                {{ $rank }}
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="truncate text-sm font-bold text-slate-900">{{ $event['name'] }}</p>
                                                        @if ($rank === 1)
                                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">#1</span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        {{ number_format($event['tickets_sold']) }} tickets
                                                        · {{ $event['fill_rate'] }}% fill
                                                        @if ($event['rating'])
                                                            · {{ number_format($event['rating'], 1) }}★
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="shrink-0 text-left sm:text-right">
                                                    <p class="text-sm font-bold tabular-nums text-emerald-600">LKR {{ number_format($event['revenue'], 2) }}</p>
                                                    <p class="text-[11px] text-slate-400">{{ $event['status'] }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-500"
                                                    style="width: {{ $barWidth }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-5 py-10 text-center text-sm text-slate-500">No top-performing events yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="report-section report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
                        @click="openChart('engagement', 'Engagement analytics', 'Likes, saves, comments, and ratings on your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Engagement breakdown</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Interaction signals on your events</p>
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
                </div>

                {{-- Event comparison --}}
                <div class="report-section p-5 sm:p-6">
                    <div class="mb-5">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Comparison</p>
                        <h3 class="mt-0.5 text-base font-bold text-slate-900">Event comparison</h3>
                        <p class="mt-1 text-sm text-slate-500">Revenue ranking, profitability vs attendance, and peak sales days</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                            @click="openChart('revenueRanking', 'Events ranked by revenue', 'Horizontal ranking of your events by confirmed ticket revenue')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Revenue ranking</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">Quick event comparison</p>
                                </div>
                                <button type="button"
                                    class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                    title="View fullscreen"
                                    aria-label="View revenue ranking fullscreen"
                                    @click.stop="openChart('revenueRanking', 'Events ranked by revenue', 'Horizontal ranking of your events by confirmed ticket revenue')">
                                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                                </button>
                            </div>
                            <div class="mt-5 h-72">
                                <canvas id="revenueRankingChart"></canvas>
                            </div>
                        </div>

                        <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                            @click="openChart('revenueFillScatter', 'Revenue vs fill rate', 'Events that are both profitable and well attended sit toward the top-right')">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Revenue vs fill rate</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">Profitable and well-attended</p>
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

                        <div class="report-chart p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Sales heatmap</h3>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $salesHeatmap['month_label'] }} · peak ticket days</p>
                                </div>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                    Calendar
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
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
                </div>

                {{-- Revenue / sales by event --}}
                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="report-section report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('revenueByEvent', 'Revenue by event', 'Horizontal comparison of earnings across your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Revenue by event</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Horizontal comparison of your event earnings</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View revenue by event fullscreen"
                                @click.stop="openChart('revenueByEvent', 'Revenue by event', 'Horizontal comparison of earnings across your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-64">
                            <canvas id="overviewRevenueByEventChart"></canvas>
                        </div>
                    </div>
                    <div class="report-section report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('ticketSalesByEvent', 'Ticket sales by event', 'Compare ticket popularity across your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Ticket sales by event</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Compare popularity across your events</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 opacity-80 transition group-hover:bg-blue-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View ticket sales by event fullscreen"
                                @click.stop="openChart('ticketSalesByEvent', 'Ticket sales by event', 'Compare ticket popularity across your events')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-64">
                            <canvas id="overviewTicketSalesByEventChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 5. Engagement --}}
            <section id="report-engagement" class="report-section scroll-mt-32 p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Engagement</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Engagement & conversion</h2>
                        <p class="mt-1 text-sm text-slate-500">Track whether buzz builds before event day, and if engagement correlates with ticket sales</p>
                    </div>
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="engagement" />
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('engagementOverTime', 'Engagement signals over time', 'Monthly likes, saves, comments, and ratings across your events')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Engagement over time</h3>
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
                        @click="openChart('engagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Engagement vs ticket sales</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Do likes and comments actually drive purchases?</p>
                            </div>
                            <button type="button"
                                class="btn-smooth flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 opacity-80 transition group-hover:bg-emerald-100 group-hover:opacity-100"
                                title="View fullscreen"
                                aria-label="View engagement vs ticket sales fullscreen"
                                @click.stop="openChart('engagementVsSales', 'Engagement vs ticket sales', 'Each point is an event — top-right suggests engagement and purchases move together')">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>
                        <div class="mt-5 h-80">
                            <canvas id="engagementVsSalesChart"></canvas>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            Engagement score = likes + saves + comments + ratings. Points toward the top-right mean high interaction and high ticket sales.
                        </p>
                    </div>
                </div>
            </section>

            {{-- 6. Audience --}}
            <section id="report-audience" class="report-section scroll-mt-32 p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-600">Audience</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Audience insights</h2>
                        <p class="mt-1 text-sm text-slate-500">Demographics, loyalty, top buyers, and attendee behavior</p>
                    </div>
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="audience" />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('demographicsAge', 'Age groups', 'Attendee age distribution from confirmed ticket buyers')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Age groups</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Demographic pie</p>
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
                        @click="openChart('demographicsGender', 'Gender', 'Attendee gender split from confirmed ticket buyers')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Gender</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Demographic pie</p>
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

                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-5">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5 lg:col-span-2"
                        @click="openChart('repeatVsNew', 'Repeat vs new attendees', 'Loyalty split of unique confirmed buyers')">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Repeat vs new</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Shows customer loyalty</p>
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
                                    </div>
                                </div>
                            @empty
                                <div class="px-5 py-10 text-center text-sm text-slate-500">No customer purchases yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="report-chart chart-expand-hit group relative p-4 sm:p-5"
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

            {{-- 7. Activity --}}
            <section id="report-activity" class="report-section scroll-mt-32 overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Activity</p>
                        <h2 class="mt-0.5 text-lg font-bold text-slate-900">Recent transactions</h2>
                        <p class="mt-1 text-sm text-slate-500">Latest ticket purchases on your events</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input type="search" x-model="transactionQuery" placeholder="Filter…"
                                class="w-full rounded-xl border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44">
                        </div>
                        <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="activity" />
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
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">No transactions for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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

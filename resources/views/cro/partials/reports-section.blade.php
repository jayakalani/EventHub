{{-- CRO report analytics (merged into dashboard) --}}
@php
    $inquiries = $reports['inquiries'];
    $complaints = $reports['complaints'];
    $reportSatisfaction = $reports['satisfaction'] ?? ['average' => null, 'reviewCount' => 0, 'distribution' => ['labels' => [], 'counts' => [], 'percents' => [], 'total' => 0], 'trend' => []];
    $summary = $reports['summary'] ?? [];
    $activeFilters = $reports['filters'] ?? ['event' => null, 'cro' => null, 'range' => 'month', 'from' => null, 'to' => null];
    $validTabs = ['inquiries', 'complaints'];
    $initialTab = in_array(request('tab'), $validTabs, true) ? request('tab') : 'inquiries';
@endphp

<section id="cro-reports" class="space-y-5"
    x-data="{
        activeTab: '{{ $initialTab }}',
        setTab(tab) {
            this.activeTab = tab;
            this.$nextTick(() => window.dispatchEvent(new CustomEvent('cro-reports-tab-changed')));
        },
    }">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Reports &amp; insights</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Inquiry resolution, complaint mix, and satisfaction for your assigned events
                @if (! empty($activeFilters['selectedEventName']))
                    ·
                    <span class="font-medium text-slate-700">{{ $activeFilters['selectedEventName'] }}</span>
                @endif
            </p>
        </div>
        <x-report-export-buttons
            excel-route="cro.reports.export.excel"
            pdf-route="cro.reports.export.pdf"
            scope="cro"
            section="inquiries"
        />
    </div>

    {{-- Summary KPIs --}}
    <section class="grid grid-cols-2 gap-3 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Resolved', 'value' => number_format($summary['resolved'] ?? 0), 'sub' => 'Closed cases', 'accent' => 'emerald', 'icon' => 'bi-check2-circle'],
            ['label' => 'Pending', 'value' => number_format($summary['pending'] ?? 0), 'sub' => 'Still open', 'accent' => 'amber', 'icon' => 'bi-hourglass-split'],
            ['label' => 'Avg Response', 'value' => $summary['avgResponseLabel'] ?? '—', 'sub' => 'First reply', 'accent' => 'indigo', 'icon' => 'bi-stopwatch'],
            ['label' => 'Resolution Rate', 'value' => ($summary['resolutionRate'] ?? 0) . '%', 'sub' => 'Inquiries', 'accent' => 'cyan', 'icon' => 'bi-graph-up'],
            ['label' => 'CSAT', 'value' => isset($summary['csatAverage']) ? number_format($summary['csatAverage'], 1) . '/5' : '—', 'sub' => number_format($reportSatisfaction['reviewCount'] ?? 0) . ' ratings', 'accent' => 'rose', 'icon' => 'bi-star'],
        ] as $card)
            @php
                $accent = match ($card['accent']) {
                    'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                    'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600'],
                    'cyan' => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600'],
                    'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600'],
                    default => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600'],
                };
            @endphp
            <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $card['sub'] }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} transition-transform duration-300 group-hover:scale-110">
                        <i class="bi {{ $card['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Sticky section nav --}}
    <nav class="sticky top-16 z-30 sm:top-20" aria-label="Report sections">
        <div class="flex gap-1.5 overflow-x-auto rounded-2xl border border-white/60 bg-white/70 p-1.5 shadow-sm backdrop-blur-xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ([
                'overview' => ['label' => 'Overview', 'icon' => 'bi-grid-1x2'],
                'inquiries' => ['label' => 'Inquiries', 'icon' => 'bi-chat-left-text'],
                'complaints' => ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle'],
            ] as $key => $tab)
                <button type="button"
                    @click="{{ $key === 'overview' ? "document.getElementById('report-overview')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" : "setTab('".$key."'); document.getElementById('report-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" }}"
                    class="btn-smooth inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition"
                    :class="{{ $key === 'overview' ? "'bg-white/40 text-slate-600 hover:bg-white/70 hover:text-slate-900'" : "activeTab === '".$key."' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white/40 text-slate-600 hover:bg-white/70 hover:text-slate-900'" }}">
                    <i class="bi {{ $tab['icon'] }}"></i>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </nav>

    {{-- Overview insights --}}
    <section id="report-overview" class="glass-panel !rounded-2xl scroll-mt-28 p-4 sm:p-5">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Visual insights</h3>
                <p class="text-sm text-slate-500">Cross-cutting trends for the current filter set</p>
            </div>
            <x-report-export-buttons
                excel-route="cro.reports.export.excel"
                pdf-route="cro.reports.export.pdf"
                scope="cro"
                section="inquiries"
            />
        </div>
        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="Inquiry vs Resolution"
                description="Submitted vs resolved over time"
                canvas-id="overviewInquiryResolutionChart"
            />
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="Avg Response Time"
                description="Minutes to first response"
                canvas-id="overviewResponseTimeChart"
            />
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="Complaint Categories"
                description="Payment, tickets, refunds, and more"
                canvas-id="overviewComplaintCategoriesChart"
            />
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="Resolution Rate Trend"
                description="Resolution percentage over time"
                canvas-id="overviewResolutionRateChart"
            />
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="CSAT Trend"
                description="Average rating over time"
                canvas-id="overviewCsatTrendChart"
            />
            <x-report-chart-card
                class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                title="CSAT Distribution"
                description="% of 5-star to 1-star ratings"
                canvas-id="overviewCsatDistributionChart"
            />
        </div>
    </section>

    {{-- Detail tabs --}}
    <div id="report-details" class="scroll-mt-28 space-y-5">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ([
                'inquiries' => ['label' => 'Inquiry Resolution', 'icon' => 'bi-chat-left-text', 'desc' => 'Rates, response time, and events'],
                'complaints' => ['label' => 'Complaint Statistics', 'icon' => 'bi-exclamation-triangle', 'desc' => 'Status, categories, and volume'],
            ] as $key => $tab)
                <button type="button"
                    @click="setTab('{{ $key }}')"
                    class="glass-card group !p-4 text-left hover:!-translate-y-1"
                    :class="activeTab === '{{ $key }}' ? 'ring-2 ring-indigo-400/60' : ''">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl transition"
                            :class="activeTab === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600'">
                            <i class="bi {{ $tab['icon'] }} text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $tab['label'] }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $tab['desc'] }}</p>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Inquiries --}}
        <div x-show="activeTab === 'inquiries'" x-cloak class="space-y-5">
            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Inquiry resolution</h3>
                        <p class="text-sm text-slate-500">Performance for the selected filters</p>
                    </div>
                    <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" scope="cro" section="inquiries" />
                </div>

                <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
                    @foreach ([
                        ['label' => 'Total', 'value' => $inquiries['total'], 'accent' => 'indigo', 'icon' => 'bi-inbox'],
                        ['label' => 'Resolution', 'value' => $inquiries['resolutionRate'] . '%', 'accent' => 'emerald', 'icon' => 'bi-check2-circle'],
                        ['label' => 'Active', 'value' => $inquiries['active'], 'accent' => 'amber', 'icon' => 'bi-hourglass-split'],
                        ['label' => 'Resolved', 'value' => $inquiries['resolvedOrClosed'], 'accent' => 'blue', 'icon' => 'bi-patch-check'],
                        ['label' => 'Avg Reply', 'value' => $inquiries['avgResponseLabel'] ?? '—', 'accent' => 'cyan', 'icon' => 'bi-stopwatch'],
                    ] as $card)
                        @php
                            $accent = match ($card['accent']) {
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                                'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600'],
                                'blue' => ['top' => 'border-t-blue-500', 'iconBg' => 'bg-blue-100/70', 'iconText' => 'text-blue-600'],
                                'cyan' => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600'],
                                default => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} !p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $card['value'] }}</p>
                                </div>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $accent['iconBg'] }} transition-transform group-hover:scale-110">
                                    <i class="bi {{ $card['icon'] }} {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($inquiries['statusBreakdown'] as $status)
                        <div class="glass-card !p-3 text-center hover:!-translate-y-0.5">
                            <p class="text-xl font-bold text-slate-900">{{ $status['count'] }}</p>
                            <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $status['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-3">
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Status distribution</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Open through closed</p>
                    <div class="mt-4 h-64 sm:h-72">
                        <canvas id="inquiryStatusChart"></canvas>
                    </div>
                </section>
                <section class="glass-card p-4 sm:p-5 xl:col-span-2">
                    <h4 class="text-base font-bold text-slate-900">Inquiry vs resolution</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Submitted vs resolved in range</p>
                    <div class="mt-4 h-64 sm:h-72">
                        <canvas id="inquiryResolutionTrendChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Average response time</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Minutes to first response</p>
                    <div class="mt-4 h-60">
                        <canvas id="inquiryResponseTimeChart"></canvas>
                    </div>
                </section>
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Inquiries by event</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Highest volume events</p>
                    <div class="mt-4 h-60">
                        <canvas id="inquiryByEventChart"></canvas>
                    </div>
                </section>
            </div>

            <section class="glass-card overflow-hidden !p-0">
                <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">Recent inquiries</h4>
                        <p class="mt-0.5 text-sm text-slate-500">Latest submissions in scope</p>
                    </div>
                    <a href="{{ route('cro.inquiries.index') }}"
                        class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        View all →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Subject</th>
                                <th class="px-4 py-2.5 sm:px-5 hidden md:table-cell">User</th>
                                <th class="px-4 py-2.5 sm:px-5 hidden sm:table-cell">Event</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse($inquiries['recentInquiries'] as $inquiry)
                                <tr class="btn-smooth hover:bg-white/45">
                                    <td class="max-w-xs truncate px-4 py-3 font-medium text-slate-900 sm:px-5">{{ $inquiry['subject'] }}</td>
                                    <td class="hidden whitespace-nowrap px-4 py-3 text-slate-500 md:table-cell sm:px-5">{{ $inquiry['user'] }}</td>
                                    <td class="hidden max-w-xs truncate px-4 py-3 text-slate-500 sm:table-cell sm:px-5">{{ $inquiry['event'] }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $inquiry['statusClass'] }}">
                                            {{ $inquiry['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-xs text-slate-400 sm:px-5">{{ $inquiry['submitted'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">No inquiries for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Complaints --}}
        <div x-show="activeTab === 'complaints'" x-cloak class="space-y-5">
            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Complaint statistics</h3>
                        <p class="text-sm text-slate-500">Status and category breakdown</p>
                    </div>
                    <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" scope="cro" section="complaints" />
                </div>

                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    @foreach ([
                        ['label' => 'Total', 'value' => $complaints['total'], 'accent' => 'rose', 'icon' => 'bi-exclamation-octagon'],
                        ['label' => 'Open', 'value' => $complaints['open'], 'accent' => 'amber', 'icon' => 'bi-envelope-open'],
                        ['label' => 'In Progress', 'value' => $complaints['inProgress'], 'accent' => 'blue', 'icon' => 'bi-arrow-repeat'],
                        ['label' => 'Resolved', 'value' => $complaints['resolved'] + $complaints['closed'], 'accent' => 'emerald', 'icon' => 'bi-check-circle'],
                    ] as $card)
                        @php
                            $accent = match ($card['accent']) {
                                'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600'],
                                'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600'],
                                'blue' => ['top' => 'border-t-blue-500', 'iconBg' => 'bg-blue-100/70', 'iconText' => 'text-blue-600'],
                                default => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} !p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-900">{{ $card['value'] }}</p>
                                </div>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $accent['iconBg'] }} transition-transform group-hover:scale-110">
                                    <i class="bi {{ $card['icon'] }} {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-3">
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">By status</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Current mix</p>
                    <div class="mt-4 h-64 sm:h-72">
                        <canvas id="complaintStatusChart"></canvas>
                    </div>
                </section>
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Categories</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Payment, tickets, refunds…</p>
                    <div class="mt-4 h-64 sm:h-72">
                        <canvas id="complaintCategoryPieChart"></canvas>
                    </div>
                </section>
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Submission trend</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Volume in range</p>
                    <div class="mt-4 h-64 sm:h-72">
                        <canvas id="complaintSubmissionsChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">By type</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Broader subject themes</p>
                    <div class="mt-4 h-64">
                        <canvas id="complaintTypeChart"></canvas>
                    </div>
                </section>
                <section class="glass-card p-4 sm:p-5">
                    <h4 class="text-base font-bold text-slate-900">Status by type</h4>
                    <p class="mt-0.5 text-sm text-slate-500">Handling progress per category</p>
                    <div class="mt-4 h-64">
                        <canvas id="complaintStatusByTypeChart"></canvas>
                    </div>
                </section>
            </div>

            <section class="glass-card overflow-hidden !p-0">
                <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">Recent complaints</h4>
                        <p class="mt-0.5 text-sm text-slate-500">Latest cases in scope</p>
                    </div>
                    <a href="{{ route('cro.complaints.index') }}"
                        class="btn-smooth text-xs font-semibold text-rose-600 hover:text-rose-800">
                        View all →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Subject</th>
                                <th class="px-4 py-2.5 sm:px-5 hidden md:table-cell">User</th>
                                <th class="px-4 py-2.5 sm:px-5 hidden sm:table-cell">Type</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse($complaints['recentComplaints'] as $complaint)
                                <tr class="btn-smooth hover:bg-white/45">
                                    <td class="max-w-xs truncate px-4 py-3 font-medium text-slate-900 sm:px-5">{{ $complaint['subject'] }}</td>
                                    <td class="hidden whitespace-nowrap px-4 py-3 text-slate-500 md:table-cell sm:px-5">{{ $complaint['user'] }}</td>
                                    <td class="hidden px-4 py-3 sm:table-cell sm:px-5">
                                        <span class="inline-flex rounded-md bg-slate-100/80 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                            {{ $complaint['type'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $complaint['statusClass'] }}">
                                            {{ $complaint['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-xs text-slate-400 sm:px-5">{{ $complaint['submitted'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">No complaints for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</section>

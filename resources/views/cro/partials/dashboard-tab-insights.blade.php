{{-- Insights: support charts + inquiry/complaint deep dive --}}
@php
    $inquiries = $reports['inquiries'];
    $complaints = $reports['complaints'];
    $activeInsightFilters = $reports['filters'] ?? ['event' => null, 'from' => null, 'to' => null];
    $selectedEventName = $eventFilter['selectedEventName']
        ?? ($activeInsightFilters['selectedEventName'] ?? null);
    $trendPeriodKey = ($activeRange ?? 'month') === 'week' ? 'week' : 'month';
    $trendPeriodLabel = $dashboard['charts']['periods'][$trendPeriodKey]['label']
        ?? ($trendPeriodKey === 'week' ? 'Weekly' : 'Monthly');
@endphp

<div class="space-y-5" id="cro-insights">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-bold tracking-tight text-slate-900">Support insights</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Trends, inquiry resolution, and complaint mix for your assigned events
                @if ($selectedEventName)
                    · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('cro.reports') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
            <i class="bi bi-sliders"></i>
            Export builder
        </a>
    </div>

    {{-- Support charts + calendar --}}
    <div class="grid gap-4 xl:grid-cols-12">
        <section class="glass-panel !rounded-2xl p-4 sm:p-5 xl:col-span-8">
            <div class="mb-4">
                <h3 class="text-base font-bold text-slate-900">Volume &amp; resolution</h3>
                <p class="text-sm text-slate-500">Driven by the filters above</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card !shadow-none border-white/50 p-4 sm:p-5 lg:col-span-2">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-slate-900">Support trends</h4>
                            <p class="mt-0.5 text-sm text-slate-500">
                                <span data-cro-period-label>{{ $trendPeriodLabel }}</span>
                                volume
                            </p>
                        </div>
                        <button type="button"
                            @click="openChart('supportTrend', @js('Support Trends'), @js('Inquiries, complaints, and refunds over time'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 hover:bg-indigo-100/90"
                            title="View fullscreen"
                            aria-label="View Support Trends fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <button type="button"
                        @click="openChart('supportTrend', @js('Support Trends'), @js('Inquiries, complaints, and refunds over time'))"
                        class="btn-smooth block h-56 w-full cursor-pointer rounded-xl text-left hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-64"
                        aria-label="Open Support Trends fullscreen">
                        <canvas id="croSupportTrendChart" class="pointer-events-none"></canvas>
                    </button>
                </section>
                <section class="glass-card !shadow-none border-white/50 p-4 sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-slate-900">Complaint resolution</h4>
                            <p class="mt-0.5 text-sm text-slate-500">Resolved · Pending · In Progress</p>
                        </div>
                        <button type="button"
                            @click="openChart('complaintStatus', @js('Complaint Resolution Status'), @js('Current complaint mix'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 hover:bg-indigo-100/90"
                            title="View fullscreen"
                            aria-label="View Complaint Resolution fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <button type="button"
                        @click="openChart('complaintStatus', @js('Complaint Resolution Status'), @js('Current complaint mix'))"
                        class="btn-smooth block h-44 w-full cursor-pointer rounded-xl text-left hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-48"
                        aria-label="Open Complaint Resolution fullscreen">
                        <canvas id="croComplaintStatusChart" class="pointer-events-none"></canvas>
                    </button>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ([
                            ['label' => 'Resolved', 'percent' => $complaintStatus['percents'][0] ?? 0, 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/70'],
                            ['label' => 'Pending', 'percent' => $complaintStatus['percents'][1] ?? 0, 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/70'],
                            ['label' => 'In Progress', 'percent' => $complaintStatus['percents'][2] ?? 0, 'color' => 'text-blue-700', 'bg' => 'bg-blue-50/70'],
                        ] as $stat)
                            <div class="rounded-lg border border-white/60 {{ $stat['bg'] }} px-2 py-2 text-center backdrop-blur-sm">
                                <p class="text-[10px] font-medium text-slate-500">{{ $stat['label'] }}</p>
                                <p class="text-sm font-bold {{ $stat['color'] }}">{{ $stat['percent'] }}%</p>
                            </div>
                        @endforeach
                    </div>
                </section>
                <x-report-chart-card
                    class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                    title="Feedback Themes"
                    description="Common problem areas in range"
                    canvas-id="croSupportCategoriesChart"
                    expand-key="supportCategories"
                />
            </div>
        </section>

        <div class="xl:col-span-4">
            <x-dashboard-mini-calendar :calendar="$dashboard['miniCalendar']" class="h-full" />
        </div>
    </div>

    {{-- Inquiry deep dive --}}
    <section class="space-y-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Inquiry insights</h3>
            <p class="text-sm text-slate-500">Status mix, response speed, and recent submissions</p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($inquiries['statusBreakdown'] as $status)
                <div class="glass-card !p-3 text-center hover:!-translate-y-0.5">
                    <p class="text-xl font-bold text-slate-900">{{ $status['count'] }}</p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $status['label'] }}</p>
                </div>
            @endforeach
        </div>

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
    </section>

    {{-- Complaint deep dive (no status doughnut — covered above) --}}
    <section class="space-y-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Complaint insights</h3>
            <p class="text-sm text-slate-500">Categories, volume, and recent cases</p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
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
    </section>
</div>

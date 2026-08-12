{{-- Support analytics: trends, resolution mix, feedback themes --}}
@php
    $activeInsightFilters = $reports['filters'] ?? ['event' => null, 'from' => null, 'to' => null];
    $selectedEventName = $eventFilter['selectedEventName']
        ?? ($activeInsightFilters['selectedEventName'] ?? null);
    $trendPeriodKey = ($activeRange ?? 'month') === 'week' ? 'week' : 'month';
    $trendPeriodLabel = $dashboard['charts']['periods'][$trendPeriodKey]['label']
        ?? ($trendPeriodKey === 'week' ? 'Weekly' : 'Monthly');
@endphp

<div class="space-y-5" id="cro-support">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-indigo-200/60 bg-indigo-50/80 text-indigo-600 shadow-sm">
                <i class="bi bi-headset text-sm"></i>
            </span>
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-900">Support</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Volume trends and resolution health
                    @if ($selectedEventName)
                        · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('cro.reports') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
            <i class="bi bi-sliders"></i>
            Export builder
        </a>
    </div>

    <div class="grid gap-4">
        <section class="glass-panel !rounded-2xl p-4 sm:p-5">
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
    </div>
</div>

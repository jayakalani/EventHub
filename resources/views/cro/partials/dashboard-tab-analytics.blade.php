{{-- Analytics: support charts + calendar --}}
<div class="space-y-5">
    <div class="grid gap-4 xl:grid-cols-12">
        <section class="glass-panel !rounded-2xl p-4 sm:p-5 xl:col-span-8">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Support analytics</h2>
                    <p class="text-sm text-slate-500">
                        Trends for inquiries, complaints, and refunds
                        @if ($eventFilter['selectedEventName'] ?? null)
                            · <span class="font-medium text-slate-700">{{ $eventFilter['selectedEventName'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="inline-flex rounded-xl border border-white/70 bg-white/45 p-1 shadow-sm backdrop-blur-md">
                    @foreach (['week' => 'Weekly', 'month' => 'Monthly'] as $key => $label)
                        <button type="button"
                            data-cro-period="{{ $key }}"
                            @click="setChartPeriod('{{ $key }}')"
                            class="btn-smooth rounded-lg px-3.5 py-1.5 text-xs font-semibold {{ ($dashboard['charts']['defaultPeriod'] ?? 'week') === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white/70' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card !shadow-none border-white/50 p-4 sm:p-5 lg:col-span-2">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Support trends</h3>
                            <p class="mt-0.5 text-sm text-slate-500">
                                <span data-cro-period-label>{{ ($dashboard['charts']['periods']['week']['label'] ?? 'Weekly') }}</span>
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
                            <h3 class="text-base font-bold text-slate-900">Complaint resolution</h3>
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
</div>

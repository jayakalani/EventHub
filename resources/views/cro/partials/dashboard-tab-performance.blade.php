{{-- Performance: KPIs, satisfaction, inquiry/refund tables --}}
<div class="space-y-5">
    @if ($personalKpis)
        <section>
            <div class="mb-3 flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-indigo-200/60 bg-indigo-50/80 text-indigo-600 shadow-sm">
                    <i class="bi bi-speedometer2 text-sm"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold tracking-tight text-slate-900">Your KPIs</h2>
                    <p class="text-xs text-slate-500 sm:text-sm">Response, resolution, refunds, satisfaction</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Avg first response', 'value' => $personalKpis['avgFirstResponseLabel'] ?? '—', 'sub' => 'On cases assigned to you', 'icon' => 'bi-lightning-charge', 'accent' => 'indigo'],
                    ['label' => 'Avg resolution', 'value' => $personalKpis['avgResolutionLabel'] ?? '—', 'sub' => 'Inquiries & complaints', 'icon' => 'bi-hourglass-split', 'accent' => 'sky'],
                    ['label' => 'Refund approve / decline', 'value' => (($personalKpis['refundApproveRate'] ?? null) !== null ? number_format($personalKpis['refundApproveRate'], 0).'% / '.number_format($personalKpis['refundDeclineRate'] ?? 0, 0).'%' : '—'), 'sub' => number_format($personalKpis['refundReviewed'] ?? 0).' reviewed in range', 'icon' => 'bi-arrow-counterclockwise', 'accent' => 'amber'],
                    ['label' => 'Event satisfaction', 'value' => (($personalKpis['satisfactionAverage'] ?? null) !== null ? number_format($personalKpis['satisfactionAverage'], 1).'/5' : '—'), 'sub' => number_format($personalKpis['satisfactionCount'] ?? 0).' ratings on your events', 'icon' => 'bi-star', 'accent' => 'emerald'],
                ] as $kpi)
                    @php
                        $accent = match ($kpi['accent']) {
                            'sky' => ['top' => 'border-t-sky-500', 'iconBg' => 'bg-sky-100/80', 'iconText' => 'text-sky-600'],
                            'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/80', 'iconText' => 'text-amber-600'],
                            'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/80', 'iconText' => 'text-emerald-600'],
                            default => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/80', 'iconText' => 'text-indigo-600'],
                        };
                    @endphp
                    <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                                <p class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $kpi['value'] }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                            </div>
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} transition duration-300 group-hover:scale-110">
                                <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Open Inquiries', 'value' => number_format($kpis['openInquiries']), 'sub' => 'Waiting for response', 'icon' => 'bi-envelope-open', 'accent' => 'amber', 'href' => route('cro.inquiries.index')],
            ['label' => 'Active Complaints', 'value' => number_format($kpis['activeComplaints']), 'sub' => 'Open & in progress', 'icon' => 'bi-exclamation-triangle', 'accent' => 'rose', 'href' => route('cro.complaints.index')],
            ['label' => 'Resolved Today', 'value' => number_format($kpis['resolvedToday']), 'sub' => 'Cases completed today', 'icon' => 'bi-check2-circle', 'accent' => 'emerald', 'href' => null],
            ['label' => 'Avg. Response Time', 'value' => $kpis['avgResponseLabel'], 'sub' => 'First response speed', 'icon' => 'bi-stopwatch', 'accent' => 'indigo', 'href' => null],
        ] as $kpi)
            @php
                $accent = match ($kpi['accent']) {
                    'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/80', 'iconText' => 'text-amber-600'],
                    'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/80', 'iconText' => 'text-rose-600'],
                    'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/80', 'iconText' => 'text-emerald-600'],
                    default => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/80', 'iconText' => 'text-indigo-600'],
                };
                $cardClass = "glass-card kpi-lift group border-t-4 {$accent['top']} p-4 sm:p-5";
            @endphp
            @if ($kpi['href'])
                <a href="{{ $kpi['href'] }}" class="{{ $cardClass }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} transition-transform duration-300 group-hover:scale-110">
                            <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                        </div>
                    </div>
                </a>
            @else
                <div class="{{ $cardClass }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} transition-transform duration-300 group-hover:scale-110">
                            <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="glass-card flex flex-col p-4 sm:p-5 lg:col-span-1">
            <div>
                <h2 class="text-base font-bold text-slate-900">Customer satisfaction</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $satisfaction['label'] }}</p>
            </div>
            <div class="mt-4 flex items-end gap-2">
                @if ($satisfaction['average'] !== null)
                    <p class="text-4xl font-bold tracking-tight text-slate-900">{{ number_format($satisfaction['average'], 1) }}</p>
                    <p class="mb-1 text-lg font-semibold text-slate-400">/ 5</p>
                @else
                    <p class="text-4xl font-bold tracking-tight text-slate-300">—</p>
                @endif
            </div>
            <div class="mt-2 flex items-center gap-1 text-amber-400" aria-hidden="true">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $satisfaction['average'] !== null && $i <= round($satisfaction['average']) ? 'bi-star-fill' : 'bi-star' }} text-sm"></i>
                @endfor
            </div>
            <div class="mt-4 h-36">
                <canvas id="croSatisfactionDistributionChart"></canvas>
            </div>
            @if (($ratingDist['total'] ?? 0) === 0)
                <p class="mt-2 text-center text-[11px] text-slate-400">No star ratings yet for this scope.</p>
            @endif
            <div class="mt-4 rounded-xl border border-emerald-200/50 bg-emerald-50/50 px-3.5 py-3 backdrop-blur-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Positive share</p>
                    <p class="text-xl font-bold text-emerald-700">
                        @if (($satisfaction['source'] ?? null) === 'ratings')
                            {{ number_format($satisfaction['happyPercent'], 0) }}%
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-emerald-100">
                    <div class="h-full rounded-full bg-emerald-500 transition-all"
                        style="width: {{ ($satisfaction['source'] ?? null) === 'ratings' ? min(100, max(0, $satisfaction['happyPercent'])) : 0 }}%"></div>
                </div>
            </div>
            @if (count($feedbackThemes))
                <div class="mt-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Top feedback themes</p>
                    <div class="mt-2 space-y-1.5">
                        @foreach ($feedbackThemes as $theme)
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-white/60 bg-white/50 px-2.5 py-1.5 backdrop-blur-sm">
                                <p class="truncate text-xs font-medium text-slate-700">{{ $theme['label'] }}</p>
                                <p class="shrink-0 text-xs font-bold text-slate-900">{{ $theme['count'] }} · {{ number_format($theme['percent'], 0) }}%</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <div class="grid gap-4 lg:col-span-2">
            <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
                <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Recent inquiries</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Latest customer messages</p>
                    </div>
                    <a href="{{ route('cro.inquiries.index') }}"
                        class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                        View all →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Customer</th>
                                <th class="px-4 py-2.5 sm:px-5">Subject</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                                <th class="px-4 py-2.5 sm:px-5">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse ($dashboard['recentInquiries'] as $inquiry)
                                <tr class="btn-smooth cursor-pointer hover:bg-white/50"
                                    onclick="window.location.href='{{ $inquiry['href'] }}'">
                                    <td class="px-4 py-3 sm:px-5">
                                        <p class="font-medium text-slate-900 whitespace-nowrap">{{ $inquiry['customer'] }}</p>
                                        <p class="mt-0.5 max-w-[9rem] truncate text-[11px] text-slate-400">{{ $inquiry['event'] }}</p>
                                    </td>
                                    <td class="max-w-[10rem] truncate px-4 py-3 text-slate-700 sm:px-5">{{ $inquiry['subject'] }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $inquiry['statusClass'] }}">
                                            {{ $inquiry['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 sm:px-5">{{ $inquiry['time'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-slate-500">No inquiries yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
                <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pending refunds</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Awaiting review</p>
                    </div>
                    <a href="{{ route('cro.refund-requests.index') }}"
                        class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                        Review →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Customer</th>
                                <th class="px-4 py-2.5 sm:px-5">Event</th>
                                <th class="px-4 py-2.5 sm:px-5">Amount</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse ($dashboard['pendingRefunds'] as $refund)
                                <tr class="btn-smooth cursor-pointer hover:bg-white/50"
                                    onclick="window.location.href='{{ $refund['href'] }}'">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 sm:px-5">{{ $refund['customer'] }}</td>
                                    <td class="max-w-[9rem] truncate px-4 py-3 text-slate-700 sm:px-5">{{ $refund['event'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-emerald-700 sm:px-5">{{ $refund['amount'] }}</td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $refund['statusClass'] }}">
                                            {{ $refund['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-slate-500">No pending refunds.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

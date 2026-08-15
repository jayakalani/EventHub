@php
    $supportReport = $supportReport ?? [];
    $cros = $supportReport['cros'] ?? [];
    $selectedCroId = $supportReport['selectedCroId'] ?? null;
    $selectedCroName = $supportReport['selectedCroName'] ?? null;
    $selectedOrganizerId = $supportReport['selectedOrganizerId'] ?? ($scopeFilter['selectedOrganizerId'] ?? null);
    $selectedEventId = $supportReport['selectedEventId'] ?? ($scopeFilter['selectedEventId'] ?? null);
    $totalInquiries = $supportReport['totalInquiries'] ?? 0;
    $totalComplaints = $supportReport['totalComplaints'] ?? 0;
    $resolvedCount = $supportReport['resolvedCount'] ?? 0;
    $pendingCount = $supportReport['pendingCount'] ?? 0;
    $pendingInquiries = $supportReport['pendingInquiries'] ?? 0;
    $pendingComplaints = $supportReport['pendingComplaints'] ?? 0;
    $resolvedInquiries = $supportReport['resolvedInquiries'] ?? 0;
    $resolvedComplaints = $supportReport['resolvedComplaints'] ?? 0;
    $recentInquiries = $supportReport['recentInquiries'] ?? collect();
    $recentComplaints = $supportReport['recentComplaints'] ?? collect();
    $slaAging = $supportReport['slaAging'] ?? [];

    $scopeCaption = $supportReport['scopeCaption']
        ?? ($selectedCroName ? 'Assigned to '.$selectedCroName : 'All customer relations officers');

    $exportParams = array_filter([
        'cro' => $selectedCroId,
        'organizer' => $selectedOrganizerId,
        'event' => $selectedEventId,
        'from' => $activeFilters['from'] ?? ($dateFilter['from'] ?? null),
        'to' => $activeFilters['to'] ?? ($dateFilter['to'] ?? null),
    ], fn ($value) => filled($value));

    $kpis = [
        [
            'label' => 'Total Inquiries',
            'value' => $totalInquiries,
            'sub' => 'Event-related questions',
            'icon' => 'bi-chat-left-text',
            'accent' => 'indigo',
        ],
        [
            'label' => 'Total Complaints',
            'value' => $totalComplaints,
            'sub' => 'Escalated concerns',
            'icon' => 'bi-exclamation-triangle',
            'accent' => 'rose',
        ],
        [
            'label' => 'Resolved Jobs',
            'value' => $resolvedCount,
            'sub' => $resolvedInquiries.' inquiries · '.$resolvedComplaints.' complaints',
            'icon' => 'bi-check2-circle',
            'accent' => 'emerald',
        ],
        [
            'label' => 'Pending Jobs',
            'value' => $pendingCount,
            'sub' => $pendingInquiries.' inquiries · '.$pendingComplaints.' complaints',
            'icon' => 'bi-hourglass-split',
            'accent' => 'amber',
        ],
    ];
@endphp

<div class="space-y-5">
    <section class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Support</h2>
            <p class="text-sm text-slate-500">{{ $scopeCaption }} · inquiry and complaint workload · date chips apply</p>
        </div>
    </section>

    <section class="space-y-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Workload snapshot</h3>
            <p class="text-xs text-slate-500">CRO, organizer, event, and date chips.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                @php
                    $accent = match ($kpi['accent']) {
                        'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700'],
                        'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600', 'value' => 'text-rose-700'],
                        'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600', 'value' => 'text-emerald-700'],
                        default => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600', 'value' => 'text-amber-700'],
                    };
                @endphp
                <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                            <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $accent['value'] }}">
                                {{ number_format($kpi['value']) }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                            <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="space-y-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Volume and SLA</h3>
            <p class="text-xs text-slate-500">Weekly intake and aging of open tickets in this scope.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-report-chart-card
                title="Support volume"
                description="Inquiries vs complaints · last 8 weeks, clipped to the date chips"
                canvas-id="adminSupportVolumeChart"
                expand-key="supportVolume"
                expand-title="Support volume"
                expand-description="Weekly inquiry and complaint counts for the current filters"
            />
            <div class="space-y-3">
                <x-report-chart-card
                    title="Open ticket SLA"
                    description="24h aging · 48h overdue · urgent subjects"
                    canvas-id="adminSupportSlaChart"
                    expand-key="supportSla"
                    expand-title="Open ticket SLA"
                    expand-description="Open and in-progress tickets scored against SLA"
                />
                @if ($slaAging !== [])
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($slaAging as $bucket)
                            @php
                                $tile = match ($bucket['key'] ?? '') {
                                    'urgent' => ['bg' => 'bg-rose-50/80', 'text' => 'text-rose-700'],
                                    'overdue' => ['bg' => 'bg-orange-50/80', 'text' => 'text-orange-800'],
                                    'aging' => ['bg' => 'bg-amber-50/80', 'text' => 'text-amber-800'],
                                    default => ['bg' => 'bg-emerald-50/80', 'text' => 'text-emerald-700'],
                                };
                            @endphp
                            <div class="rounded-xl border border-white/60 {{ $tile['bg'] }} px-3 py-2.5 text-center backdrop-blur-sm">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $bucket['label'] }}</p>
                                <p class="mt-0.5 text-lg font-bold {{ $tile['text'] }}">{{ number_format($bucket['count'] ?? 0) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="space-y-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Recent activity</h3>
            <p class="text-xs text-slate-500">Latest inquiries and complaints in this scope.</p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
                <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100/80 text-indigo-600">
                            <i class="bi bi-chat-left-text"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-slate-900">Recent Inquiries</h3>
                            <p class="text-xs text-slate-500">Event support questions</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-white/60 bg-white/50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 backdrop-blur-sm">
                        {{ number_format($recentInquiries->count()) }}
                    </span>
                </div>

                <div class="divide-y divide-white/40">
                    @forelse ($recentInquiries as $inquiry)
                        <div class="btn-smooth px-4 py-3.5 hover:bg-white/45 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $inquiry->subject }}</p>
                                    <p class="mt-0.5 truncate text-sm text-slate-500">
                                        {{ $inquiry->user->full_name }}
                                        @if ($inquiry->event)
                                            · {{ $inquiry->event->name }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $inquiry->created_at?->diffForHumans() }}</p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $inquiry->status])
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-10 sm:px-5">
                            <x-report-empty-state
                                class="!min-h-[8rem] border-0 bg-transparent shadow-none"
                                title="No inquiries found."
                                hint="Try another CRO, organizer, or event filter."
                            />
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
                <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100/80 text-rose-600">
                            <i class="bi bi-exclamation-triangle"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-slate-900">Recent Complaints</h3>
                            <p class="text-xs text-slate-500">Escalated attendee issues</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-white/60 bg-white/50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 backdrop-blur-sm">
                        {{ number_format($recentComplaints->count()) }}
                    </span>
                </div>

                <div class="divide-y divide-white/40">
                    @forelse ($recentComplaints as $complaint)
                        <div class="btn-smooth px-4 py-3.5 hover:bg-white/45 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $complaint->subject }}</p>
                                    <p class="mt-0.5 truncate text-sm text-slate-500">
                                        {{ $complaint->user->full_name }}
                                        @if ($complaint->event)
                                            · {{ $complaint->event->name }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $complaint->created_at?->diffForHumans() }}</p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $complaint->status])
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-10 sm:px-5">
                            <x-report-empty-state
                                class="!min-h-[8rem] border-0 bg-transparent shadow-none"
                                title="No complaints found."
                                hint="Try another CRO, organizer, or event filter."
                            />
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>

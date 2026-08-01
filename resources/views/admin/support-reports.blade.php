<x-app-layout>
    @php
        $scopeCaption = $selectedCroName
            ? 'Assigned to '.$selectedCroName
            : 'All customer relations officers';
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

    <div class="admin-support-reports relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- 1. Hero --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/25 blur-2xl"></div>

                    <div class="relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10">
                                    <i class="bi bi-headset text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Support operations</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Support Reports
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                {{ $scopeCaption }} · {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <form method="GET" action="{{ route('admin.support-reports') }}" class="min-w-[11rem] flex-1 sm:flex-none">
                                <label for="support_report_cro" class="sr-only">Filter by CRO</label>
                                <select
                                    id="support_report_cro"
                                    name="cro"
                                    onchange="this.form.submit()"
                                    class="btn-smooth block w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">All CROs</option>
                                    @foreach ($cros as $croOption)
                                        <option value="{{ $croOption['id'] }}" @selected((int) ($selectedCroId ?? 0) === (int) $croOption['id'])>
                                            {{ $croOption['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>

                            <a href="{{ route('admin.support-reports.export.csv', array_filter(['cro' => $selectedCroId])) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-filetype-csv"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('admin.support-reports.export.pdf', array_filter(['cro' => $selectedCroId])) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Export PDF
                            </a>
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. Snapshot KPIs --}}
            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Workload snapshot</h2>
                    <p class="text-xs text-slate-500">Inquiry and complaint volume for the selected scope.</p>
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

            {{-- 3. Recent activity --}}
            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Recent activity</h2>
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
                                        hint="Try another CRO or check back later."
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
                                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $complaint->user->full_name }}</p>
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
                                        hint="Try another CRO or check back later."
                                    />
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>

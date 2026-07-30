<x-app-layout>
    @php
        $kpis = $dashboard['kpis'];
        $todayTasks = $dashboard['todayTasks'];
        $complaintStatus = $dashboard['charts']['complaintStatus'];
        $satisfaction = $dashboard['satisfaction'];
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'CRO';
        $initials = strtoupper(substr($user?->first_name ?? 'C', 0, 1) . substr($user?->last_name ?? '', 0, 1));
    @endphp

    <div class="cro-dashboard relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            open: false,
            chartKey: null,
            title: '',
            description: '',
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('cro-chart-expand', {
                        detail: { key },
                    }));
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('cro-chart-collapse'));
            },
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-rose-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- 1. Header --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-indigo-200/30 blur-2xl"></div>

                    <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                @if ($user?->profile_photo)
                                    <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                        alt="{{ $displayName }}"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white/80 shadow-sm sm:h-10 sm:w-10">
                                @else
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-xs font-bold text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10 sm:text-sm">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $greeting }}, {{ $displayName }}
                                    </p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        CRO Dashboard
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Customer relations workspace · {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <a href="{{ route('cro.inquiries.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-chat-dots"></i>
                                Inquiries
                            </a>
                            <a href="{{ route('cro.complaints.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-exclamation-triangle"></i>
                                Complaints
                            </a>
                            <a href="{{ route('cro.refund-requests.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Refunds
                            </a>
                            <a href="{{ route('cro.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-bar-chart-line"></i>
                                Reports
                            </a>
                        </div>
                    </div>

                    {{-- Today strip --}}
                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>
                        <div class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ([
                                ['label' => 'New Inquiries', 'value' => $todayTasks['newInquiries'], 'href' => route('cro.inquiries.index'), 'icon' => 'bi-envelope', 'bg' => 'bg-indigo-50/60', 'iconBg' => 'bg-indigo-100/80', 'iconColor' => 'text-indigo-600'],
                                ['label' => 'Refunds', 'value' => $todayTasks['refundRequests'], 'href' => route('cro.refund-requests.index'), 'icon' => 'bi-arrow-counterclockwise', 'bg' => 'bg-amber-50/60', 'iconBg' => 'bg-amber-100/80', 'iconColor' => 'text-amber-600'],
                                ['label' => 'Urgent Complaints', 'value' => $todayTasks['urgentComplaints'], 'href' => route('cro.complaints.index'), 'icon' => 'bi-exclamation-octagon', 'bg' => 'bg-rose-50/60', 'iconBg' => 'bg-rose-100/80', 'iconColor' => 'text-rose-600'],
                                ['label' => 'Events Today', 'value' => $todayTasks['eventsToday'], 'href' => null, 'icon' => 'bi-calendar-event', 'bg' => 'bg-cyan-50/60', 'iconBg' => 'bg-cyan-100/80', 'iconColor' => 'text-cyan-600'],
                            ] as $task)
                                @if ($task['href'])
                                    <a href="{{ $task['href'] }}"
                                        class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 {{ $task['bg'] }} px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                        <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $task['iconBg'] }} text-sm {{ $task['iconColor'] }} sm:flex">
                                            <i class="bi {{ $task['icon'] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">{{ number_format($task['value']) }}</p>
                                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $task['label'] }}</p>
                                        </div>
                                    </a>
                                @else
                                    <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 {{ $task['bg'] }} px-2 py-1.5 backdrop-blur-sm sm:px-2.5">
                                        <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $task['iconBg'] }} text-sm {{ $task['iconColor'] }} sm:flex">
                                            <i class="bi {{ $task['icon'] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">{{ number_format($task['value']) }}</p>
                                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $task['label'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. Performance KPIs --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Open Inquiries', 'value' => number_format($kpis['openInquiries']), 'sub' => 'Waiting for response', 'icon' => 'bi-envelope-open', 'accent' => 'amber', 'href' => route('cro.inquiries.index')],
                    ['label' => 'Active Complaints', 'value' => number_format($kpis['activeComplaints']), 'sub' => 'Open & in progress', 'icon' => 'bi-exclamation-triangle', 'accent' => 'rose', 'href' => route('cro.complaints.index')],
                    ['label' => 'Resolved Today', 'value' => number_format($kpis['resolvedToday']), 'sub' => 'Cases completed today', 'icon' => 'bi-check2-circle', 'accent' => 'emerald', 'href' => null],
                    ['label' => 'Avg. Response Time', 'value' => $kpis['avgResponseLabel'], 'sub' => 'First response speed', 'icon' => 'bi-stopwatch', 'accent' => 'indigo', 'href' => null],
                ] as $kpi)
                    @php
                        $accent = match ($kpi['accent']) {
                            'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600'],
                            'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600'],
                            'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                            default => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600'],
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

            {{-- 3. Priority queue + satisfaction --}}
            <div class="grid gap-4 lg:grid-cols-3">
                <section class="glass-card overflow-hidden !p-0 lg:col-span-2">
                    <div class="flex items-center justify-between gap-3 border-b border-rose-200/50 bg-rose-50/40 px-4 py-3.5 sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-rose-900">High Priority Cases</h2>
                            <p class="mt-0.5 text-sm text-rose-600/80">Urgent issues requiring immediate attention</p>
                        </div>
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-rose-100 px-2 text-sm font-bold text-rose-700">
                            {{ count($dashboard['highPriority']) }}
                        </span>
                    </div>
                    <div class="divide-y divide-rose-100/70">
                        @forelse ($dashboard['highPriority'] as $case)
                            <a href="{{ $case['href'] }}"
                                class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-rose-50/50 sm:px-5">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                    <i class="bi bi-exclamation-triangle-fill text-sm"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-rose-950">{{ $case['title'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $case['meta'] }}</p>
                                </div>
                                <i class="bi bi-chevron-right mt-1 text-xs text-rose-300"></i>
                            </a>
                        @empty
                            <div class="flex flex-col items-center justify-center px-4 py-12 text-center sm:px-5">
                                <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                                <p class="text-sm font-medium text-slate-700">All clear</p>
                                <p class="mt-0.5 text-xs text-slate-500">No high-priority cases right now.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="glass-card flex flex-col p-4 sm:p-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Customer Satisfaction</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Based on resolved support cases</p>
                    </div>
                    <div class="mt-5 flex flex-1 flex-col justify-center">
                        <div class="flex items-end gap-2">
                            @if ($satisfaction['average'] !== null)
                                <p class="text-4xl font-bold tracking-tight text-slate-900">
                                    {{ number_format($satisfaction['average'], 1) }}
                                </p>
                                <p class="mb-1 text-lg font-semibold text-slate-400">/ 5</p>
                            @else
                                <p class="text-4xl font-bold tracking-tight text-slate-300">—</p>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center gap-1 text-amber-400" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $satisfaction['average'] !== null && $i <= round($satisfaction['average']) ? 'bi-star-fill' : 'bi-star' }} text-sm"></i>
                            @endfor
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ $satisfaction['label'] }}</p>
                        <div class="mt-5 rounded-xl border border-emerald-200/60 bg-emerald-50/60 px-3.5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Happy customers</p>
                                <p class="text-xl font-bold text-emerald-700">{{ number_format($satisfaction['happyPercent'], 0) }}%</p>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-emerald-100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all"
                                    style="width: {{ min(100, max(0, $satisfaction['happyPercent'])) }}%"></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- 4. Work queues --}}
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Inquiries</h2>
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
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5">
                                            <p class="font-medium text-slate-900 whitespace-nowrap">{{ $inquiry['customer'] }}</p>
                                            <p class="mt-0.5 max-w-[9rem] truncate text-[11px] text-slate-400">{{ $inquiry['event'] }}</p>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-700 max-w-[10rem] truncate">{{ $inquiry['subject'] }}</td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $inquiry['statusClass'] }}">
                                                {{ $inquiry['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-500 whitespace-nowrap text-xs">{{ $inquiry['time'] }}</td>
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

                <section class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Pending Refunds</h2>
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
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5 font-medium text-slate-900 whitespace-nowrap">{{ $refund['customer'] }}</td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-700 max-w-[9rem] truncate">{{ $refund['event'] }}</td>
                                        <td class="px-4 py-3 sm:px-5 font-semibold text-emerald-700 whitespace-nowrap">{{ $refund['amount'] }}</td>
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

            {{-- 5. Analytics --}}
            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Support Analytics</h2>
                        <p class="text-sm text-slate-500">Trends and complaint distribution</p>
                    </div>
                    <a href="{{ route('cro.reports') }}" class="btn-smooth text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        Full reports →
                    </a>
                </div>
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-report-chart-card
                        class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                        title="Inquiry Trend"
                        description="Volume over the past week"
                        canvas-id="croInquiryTrendChart"
                        expand-key="inquiryTrend"
                    />
                    <section class="glass-card !shadow-none border-white/50 p-4 sm:p-5">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Complaint Status</h3>
                                <p class="mt-0.5 text-sm text-slate-500">Current mix</p>
                            </div>
                            <button type="button"
                                @click="openChart('complaintStatus', @js('Complaint Status'), @js('Current complaint mix'))"
                                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 hover:bg-indigo-100/90"
                                title="View fullscreen"
                                aria-label="View Complaint Status fullscreen">
                                <i class="bi bi-arrows-fullscreen text-xs"></i>
                            </button>
                        </div>
                        <button type="button"
                            @click="openChart('complaintStatus', @js('Complaint Status'), @js('Current complaint mix'))"
                            class="btn-smooth block h-44 w-full cursor-pointer rounded-xl text-left hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-48"
                            aria-label="Open Complaint Status fullscreen">
                            <canvas id="croComplaintStatusChart" class="pointer-events-none"></canvas>
                        </button>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach ([
                                ['label' => 'Resolved', 'percent' => $complaintStatus['percents'][0] ?? 0, 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/70'],
                                ['label' => 'Pending', 'percent' => $complaintStatus['percents'][1] ?? 0, 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/70'],
                                ['label' => 'In Progress', 'percent' => $complaintStatus['percents'][2] ?? 0, 'color' => 'text-blue-700', 'bg' => 'bg-blue-50/70'],
                            ] as $stat)
                                <div class="rounded-lg border border-white/60 {{ $stat['bg'] }} px-2 py-2 text-center">
                                    <p class="text-[10px] font-medium text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="text-sm font-bold {{ $stat['color'] }}">{{ $stat['percent'] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <x-report-chart-card
                        class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                        title="Support Categories"
                        description="Common problem areas"
                        canvas-id="croSupportCategoriesChart"
                        expand-key="supportCategories"
                    />
                </div>
            </section>

            {{-- 6. Context: events + activity --}}
            <div class="grid gap-4 lg:grid-cols-5">
                <section class="glass-card overflow-hidden !p-0 lg:col-span-3">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Event Support Overview</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Events happening today</p>
                    </div>
                    <div class="divide-y divide-white/40">
                        @forelse ($dashboard['eventsToday'] as $event)
                            <div class="btn-smooth px-4 py-3.5 hover:bg-white/45 sm:px-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-slate-900">{{ $event['name'] }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-md border border-cyan-200/50 bg-cyan-50/70 px-2 py-0.5 text-[11px] font-semibold text-cyan-700">
                                            <i class="bi bi-people"></i>
                                            {{ number_format($event['attendees']) }} attendees
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-md border border-amber-200/50 bg-amber-50/70 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                            <i class="bi bi-envelope"></i>
                                            {{ number_format($event['openInquiries']) }} inquiries
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-md border border-rose-200/50 bg-rose-50/70 px-2 py-0.5 text-[11px] font-semibold text-rose-700">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            {{ number_format($event['pendingRefunds']) }} refunds
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center px-4 py-12 text-center sm:px-5">
                                <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i class="bi bi-calendar-x"></i>
                                </span>
                                <p class="text-sm text-slate-500">No events happening today.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="glass-card overflow-hidden !p-0 lg:col-span-2">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Recent Activity</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Latest support actions</p>
                    </div>
                    <div class="max-h-80 divide-y divide-white/40 overflow-y-auto">
                        @forelse ($dashboard['recentActivity'] as $activity)
                            @php
                                $color = match ($activity['color']) {
                                    'emerald' => 'bg-emerald-100 text-emerald-600',
                                    'amber' => 'bg-amber-100 text-amber-600',
                                    'blue' => 'bg-blue-100 text-blue-600',
                                    default => 'bg-indigo-100 text-indigo-600',
                                };
                            @endphp
                            <div class="btn-smooth flex items-start gap-3 px-4 py-3 hover:bg-white/45 sm:px-5">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                                    <i class="bi {{ $activity['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $activity['title'] }}</p>
                                        <span class="shrink-0 text-[11px] font-medium text-slate-400">{{ $activity['time'] }}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $activity['meta'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-12 text-center text-sm text-slate-500 sm:px-5">
                                No recent activity yet.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- Fullscreen chart modal --}}
        <div x-cloak
            x-show="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6"
            role="presentation">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="closeChart()"
                aria-hidden="true"></div>

            <div class="relative flex h-[min(92vh,56rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/50 bg-white/85 shadow-2xl shadow-indigo-500/10 backdrop-blur-2xl"
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
                <div class="flex items-start justify-between gap-4 border-b border-white/50 bg-white/40 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-slate-500 hover:bg-white hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full">
                        <canvas id="croChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-white/50 bg-white/30 px-5 py-3 text-xs text-slate-400 sm:px-6">
                    Press <kbd class="rounded border border-slate-200/80 bg-white/70 px-1.5 py-0.5 font-semibold text-slate-600">Esc</kbd> to close
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
            window.croDashboardData = @json($dashboard);
        </script>
        @vite('resources/js/cro-dashboard.js')
    @endpush
</x-app-layout>

<x-app-layout>
    @php
        $kpis = $dashboard['kpis'];
        $todayTasks = $dashboard['todayTasks'];
        $todayWork = $dashboard['todayWork'] ?? [];
        $personalKpis = $dashboard['personalKpis'] ?? null;
        $handoffs = $dashboard['handoffs'] ?? [];
        $complaintStatus = $dashboard['charts']['complaintStatus'];
        $satisfaction = $dashboard['satisfaction'];
        $eventFilter = $dashboard['eventFilter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => []];
        $filters = $dashboard['filters'] ?? ['event' => null, 'from' => null, 'to' => null];
        $feedbackThemes = $dashboard['feedbackThemes'] ?? [];
        $ratingDist = $dashboard['charts']['satisfactionDistribution'] ?? ['labels' => [], 'counts' => [], 'percents' => [], 'total' => 0];
        $activeFilters = $reports['filters'] ?? ['event' => null, 'cro' => null, 'range' => 'month', 'from' => null, 'to' => null];
        $filterOptions = $reports['filterOptions'] ?? ['events' => $eventFilter['events'] ?? [], 'cros' => []];
        $activeRange = $activeFilters['range'] ?? 'month';
        $chartPeriod = $activeRange === 'week' ? 'week' : 'month';
        $selectedEventId = (int) ($activeFilters['event'] ?? $eventFilter['selectedEventId'] ?? 0);
        $selectedEventName = $activeFilters['selectedEventName']
            ?? collect($filterOptions['events'] ?? [])->firstWhere('id', $selectedEventId)['name']
            ?? null;
        $hasActiveFilters = filled($activeFilters['event'] ?? null)
            || filled($filters['event'] ?? null)
            || ($activeRange !== 'month');
        $filterQueryBase = array_filter([
            'event' => $activeFilters['event'] ?? $filters['event'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
        $datePresets = [
            'week' => [
                'key' => 'week',
                'label' => 'Weekly',
                'from' => now()->subDays(6)->toDateString(),
                'to' => now()->toDateString(),
            ],
            'month' => [
                'key' => 'month',
                'label' => 'Monthly',
                'from' => now()->subDays(29)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'CRO';
        $initials = strtoupper(substr($user?->first_name ?? 'C', 0, 1) . substr($user?->last_name ?? '', 0, 1));
        $queueCount = (int) ($todayTasks['queueTotal'] ?? 0);
        $sectionTabs = [
            'today' => ['label' => 'Today', 'icon' => 'bi-lightning-charge', 'badge' => $queueCount > 0 ? $queueCount : null],
            'attendance' => ['label' => 'Attendance', 'icon' => 'bi-person-check', 'badge' => null],
            'performance' => ['label' => 'Performance', 'icon' => 'bi-speedometer2', 'badge' => null],
            'support' => ['label' => 'Support', 'icon' => 'bi-headset', 'badge' => null],
            'inquiry' => ['label' => 'Inquiry', 'icon' => 'bi-chat-left-text', 'badge' => null],
            'complaints' => ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle', 'badge' => null],
        ];
        $pulseStats = [
            ['label' => 'New inquiries', 'value' => $todayTasks['newInquiries'], 'href' => route('cro.inquiries.index'), 'icon' => 'bi-envelope', 'tone' => 'indigo'],
            ['label' => 'Refunds', 'value' => $todayTasks['refundRequests'], 'href' => route('cro.refund-requests.index'), 'icon' => 'bi-arrow-counterclockwise', 'tone' => 'amber'],
            ['label' => 'Urgent', 'value' => $todayTasks['urgentComplaints'], 'href' => route('cro.complaints.index'), 'icon' => 'bi-exclamation-octagon', 'tone' => 'rose'],
            ['label' => 'Events today', 'value' => $todayTasks['eventsToday'], 'href' => null, 'scrollTo' => 'cro-events-today', 'section' => 'today', 'icon' => 'bi-calendar-event', 'tone' => 'cyan'],
        ];
    @endphp

    <div class="cro-dashboard relative isolate py-5 sm:py-6"
        x-data="{
            open: false,
            chartKey: null,
            title: '',
            description: '',
            chartPeriod: @js($chartPeriod),
            attendanceQuery: '',
            tabsStuck: false,
            section: (() => {
                const hash = (window.location.hash || '').replace('#', '');
                if (['cro-reports', 'reports', 'analytics', 'cro-insights', 'insights'].includes(hash)) return 'support';
                if (['today', 'attendance', 'performance', 'support', 'inquiry', 'complaints'].includes(hash)) return hash;
                return 'today';
            })(),
            matches(text, query) {
                if (!query) return true;
                return String(text || '').toLowerCase().includes(String(query).toLowerCase());
            },
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('cro-chart-expand', {
                        detail: { key, period: this.chartPeriod },
                    }));
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('cro-chart-collapse'));
            },
            setSection(section) {
                this.section = section;
                history.replaceState(null, '', '#' + section);
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('cro-dashboard-section-changed', { detail: { section } }));
                    if (['support', 'inquiry', 'complaints'].includes(section)) {
                        window.dispatchEvent(new CustomEvent('cro-reports-tab-changed'));
                    }
                });
            },
            init() {
                this.$nextTick(() => {
                    const sentinel = this.$refs.tabSentinel;
                    if (!sentinel || !('IntersectionObserver' in window)) return;
                    const io = new IntersectionObserver(([entry]) => {
                        this.tabsStuck = !entry.isIntersecting;
                    }, { root: null, threshold: 0, rootMargin: '-72px 0px 0px 0px' });
                    io.observe(sentinel);
                    this.$el._croTabsObserver = io;
                });
            },
            destroy() {
                this.$el._croTabsObserver?.disconnect();
            },
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/45 to-cyan-50/55"></div>
            <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-36 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-sky-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-50"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:space-y-5 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="glass-card !rounded-xl border-emerald-200/80 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-emerald-800"
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 5000)"
                    x-transition.opacity>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-check-circle-fill text-emerald-600"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="glass-card !rounded-xl border-rose-200/80 bg-rose-50/70 px-4 py-3 text-sm font-medium text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Hero --}}
            <section class="glass-panel relative overflow-hidden !rounded-2xl">
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-indigo-300/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/3 h-36 w-36 rounded-full bg-cyan-300/25 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-white/20 via-transparent to-indigo-50/20"></div>

                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                @if ($user?->profile_photo)
                                    <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                        alt="{{ $displayName }}"
                                        class="h-11 w-11 rounded-2xl object-cover shadow-md ring-2 ring-white/80 sm:h-12 sm:w-12">
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 text-sm font-bold text-white shadow-md shadow-indigo-500/30 ring-2 ring-white/70 sm:h-12 sm:w-12">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-600">{{ $greeting }}, {{ $displayName }}</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">CRO Dashboard</h1>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 shadow-sm backdrop-blur-md">
                                    <i class="bi bi-calendar3 text-indigo-500"></i>
                                    {{ now()->format('l, M j, Y') }}
                                </span>
                                @if ($selectedEventName)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200/70 bg-indigo-50/70 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 shadow-sm backdrop-blur-md">
                                        <i class="bi bi-ticket-perforated"></i>
                                        {{ $selectedEventName }}
                                    </span>
                                @endif
                                @if ($queueCount > 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200/80 bg-indigo-600 px-2.5 py-1 text-[11px] font-semibold text-white shadow-md shadow-indigo-500/25">
                                        <i class="bi bi-inbox"></i>
                                        {{ number_format($queueCount) }} waiting
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="cro.dashboard.export.pdf"
                                filter-form-id="cro-dashboard-filters"
                                :params="request()->only(['event', 'from', 'to', 'range'])"
                                :charts="[
                                    ['canvasId' => 'croAttendanceBreakdownChart', 'title' => 'Attendance mix', 'section' => 'attendance'],
                                    ['canvasId' => 'croCheckInTimingChart', 'title' => 'Check-in timing', 'section' => 'attendance'],
                                    ['canvasId' => 'croAttendanceByEventChart', 'title' => 'Attendance by event', 'section' => 'attendance'],
                                    ['canvasId' => 'croSatisfactionDistributionChart', 'title' => 'Satisfaction distribution', 'section' => 'performance'],
                                    ['canvasId' => 'croSupportTrendChart', 'title' => 'Support trends', 'section' => 'support'],
                                    ['canvasId' => 'croComplaintStatusChart', 'title' => 'Complaint resolution status', 'section' => 'support'],
                                    ['canvasId' => 'croSupportCategoriesChart', 'title' => 'Feedback themes', 'section' => 'support'],
                                    ['canvasId' => 'inquiryStatusChart', 'title' => 'Inquiry status distribution', 'section' => 'inquiry'],
                                    ['canvasId' => 'inquiryResolutionTrendChart', 'title' => 'Inquiry vs resolution', 'section' => 'inquiry'],
                                    ['canvasId' => 'inquiryResponseTimeChart', 'title' => 'Average response time', 'section' => 'inquiry'],
                                    ['canvasId' => 'inquiryByEventChart', 'title' => 'Inquiries by event', 'section' => 'inquiry'],
                                    ['canvasId' => 'complaintCategoryPieChart', 'title' => 'Complaint categories', 'section' => 'complaints'],
                                    ['canvasId' => 'complaintSubmissionsChart', 'title' => 'Complaint submission trend', 'section' => 'complaints'],
                                    ['canvasId' => 'complaintTypeChart', 'title' => 'Complaints by type', 'section' => 'complaints'],
                                    ['canvasId' => 'complaintStatusByTypeChart', 'title' => 'Status by type', 'section' => 'complaints'],
                                ]"
                            />
                            <a href="{{ route('cro.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-xl border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-md hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/85 hover:text-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-sliders"></i>
                                Reports
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Today pulse --}}
            <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach ($pulseStats as $stat)
                    @php
                        $tone = match ($stat['tone']) {
                            'amber' => ['top' => 'border-t-amber-500', 'icon' => 'bg-amber-100/80 text-amber-700', 'glow' => 'hover:shadow-amber-500/15'],
                            'rose' => ['top' => 'border-t-rose-500', 'icon' => 'bg-rose-100/80 text-rose-700', 'glow' => 'hover:shadow-rose-500/15'],
                            'cyan' => ['top' => 'border-t-cyan-500', 'icon' => 'bg-cyan-100/80 text-cyan-700', 'glow' => 'hover:shadow-cyan-500/15'],
                            default => ['top' => 'border-t-indigo-500', 'icon' => 'bg-indigo-100/80 text-indigo-600', 'glow' => 'hover:shadow-indigo-500/15'],
                        };
                        $cardClass = "glass-card kpi-lift group border-t-4 {$tone['top']} {$tone['glow']} p-3.5 sm:p-4";
                    @endphp
                    @if ($stat['href'])
                        <a href="{{ $stat['href'] }}" class="{{ $cardClass }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                                </div>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tone['icon'] }} transition duration-300 group-hover:scale-110">
                                    <i class="bi {{ $stat['icon'] }}"></i>
                                </span>
                            </div>
                        </a>
                    @elseif (!empty($stat['scrollTo']))
                        <button type="button"
                            @click="setSection(@js($stat['section'] ?? 'today')); $nextTick(() => document.getElementById(@js($stat['scrollTo']))?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="{{ $cardClass }} text-left">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                                </div>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tone['icon'] }} transition duration-300 group-hover:scale-110">
                                    <i class="bi {{ $stat['icon'] }}"></i>
                                </span>
                            </div>
                        </button>
                    @else
                        <div class="{{ $cardClass }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stat['value']) }}</p>
                                </div>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tone['icon'] }} transition duration-300 group-hover:scale-110">
                                    <i class="bi {{ $stat['icon'] }}"></i>
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            {{-- Global filters --}}
            <section class="dashboard-filters relative overflow-hidden rounded-2xl border border-white/50 bg-white/40 px-4 py-3.5 shadow-lg shadow-indigo-500/10 backdrop-blur-2xl sm:px-5">
                <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full bg-indigo-300/20 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-1/4 h-24 w-24 rounded-full bg-cyan-300/15 blur-2xl"></div>

                <div class="relative mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-indigo-600 shadow-sm backdrop-blur-md">
                            <i class="bi bi-sliders text-sm"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold tracking-tight text-slate-900">Filters</h2>
                            <p class="text-xs text-slate-500">Assigned events only · one set for every tab</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($datePresets as $preset)
                            <a href="{{ route('cro.dashboard', array_merge($filterQueryBase, ['range' => $preset['key'], 'from' => $preset['from'], 'to' => $preset['to']])) }}"
                                @click.prevent="window.location.href = @js(route('cro.dashboard', array_merge($filterQueryBase, ['range' => $preset['key'], 'from' => $preset['from'], 'to' => $preset['to']]))) + '#' + section"
                                class="filter-chip btn-smooth inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold transition duration-200
                                    {{ $activeRange === $preset['key']
                                        ? 'border-indigo-500/80 bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                        : 'border-white/70 bg-white/45 text-slate-600 backdrop-blur-md hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm' }}">
                                {{ $preset['label'] }}
                            </a>
                        @endforeach
                        <span class="filter-chip inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold
                            {{ $activeRange === 'custom'
                                ? 'border-indigo-500/80 bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                : 'border-white/70 bg-white/40 text-slate-500 backdrop-blur-md' }}">
                            Custom
                        </span>
                        @if ($hasActiveFilters)
                            <a href="{{ route('cro.dashboard') }}"
                                @click.prevent="window.location.href = @js(route('cro.dashboard')) + '#' + section"
                                class="btn-smooth inline-flex items-center gap-1 rounded-full border border-rose-200/70 bg-rose-50/70 px-3 py-1.5 text-xs font-semibold text-rose-700 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-rose-100/80 hover:shadow-sm">
                                <i class="bi bi-x-circle"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <form id="cro-dashboard-filters" method="GET" action="{{ route('cro.dashboard') }}"
                    class="relative grid gap-3 lg:grid-cols-12 lg:items-end"
                    @submit="$el.action = '{{ route('cro.dashboard') }}' + '#' + section">
                    <input type="hidden" name="range" value="custom">
                    <div class="lg:col-span-5">
                        <label for="cro_event" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event</label>
                        <select id="cro_event" name="event"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                            <option value="">All assigned events</option>
                            @foreach ($filterOptions['events'] as $eventOption)
                                <option value="{{ $eventOption['id'] }}"
                                    @selected($selectedEventId === (int) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label for="cro_from" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" id="cro_from" name="from" value="{{ $activeFilters['from'] ?? $filters['from'] }}"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="cro_to" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" id="cro_to" name="to" value="{{ $activeFilters['to'] ?? $filters['to'] }}"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                    </div>
                    <div class="flex flex-wrap gap-2 lg:col-span-3">
                        <button type="submit"
                            class="btn-smooth inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-500/30">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>
            </section>

            {{-- Floating tabs --}}
            <div x-ref="tabSentinel" class="h-px w-full" aria-hidden="true"></div>
            <nav class="cro-floating-tabs sticky z-40"
                :class="tabsStuck && 'is-stuck'"
                aria-label="Dashboard sections">
                <div class="cro-floating-tabs-bar overflow-x-auto p-1.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-1 sm:min-w-0 sm:gap-1.5">
                        @foreach ($sectionTabs as $key => $tab)
                            <button type="button"
                                @click="setSection('{{ $key }}')"
                                class="cro-tab-btn btn-smooth group relative inline-flex flex-1 items-center justify-center gap-1.5 rounded-full px-3 py-2.5 text-xs font-semibold sm:gap-2 sm:px-4 sm:text-sm"
                                :class="section === '{{ $key }}'
                                    ? 'is-active'
                                    : 'is-idle'">
                                <i class="bi {{ $tab['icon'] }} transition duration-200 group-hover:scale-110"></i>
                                <span>{{ $tab['label'] }}</span>
                                @if (($tab['badge'] ?? 0) > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-bold"
                                        :class="section === '{{ $key }}' ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-700'">
                                        {{ $tab['badge'] > 99 ? '99+' : $tab['badge'] }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </nav>

            {{-- Tab panels --}}
            <div class="cro-dashboard-panels scroll-mt-[8.5rem] sm:scroll-mt-28">
                <div x-show="section === 'today'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-today')
                </div>
                <div x-show="section === 'attendance'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-attendance')
                </div>
                <div x-show="section === 'performance'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-performance')
                </div>
                <div x-show="section === 'support'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-support')
                </div>
                <div x-show="section === 'inquiry'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-inquiry')
                </div>
                <div x-show="section === 'complaints'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-complaints')
                </div>
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
                @click.stop
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
            .dashboard-filters {
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.7) inset,
                    0 18px 40px -20px rgba(79, 70, 229, 0.22);
            }
            .filter-control:hover {
                box-shadow: 0 8px 20px -12px rgba(79, 70, 229, 0.28);
            }
            .cro-floating-tabs {
                top: max(4.25rem, calc(env(safe-area-inset-top, 0px) + 4rem));
                margin-top: 0.15rem;
                padding: 0.35rem 0 0.55rem;
                background: linear-gradient(to bottom, rgba(248, 250, 252, 0.96), rgba(248, 250, 252, 0.78) 68%, transparent);
            }
            @media (min-width: 640px) {
                .cro-floating-tabs {
                    top: 4.5rem;
                }
            }
            .cro-floating-tabs.is-stuck .cro-floating-tabs-bar {
                background: rgba(255, 255, 255, 0.72);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.9) inset,
                    0 18px 40px -10px rgba(79, 70, 229, 0.38),
                    0 10px 24px -12px rgba(15, 23, 42, 0.22);
            }
            .cro-floating-tabs-bar {
                border-radius: 9999px;
                border: 1px solid rgba(255, 255, 255, 0.72);
                background: rgba(255, 255, 255, 0.48);
                backdrop-filter: blur(22px) saturate(1.35);
                -webkit-backdrop-filter: blur(22px) saturate(1.35);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.85) inset,
                    0 14px 40px -12px rgba(79, 70, 229, 0.28),
                    0 6px 18px -8px rgba(15, 23, 42, 0.12);
                transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
            }
            .cro-floating-tabs-bar:hover {
                background: rgba(255, 255, 255, 0.62);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.9) inset,
                    0 18px 44px -12px rgba(79, 70, 229, 0.32),
                    0 8px 20px -8px rgba(15, 23, 42, 0.14);
            }
            @media (max-width: 639px) {
                .cro-floating-tabs-bar {
                    border-radius: 1.35rem;
                }
            }
            .cro-tab-btn.is-idle {
                color: rgb(71 85 105);
            }
            .cro-tab-btn.is-idle:hover {
                transform: translateY(-2px);
                background: rgba(255, 255, 255, 0.82);
                color: rgb(15 23 42);
                box-shadow: 0 8px 18px -10px rgba(79, 70, 229, 0.35);
            }
            .cro-tab-btn.is-active {
                background: linear-gradient(180deg, #4f46e5 0%, #4338ca 100%);
                color: #fff;
                box-shadow:
                    0 10px 22px -8px rgba(79, 70, 229, 0.55),
                    0 1px 0 rgba(255, 255, 255, 0.25) inset;
            }
            .cro-tab-btn.is-active:hover {
                transform: translateY(-1px);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.croDashboardData = @json($dashboard);
            window.croReportData = @json($reports);
        </script>
        @vite(['resources/js/cro-dashboard.js', 'resources/js/cro-reports.js'])
    @endpush
</x-app-layout>

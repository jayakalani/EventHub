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

    <div class="cro-dashboard relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            open: false,
            chartKey: null,
            title: '',
            description: '',
            chartPeriod: @js($chartPeriod),
            section: (() => {
                const hash = (window.location.hash || '').replace('#', '');
                if (['cro-reports', 'reports', 'analytics', 'cro-insights', 'insights'].includes(hash)) return 'support';
                if (['today', 'performance', 'support', 'inquiry', 'complaints'].includes(hash)) return hash;
                return 'today';
            })(),
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
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/45 to-cyan-50/55"></div>
            <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-36 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-sky-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-50"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

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
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/30 blur-2xl"></div>

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
                                    <p class="truncate text-sm font-semibold text-slate-700">{{ $greeting }}, {{ $displayName }}</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">CRO Dashboard</h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Support queue, response health, and event insights · {{ now()->format('l, M j, Y') }}
                                @if ($selectedEventName)
                                    · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                                @endif
                                @if (($todayTasks['queueTotal'] ?? 0) > 0)
                                    · <span class="font-medium text-indigo-700">{{ number_format($todayTasks['queueTotal']) }} waiting</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="cro.dashboard.export.pdf"
                                :params="request()->only(['event', 'from', 'to', 'range'])"
                                :charts="[
                                    ['canvasId' => 'croSupportTrendChart', 'title' => 'Support Trends'],
                                    ['canvasId' => 'croComplaintStatusChart', 'title' => 'Complaint Resolution Status'],
                                    ['canvasId' => 'croSatisfactionDistributionChart', 'title' => 'Satisfaction Distribution'],
                                    ['canvasId' => 'croSupportCategoriesChart', 'title' => 'Feedback Themes'],
                                ]"
                            />
                            <a href="{{ route('cro.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-sliders"></i>
                                Export builder
                            </a>
                            <a href="{{ route('cro.inquiries.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-chat-dots"></i>
                                Inquiries
                            </a>
                        </div>
                    </div>

                    {{-- Today pulse --}}
                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>
                        <div class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($pulseStats as $stat)
                                @php
                                    $tone = match ($stat['tone']) {
                                        'amber' => ['chip' => 'bg-amber-50/70', 'icon' => 'bg-amber-100/80 text-amber-700'],
                                        'rose' => ['chip' => 'bg-rose-50/70', 'icon' => 'bg-rose-100/80 text-rose-700'],
                                        'cyan' => ['chip' => 'bg-cyan-50/70', 'icon' => 'bg-cyan-100/80 text-cyan-700'],
                                        default => ['chip' => 'bg-indigo-50/70', 'icon' => 'bg-indigo-100/80 text-indigo-600'],
                                    };
                                    $chipClass = "btn-smooth flex items-center gap-2 rounded-lg border border-white/50 {$tone['chip']} px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5";
                                @endphp
                                @if ($stat['href'])
                                    <a href="{{ $stat['href'] }}" class="{{ $chipClass }}">
                                        <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $tone['icon'] }} text-sm sm:flex">
                                            <i class="bi {{ $stat['icon'] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">{{ number_format($stat['value']) }}</p>
                                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $stat['label'] }}</p>
                                        </div>
                                    </a>
                                @elseif (!empty($stat['scrollTo']))
                                    <button type="button"
                                        @click="setSection(@js($stat['section'] ?? 'today')); $nextTick(() => document.getElementById(@js($stat['scrollTo']))?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                                        class="{{ $chipClass }} text-left">
                                        <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $tone['icon'] }} text-sm sm:flex">
                                            <i class="bi {{ $stat['icon'] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">{{ number_format($stat['value']) }}</p>
                                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $stat['label'] }}</p>
                                        </div>
                                    </button>
                                @else
                                    <div class="{{ $chipClass }}">
                                        <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $tone['icon'] }} text-sm sm:flex">
                                            <i class="bi {{ $stat['icon'] }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-900">{{ number_format($stat['value']) }}</p>
                                            <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $stat['label'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-wrap gap-1.5">
                        @foreach ([
                            ['label' => 'Complaints', 'route' => route('cro.complaints.index'), 'icon' => 'bi-exclamation-triangle'],
                            ['label' => 'Refunds', 'route' => route('cro.refund-requests.index'), 'icon' => 'bi-arrow-counterclockwise'],
                            ['label' => 'Reports', 'route' => route('cro.reports'), 'icon' => 'bi-file-earmark-bar-graph'],
                        ] as $shortcut)
                            <a href="{{ $shortcut['route'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 backdrop-blur-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm">
                                <i class="bi {{ $shortcut['icon'] }}"></i>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Global filters ABOVE tabs --}}
            <section class="dashboard-filters relative overflow-hidden rounded-2xl border border-white/50 bg-white/40 px-4 py-4 shadow-lg shadow-indigo-500/10 backdrop-blur-2xl sm:px-5">
                <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full bg-indigo-300/20 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-1/4 h-24 w-24 rounded-full bg-cyan-300/15 blur-2xl"></div>

                <div class="relative mb-3.5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
                                class="filter-chip btn-smooth inline-flex items-center rounded-xl border px-3 py-1.5 text-xs font-semibold transition duration-200
                                    {{ $activeRange === $preset['key']
                                        ? 'border-indigo-500/80 bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                        : 'border-white/70 bg-white/45 text-slate-600 backdrop-blur-md hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm' }}">
                                {{ $preset['label'] }}
                            </a>
                        @endforeach
                        <span class="filter-chip inline-flex items-center rounded-xl border px-3 py-1.5 text-xs font-semibold
                            {{ $activeRange === 'custom'
                                ? 'border-indigo-500/80 bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                : 'border-white/70 bg-white/40 text-slate-500 backdrop-blur-md' }}">
                            Custom
                        </span>
                        @if ($hasActiveFilters)
                            <a href="{{ route('cro.dashboard') }}"
                                @click.prevent="window.location.href = @js(route('cro.dashboard')) + '#' + section"
                                class="btn-smooth inline-flex items-center gap-1 rounded-xl border border-rose-200/70 bg-rose-50/70 px-3 py-1.5 text-xs font-semibold text-rose-700 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-rose-100/80 hover:shadow-sm">
                                <i class="bi bi-x-circle"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <form method="GET" action="{{ route('cro.dashboard') }}"
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

            {{-- Section tabs --}}
            <nav class="sticky top-16 z-30 sm:top-20" aria-label="Dashboard sections">
                <div class="segmented-control overflow-x-auto rounded-2xl border border-white/60 bg-white/55 p-1.5 shadow-lg shadow-indigo-500/5 backdrop-blur-2xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-1 sm:min-w-0 sm:grid sm:grid-cols-5">
                        @foreach ($sectionTabs as $key => $tab)
                            <button type="button"
                                @click="setSection('{{ $key }}')"
                                class="btn-smooth group relative inline-flex items-center justify-center gap-1.5 rounded-xl px-2.5 py-2.5 text-xs font-semibold transition sm:gap-2 sm:px-3 sm:text-sm"
                                :class="section === '{{ $key }}'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 hover:bg-white/80 hover:text-slate-900'">
                                <i class="bi {{ $tab['icon'] }}"></i>
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
            <div>
                <div x-show="section === 'today'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-today')
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
            .segmented-control {
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.65) inset,
                    0 10px 30px -12px rgba(79, 70, 229, 0.18);
            }
            .dashboard-filters {
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.7) inset,
                    0 18px 40px -20px rgba(79, 70, 229, 0.22);
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

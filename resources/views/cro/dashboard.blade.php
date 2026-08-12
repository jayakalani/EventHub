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
        $queueCount = count($todayWork);
        $priorityCount = count($dashboard['highPriority'] ?? []);
        $sectionTabs = [
            'today' => ['label' => 'Today', 'icon' => 'bi-lightning-charge', 'badge' => $queueCount + $priorityCount],
            'performance' => ['label' => 'Performance', 'icon' => 'bi-speedometer2', 'badge' => null],
            'insights' => ['label' => 'Insights', 'icon' => 'bi-graph-up', 'badge' => null],
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
                if (['cro-reports', 'reports', 'analytics', 'cro-insights'].includes(hash)) return 'insights';
                if (['today', 'performance', 'insights'].includes(hash)) return hash;
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
                    if (section === 'insights') {
                        window.dispatchEvent(new CustomEvent('cro-reports-tab-changed'));
                    }
                });
            },
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/35 to-cyan-50/45"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/15 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-sky-300/10 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-50"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            {{-- Hero --}}
            <section class="relative overflow-hidden rounded-3xl border border-white/30 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-5 shadow-xl shadow-indigo-500/20 sm:p-6">
                <div class="pointer-events-none absolute inset-0 opacity-15">
                    <div class="absolute -right-12 -top-12 h-56 w-56 rounded-full bg-white blur-2xl"></div>
                    <div class="absolute bottom-0 left-1/3 h-36 w-36 rounded-full bg-white blur-xl"></div>
                </div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            @if ($user?->profile_photo)
                                <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                    alt="{{ $displayName }}"
                                    class="h-11 w-11 rounded-full object-cover ring-2 ring-white/40 shadow-sm sm:h-12 sm:w-12">
                            @else
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white ring-2 ring-white/30 backdrop-blur sm:h-12 sm:w-12 sm:text-base">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-blue-100/90">{{ $greeting }}, {{ $displayName }}</p>
                                <h1 class="truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">CRO Dashboard</h1>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-blue-100/90">
                            {{ now()->format('l, M j, Y') }}
                            @if (($todayTasks['queueTotal'] ?? 0) > 0)
                                · {{ number_format($todayTasks['queueTotal']) }} items waiting
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-dashboard-export-pdf
                            route="cro.dashboard.export.pdf"
                            :params="request()->only(['event', 'from', 'to', 'range'])"
                            :charts="[
                                ['canvasId' => 'croSupportTrendChart', 'title' => 'Support Trends'],
                                ['canvasId' => 'croComplaintStatusChart', 'title' => 'Complaint Resolution Status'],
                                ['canvasId' => 'croSatisfactionDistributionChart', 'title' => 'Satisfaction Distribution'],
                                ['canvasId' => 'croSupportCategoriesChart', 'title' => 'Feedback Themes'],
                            ]"
                            class="!rounded-xl !border-white/30 !bg-white/10 !px-3.5 !py-2 !text-white hover:!bg-white/20"
                        />
                        <a href="{{ route('cro.inquiries.index') }}"
                            class="btn-smooth inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-indigo-600 shadow-lg hover:-translate-y-0.5 hover:shadow-xl">
                            <i class="bi bi-chat-dots"></i>
                            Inquiries
                        </a>
                        <a href="{{ route('cro.complaints.index') }}"
                            class="btn-smooth inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-3.5 py-2 text-sm font-semibold text-white backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/20">
                            <i class="bi bi-exclamation-triangle"></i>
                            Complaints
                        </a>
                        <a href="{{ route('cro.refund-requests.index') }}"
                            class="btn-smooth inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-3.5 py-2 text-sm font-semibold text-white backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/20">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Refunds
                        </a>
                    </div>
                </div>

                <div class="relative mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        ['label' => 'New Inquiries', 'value' => $todayTasks['newInquiries'], 'href' => route('cro.inquiries.index'), 'icon' => 'bi-envelope'],
                        ['label' => 'Refunds', 'value' => $todayTasks['refundRequests'], 'href' => route('cro.refund-requests.index'), 'icon' => 'bi-arrow-counterclockwise'],
                        ['label' => 'Urgent', 'value' => $todayTasks['urgentComplaints'], 'href' => route('cro.complaints.index'), 'icon' => 'bi-exclamation-octagon'],
                        ['label' => 'Events', 'value' => $todayTasks['eventsToday'], 'href' => null, 'icon' => 'bi-calendar-event'],
                    ] as $task)
                        @php $chipClass = 'btn-smooth flex items-center gap-2.5 rounded-2xl border border-white/20 bg-white/10 px-3 py-2.5 backdrop-blur-md hover:-translate-y-0.5 hover:bg-white/20'; @endphp
                        @if ($task['href'])
                            <a href="{{ $task['href'] }}" class="{{ $chipClass }}">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15 text-white">
                                    <i class="bi {{ $task['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-lg font-bold leading-none text-white">{{ number_format($task['value']) }}</p>
                                    <p class="mt-1 truncate text-[11px] font-medium text-blue-100">{{ $task['label'] }}</p>
                                </div>
                            </a>
                        @else
                            <div class="{{ $chipClass }}">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15 text-white">
                                    <i class="bi {{ $task['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-lg font-bold leading-none text-white">{{ number_format($task['value']) }}</p>
                                    <p class="mt-1 truncate text-[11px] font-medium text-blue-100">{{ $task['label'] }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>

            {{-- Segmented control --}}
            <nav class="sticky top-16 z-30 sm:top-20" aria-label="Dashboard sections">
                <div class="segmented-control overflow-x-auto rounded-2xl border border-white/60 bg-white/55 p-1.5 shadow-lg shadow-indigo-500/5 backdrop-blur-2xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-1 sm:min-w-0 sm:grid sm:grid-cols-3">
                        @foreach ($sectionTabs as $key => $tab)
                            <button type="button"
                                @click="setSection('{{ $key }}')"
                                class="btn-smooth group relative inline-flex items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition"
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

            {{-- Filters --}}
            <section class="glass-panel !rounded-2xl px-4 py-3.5 sm:px-5">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Filters</h2>
                        <p class="text-xs text-slate-500">Your assigned events only · applies across all tabs</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($datePresets as $preset)
                            <a href="{{ route('cro.dashboard', array_merge($filterQueryBase, ['range' => $preset['key'], 'from' => $preset['from'], 'to' => $preset['to']])) }}"
                                class="btn-smooth inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                    {{ $activeRange === $preset['key']
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'border border-white/70 bg-white/50 text-slate-600 hover:-translate-y-0.5 hover:bg-white/90' }}">
                                {{ $preset['label'] }}
                            </a>
                        @endforeach
                        <span class="inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold
                            {{ $activeRange === 'custom'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'border border-white/70 bg-white/40 text-slate-500' }}">
                            Custom
                        </span>
                    </div>
                </div>
                <form method="GET" action="{{ route('cro.dashboard') }}" class="grid gap-3 lg:grid-cols-12 lg:items-end"
                    @submit="$el.action = '{{ route('cro.dashboard') }}' + '#' + section">
                    <input type="hidden" name="range" value="custom">
                    <div class="lg:col-span-4">
                        <label for="cro_event" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event</label>
                        <select id="cro_event" name="event"
                            class="w-full rounded-xl border border-white/70 bg-white/70 px-3 py-2 text-sm text-slate-800 shadow-sm backdrop-blur focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="">All assigned events</option>
                            @foreach ($filterOptions['events'] as $eventOption)
                                <option value="{{ $eventOption['id'] }}"
                                    @selected((int) ($activeFilters['event'] ?? $eventFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label for="cro_from" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" id="cro_from" name="from" value="{{ $activeFilters['from'] ?? $filters['from'] }}"
                            class="w-full rounded-xl border border-white/70 bg-white/70 px-3 py-2 text-sm text-slate-800 shadow-sm backdrop-blur focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div class="lg:col-span-3">
                        <label for="cro_to" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" id="cro_to" name="to" value="{{ $activeFilters['to'] ?? $filters['to'] }}"
                            class="w-full rounded-xl border border-white/70 bg-white/70 px-3 py-2 text-sm text-slate-800 shadow-sm backdrop-blur focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div class="flex flex-wrap gap-2 lg:col-span-2">
                        <button type="submit"
                            class="btn-smooth inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-md">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                        <a href="{{ route('cro.dashboard') }}"
                            class="btn-smooth inline-flex items-center justify-center rounded-xl border border-white/70 bg-white/50 px-3 py-2 text-sm font-semibold text-slate-700 hover:-translate-y-0.5 hover:bg-white/90">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 backdrop-blur">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200/80 bg-rose-50/80 px-4 py-3 text-sm text-rose-800 backdrop-blur">{{ $errors->first() }}</div>
            @endif

            {{-- Tab panels --}}
            <div>
                <div x-show="section === 'today'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-today')
                </div>
                <div x-show="section === 'performance'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-performance')
                </div>
                <div x-show="section === 'insights'" x-cloak x-transition.opacity.duration.200ms>
                    @include('cro.partials.dashboard-tab-insights')
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

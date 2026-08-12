<x-app-layout>
    @php
        $stats = $dashboard['stats'];
        $todaySummary = $dashboard['todaySummary'];
        $dayOfOps = $dashboard['dayOfOps'] ?? [
            'active' => false,
            'count' => 0,
            'checked_in' => 0,
            'sold' => 0,
            'rate' => 0,
            'scan_url' => route('organizer.bookings.scan'),
            'guest_list_url' => route('organizer.bookings.index'),
            'events' => [],
        ];
        $needsAttention = $dashboard['needsAttention'] ?? ['count' => 0, 'items' => []];
        $focusFilter = $dashboard['focusFilter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => []];
        $filterQuery = $dashboard['filterQuery'] ?? [];
        $kpis = $dashboard['kpis'];
        $kpiFilter = $dashboard['kpiFilter'] ?? ['selectedEventId' => null, 'selectedEventName' => null, 'events' => [], 'isOverride' => false];
        $revenueGoal = $dashboard['revenueGoal'];
        $statusSummary = $dashboard['statusSummary'];
        $performance = $dashboard['performance'];
        $performanceCompleted = $dashboard['performanceCompleted'] ?? [];
        $upcomingEvents = $dashboard['upcomingEvents'];
        $nextUpcomingEvent = $dashboard['nextUpcomingEvent'] ?? null;
        $recentPurchases = $dashboard['recentPurchases'];
        $recentActivity = $dashboard['recentActivity'];
        $onboarding = $dashboard['onboarding'] ?? ['show' => false, 'steps' => [], 'completed_count' => 0, 'total' => 4];
        $livePulseUrl = $dashboard['livePulseUrl'] ?? route('organizer.dashboard.live');
        $totalEvents = max(1, $stats['totalEvents']);
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'there';
        $initials = strtoupper(substr($user?->first_name ?? 'O', 0, 1) . substr($user?->last_name ?? '', 0, 1));
        $focusEvents = $focusFilter['events'] ?? $kpiFilter['events'] ?? [];
        $tab = $tab ?? 'revenue';
        $loadedTabs = $loadedTabs ?? [$tab];
        $reports = $reports ?? [];

        $activeFilters = $reports['filters'] ?? ['from' => null, 'to' => null, 'event_id' => null, 'status' => null];
        $filterOptions = $reports['filterOptions'] ?? ['events' => [], 'statuses' => []];
        $hasActiveFilters = filled($activeFilters['from'] ?? null)
            || filled($activeFilters['to'] ?? null)
            || filled($activeFilters['event_id'] ?? null)
            || filled($activeFilters['status'] ?? null)
            || filled($focusFilter['selectedEventId'] ?? null);

        $datePresets = [
            [
                'key' => '7d',
                'label' => 'Last 7 days',
                'from' => now()->subDays(6)->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => '30d',
                'label' => 'Last 30 days',
                'from' => now()->subDays(29)->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'month',
                'label' => 'This month',
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'last_month',
                'label' => 'Last month',
                'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            [
                'key' => 'year',
                'label' => 'This year',
                'from' => now()->startOfYear()->toDateString(),
                'to' => now()->toDateString(),
            ],
            [
                'key' => 'all',
                'label' => 'All time',
                'from' => null,
                'to' => null,
            ],
        ];
        $activePreset = collect($datePresets)->first(function (array $preset) use ($activeFilters) {
            if (($preset['key'] ?? '') === 'all') {
                return blank($activeFilters['from'] ?? null) && blank($activeFilters['to'] ?? null);
            }

            return ($activeFilters['from'] ?? null) === ($preset['from'] ?? null)
                && ($activeFilters['to'] ?? null) === ($preset['to'] ?? null);
        });
        $activePresetKey = $activePreset['key'] ?? null;

        $filterQueryBase = array_filter([
            'focus_event' => $focusFilter['selectedEventId'] ?? null,
            'status' => $activeFilters['status'] ?? null,
            'tab' => $tab,
        ], fn ($value) => filled($value));

        $analyticsTabs = [
            'revenue' => ['label' => 'Revenue', 'icon' => 'bi-cash-stack'],
            'tickets' => ['label' => 'Tickets', 'icon' => 'bi-ticket-perforated'],
            'events' => ['label' => 'Events', 'icon' => 'bi-calendar-event'],
            'attendance' => ['label' => 'Attendance', 'icon' => 'bi-person-check'],
            'audience' => ['label' => 'Audience', 'icon' => 'bi-people'],
            'engagement' => ['label' => 'Engagement', 'icon' => 'bi-heart'],
            'activity' => ['label' => 'Activity', 'icon' => 'bi-activity'],
        ];
        $sectionTabs = array_merge(
            ['performance' => ['label' => 'Performance', 'icon' => 'bi-speedometer2', 'badge' => null]],
            collect($analyticsTabs)->map(fn ($tabMeta) => $tabMeta + ['badge' => null])->all()
        );
        $analyticsTabKeys = array_keys($analyticsTabs);
    @endphp

    <div class="organizer-dashboard relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            analyticsTabs: @js($analyticsTabKeys),
            loadedTabs: @js($loadedTabs),
            section: (() => {
                const analytics = @js($analyticsTabKeys);
                const hash = (window.location.hash || '').replace('#', '');
                if (hash === 'today' || hash === 'insights' || hash === 'reports') {
                    return 'performance';
                }
                if (hash === 'performance' || analytics.includes(hash)) {
                    return hash;
                }
                const tab = new URLSearchParams(window.location.search).get('tab');
                if (analytics.includes(tab)) {
                    return tab;
                }
                return 'performance';
            })(),
            buildAnalyticsUrl(id) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', id);
                url.hash = id;
                const form = document.getElementById('organizer-reports-filters');
                if (form) {
                    const formData = new FormData(form);
                    ['from', 'to', 'status', 'focus_event'].forEach((key) => {
                        const value = formData.get(key);
                        if (value) {
                            url.searchParams.set(key, String(value));
                        } else {
                            url.searchParams.delete(key);
                        }
                    });
                    const focus = formData.get('focus_event');
                    if (focus) {
                        url.searchParams.set('event_id', String(focus));
                    } else {
                        url.searchParams.delete('event_id');
                    }
                }
                return url;
            },
            setSection(section) {
                if (section === 'performance') {
                    this.section = 'performance';
                    history.replaceState(null, '', '#performance');
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('organizer-dashboard-section-changed', { detail: { section } }));
                    });
                    return;
                }

                if (! this.analyticsTabs.includes(section)) {
                    return;
                }

                if (this.loadedTabs.includes(section)) {
                    this.section = section;
                    const url = this.buildAnalyticsUrl(section);
                    history.replaceState(null, '', url.pathname + url.search + '#' + section);
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent('organizer-dashboard-section-changed', { detail: { section } }));
                        window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed', { detail: { tab: section } }));
                    });
                    return;
                }

                window.location.assign(this.buildAnalyticsUrl(section).toString());
            },
        }"
        @organizer-open-performance.window="setSection('performance')">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/45 to-cyan-50/55"></div>
            <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-36 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-24 left-1/3 h-64 w-64 rounded-full bg-emerald-300/15 blur-3xl"></div>
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
                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $greeting }}, {{ $displayName }}
                                    </p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Organizer Dashboard
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Events, ticket sales, and revenue · {{ now()->format('l, M j, Y') }}
                                @if ($focusFilter['selectedEventName'] ?? null)
                                    · <span class="font-medium text-slate-700">{{ $focusFilter['selectedEventName'] }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="organizer.dashboard.export.pdf"
                                :params="$filterQuery"
                                :charts="[]"
                            />
                            <a href="{{ route('organizer.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-sliders"></i>
                                Reports
                            </a>
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4"
                        data-live-today>
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>

                        <div @class([
                            'grid min-w-0 flex-1 gap-2',
                            'grid-cols-2 sm:grid-cols-4' => $dayOfOps['active'] ?? false,
                            'grid-cols-3' => ! ($dayOfOps['active'] ?? false),
                        ])>
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-indigo-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-indigo-100/80 text-sm text-indigo-600 sm:flex">
                                    <i class="bi bi-calendar-event"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" data-live="eventsToday">{{ number_format($todaySummary['eventsToday']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Events</p>
                                </div>
                            </div>
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-blue-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-blue-100/80 text-sm text-blue-600 sm:flex">
                                    <i class="bi bi-ticket-perforated"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" data-live="ticketsSold">{{ number_format($todaySummary['ticketsSold']) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Tickets</p>
                                </div>
                            </div>
                            <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 bg-emerald-50/60 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-emerald-100/80 text-sm text-emerald-600 sm:flex">
                                    <i class="bi bi-cash-stack"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900" data-live="revenue">LKR {{ number_format($todaySummary['revenue'], 0) }}</p>
                                    <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">Revenue</p>
                                </div>
                            </div>
                            @if ($dayOfOps['active'] ?? false)
                                <div class="btn-smooth flex items-center gap-2 rounded-lg border border-cyan-200/70 bg-cyan-50/70 px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5"
                                    data-live-checkin>
                                    <span class="hidden h-7 w-7 items-center justify-center rounded-md bg-cyan-100/80 text-sm text-cyan-700 sm:flex">
                                        <i class="bi bi-person-check-fill"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900" data-live="checkinRatio">
                                            {{ number_format($dayOfOps['checked_in']) }}/{{ number_format($dayOfOps['sold']) }}
                                        </p>
                                        <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs" data-live="checkinRate">
                                            Check-in · {{ $dayOfOps['rate'] }}%
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-wrap gap-1.5">
                        @foreach ([
                            ['label' => 'Scan tickets', 'route' => $dayOfOps['scan_url'] ?? route('organizer.bookings.scan'), 'icon' => 'bi-qr-code-scan', 'emphasis' => true],
                            ['label' => 'Guest list', 'route' => $dayOfOps['guest_list_url'] ?? route('organizer.bookings.index'), 'icon' => 'bi-people', 'emphasis' => true],
                        ] as $shortcut)
                            <a href="{{ $shortcut['route'] }}"
                                @class([
                                    'btn-smooth inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold backdrop-blur-sm hover:-translate-y-0.5 hover:shadow-sm',
                                    'border border-cyan-300/80 bg-cyan-50/80 text-cyan-800 hover:border-cyan-400 hover:bg-cyan-100/90' => ! empty($shortcut['emphasis']),
                                    'border border-white/70 bg-white/50 text-slate-600 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700' => empty($shortcut['emphasis']),
                                ])>
                                <i class="bi {{ $shortcut['icon'] }}"></i>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Shared filters (above tabs — applies to every section) --}}
            <section class="dashboard-filters relative overflow-hidden rounded-2xl border border-white/50 bg-white/40 px-4 py-4 shadow-lg shadow-indigo-500/10 backdrop-blur-2xl sm:px-5 sm:py-4.5">
                <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full bg-indigo-300/20 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-1/4 h-24 w-24 rounded-full bg-cyan-300/15 blur-2xl"></div>

                <div class="relative mb-3.5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-indigo-600 shadow-sm backdrop-blur-md">
                                <i class="bi bi-sliders text-sm"></i>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold tracking-tight text-slate-900">Filters</h2>
                                <p class="text-xs text-slate-500">One set for the whole dashboard · keeps every tab in sync</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($datePresets as $preset)
                            @php
                                $presetQuery = $filterQueryBase;
                                if (filled($preset['from'] ?? null)) {
                                    $presetQuery['from'] = $preset['from'];
                                }
                                if (filled($preset['to'] ?? null)) {
                                    $presetQuery['to'] = $preset['to'];
                                }
                            @endphp
                            <a href="{{ route('organizer.dashboard', $presetQuery) }}"
                                @click.prevent="window.location.href = @js(route('organizer.dashboard', $presetQuery)) + '#' + section"
                                class="filter-chip btn-smooth inline-flex items-center rounded-xl border px-3 py-1.5 text-xs font-semibold transition duration-200
                                    {{ $activePresetKey === $preset['key']
                                        ? 'border-indigo-500/80 bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                        : 'border-white/70 bg-white/45 text-slate-600 backdrop-blur-md hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm' }}">
                                {{ $preset['label'] }}
                            </a>
                        @endforeach
                        @if ($hasActiveFilters)
                            <a href="{{ route('organizer.dashboard') }}"
                                @click.prevent="window.location.href = @js(route('organizer.dashboard')) + '#' + section"
                                class="btn-smooth inline-flex items-center gap-1 rounded-xl border border-rose-200/70 bg-rose-50/70 px-3 py-1.5 text-xs font-semibold text-rose-700 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-rose-100/80 hover:shadow-sm">
                                <i class="bi bi-x-circle"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <form id="organizer-reports-filters" method="GET" action="{{ route('organizer.dashboard') }}"
                    class="relative grid gap-3 lg:grid-cols-12 lg:items-end"
                    @submit="$el.action = '{{ route('organizer.dashboard') }}' + '#' + section">
                    <input type="hidden" name="tab" :value="analyticsTabs.includes(section) ? section : @js($tab)">
                    <div class="lg:col-span-4">
                        <label for="focus_event" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event</label>
                        <select id="focus_event" name="focus_event"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                            <option value="">All events</option>
                            @foreach ($focusEvents as $eventOption)
                                <option value="{{ $eventOption['id'] }}"
                                    @selected((int) ($focusFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label for="from" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">From</label>
                        <input type="date" id="from" name="from" value="{{ $activeFilters['from'] }}"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="to" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">To</label>
                        <input type="date" id="to" name="to" value="{{ $activeFilters['to'] }}"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                            <option value="">All</option>
                            @foreach ($filterOptions['statuses'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected(($activeFilters['status'] ?? '') === $statusOption)>
                                    {{ ucfirst($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:col-span-2">
                        <button type="submit"
                            class="btn-smooth inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-500/30">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>
            </section>

            {{-- Segmented control --}}
            <nav class="sticky top-16 z-30 sm:top-20" aria-label="Dashboard sections">
                <div class="segmented-control overflow-x-auto rounded-2xl border border-white/60 bg-white/55 p-1.5 shadow-lg shadow-indigo-500/5 backdrop-blur-2xl [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-1">
                        @foreach ($sectionTabs as $key => $sectionTab)
                            <button type="button"
                                @click="setSection('{{ $key }}')"
                                class="btn-smooth group relative inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-semibold transition duration-200 sm:gap-2 sm:px-3.5 sm:text-sm"
                                :class="section === '{{ $key }}'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white/80 hover:text-slate-900 hover:shadow-sm'">
                                <i class="bi {{ $sectionTab['icon'] }} transition group-hover:scale-110"></i>
                                <span>{{ $sectionTab['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </nav>

            {{-- Tab panels --}}
            <div>
                <div x-show="section === 'performance'" x-cloak x-transition.opacity.duration.200ms class="space-y-5">
                    @include('organizer.partials.dashboard-tab-today')
                    @include('organizer.partials.dashboard-tab-performance')
                </div>
                <div x-show="analyticsTabs.includes(section)" x-cloak x-transition.opacity.duration.200ms>
                    @include('organizer.partials.insights')
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
                    0 12px 36px -16px rgba(79, 70, 229, 0.22);
            }
            .filter-control:hover {
                box-shadow: 0 8px 20px -12px rgba(79, 70, 229, 0.28);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.organizerDashboardData = @json($dashboard);
            window.organizerDashboardLiveUrl = @json($livePulseUrl);
            window.organizerReportData = @json($reports);
            (function () {
                var key = 'organizer-dashboard-scroll';

                function currentScrollY() {
                    return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                }

                function restoreScroll(y) {
                    window.scrollTo(0, y);
                }

                try {
                    var saved = sessionStorage.getItem(key);
                    if (saved !== null) {
                        sessionStorage.removeItem(key);
                        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
                        var y = Number(saved);
                        if (Number.isFinite(y)) {
                            restoreScroll(y);
                            requestAnimationFrame(function () { restoreScroll(y); });
                            window.addEventListener('load', function () { restoreScroll(y); }, { once: true });
                            setTimeout(function () { restoreScroll(y); }, 50);
                            setTimeout(function () { restoreScroll(y); }, 250);
                            setTimeout(function () { restoreScroll(y); }, 600);
                        }
                    }
                } catch (e) {}

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.organizer-dashboard select[name="focus_event"]').forEach(function (select) {
                        select.addEventListener('change', function () {
                            try {
                                sessionStorage.setItem(key, String(currentScrollY()));
                            } catch (e) {}

                            if (this.name === 'focus_event' && !this.value) {
                                this.removeAttribute('name');
                            }

                            if (this.form) {
                                var section = 'performance';
                                try {
                                    var hash = (window.location.hash || '').replace('#', '');
                                    var analytics = @json($analyticsTabKeys);
                                    if (hash === 'performance' || analytics.includes(hash)) {
                                        section = hash;
                                    }
                                } catch (e) {}
                                this.form.action = @js(route('organizer.dashboard')) + '#' + section;
                                this.form.submit();
                            }
                        });
                    });
                });
            })();
        </script>
        @vite(['resources/js/organizer-dashboard.js', 'resources/js/organizer-reports.js'])
    @endpush
</x-app-layout>

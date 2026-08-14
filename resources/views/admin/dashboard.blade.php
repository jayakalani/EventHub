<x-app-layout>
    @php
        $users = $dashboard['users'];
        $events = $dashboard['events'];
        $tickets = $dashboard['tickets'];
        $revenue = $dashboard['revenue'];
        $payments = $dashboard['payments'];
        $support = $dashboard['support'];
        $todaySummary = $dashboard['todaySummary'];
        $organizerPerformance = $dashboard['organizerPerformance'];
        $platformAnalytics = $dashboard['platformAnalytics'];
        $attentionQueue = $dashboard['attentionQueue'] ?? ['count' => 0, 'items' => []];
        $scopeFilter = $dashboard['scopeFilter'] ?? [
            'scope' => 'global',
            'organizers' => [],
            'events' => [],
            'selectedOrganizerId' => null,
            'selectedOrganizerName' => null,
            'selectedEventId' => null,
            'selectedEventName' => null,
        ];
        $paymentScopeFilter = $dashboard['paymentScopeFilter'] ?? $scopeFilter;
        $supportScopeFilter = $dashboard['supportScopeFilter'] ?? [
            'scope' => 'global',
            'cros' => [],
            'events' => [],
            'selectedCroId' => null,
            'selectedCroName' => null,
            'selectedEventId' => null,
            'selectedEventName' => null,
        ];
        $kpis = $dashboard['kpis'] ?? [];
        $user = Auth::user();
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = $user?->first_name ?: 'Admin';
        $initials = strtoupper(substr($user?->first_name ?? 'A', 0, 1) . substr($user?->last_name ?? '', 0, 1));
        $totalUsersForRoles = max(1, (int) $users['total']);
        $rolePercents = collect($users['byRole'])->map(function ($role) use ($totalUsersForRoles) {
            return [
                'label' => $role['label'],
                'count' => $role['count'],
                'percent' => round(($role['count'] / $totalUsersForRoles) * 100, 1),
            ];
        });
        $scopeCaption = match ($scopeFilter['scope'] ?? 'global') {
            'event' => 'Event totals for '.$scopeFilter['selectedEventName'],
            'organizer' => 'Organizer totals for '.$scopeFilter['selectedOrganizerName'],
            default => 'Platform-wide overview',
        };
        $paymentScopeCaption = match ($paymentScopeFilter['scope'] ?? 'global') {
            'event' => 'Payments for '.$paymentScopeFilter['selectedEventName'],
            'organizer' => 'Payments for '.$paymentScopeFilter['selectedOrganizerName'],
            default => 'Transaction status mix',
        };
        $supportScopeCaption = match ($supportScopeFilter['scope'] ?? 'global') {
            'event' => 'Support for '.($supportScopeFilter['selectedEventName'] ?? 'selected event'),
            'organizer' => 'Support for '.($supportScopeFilter['selectedOrganizerName'] ?? 'selected organizer'),
            'cro' => 'Assigned to '.($supportScopeFilter['selectedCroName'] ?? 'selected CRO'),
            default => 'CRO module',
        };

        $analyticsTabs = [
            'overview' => ['label' => 'Overview', 'icon' => 'bi-grid-1x2'],
            'activity' => ['label' => 'Activity', 'icon' => 'bi-activity'],
            'events' => ['label' => 'Events', 'icon' => 'bi-calendar-event'],
            'users' => ['label' => 'Users', 'icon' => 'bi-people'],
            'payments' => ['label' => 'Payments', 'icon' => 'bi-cash-stack'],
        ];
        $sectionTabs = array_merge(
            [
                'performance' => ['label' => 'Performance', 'icon' => 'bi-speedometer2'],
                'support' => ['label' => 'Support', 'icon' => 'bi-headset'],
            ],
            $analyticsTabs
        );
        $analyticsTabKeys = array_keys($analyticsTabs);
        $selectedCroId = $supportScopeFilter['selectedCroId']
            ?? ($supportReport['selectedCroId'] ?? null);
        $croOptions = $supportScopeFilter['cros']
            ?? ($supportReport['cros'] ?? []);
        $hasActiveFilters = filled($scopeFilter['selectedOrganizerId'] ?? null)
            || filled($scopeFilter['selectedEventId'] ?? null)
            || filled($selectedCroId);
        $loadInsights = $loadInsights ?? false;
        $loadSupport = $loadSupport ?? false;
        $filterQuery = array_filter([
            'organizer' => $scopeFilter['selectedOrganizerId'] ?? null,
            'event' => $scopeFilter['selectedEventId'] ?? null,
            'cro' => $selectedCroId,
        ], fn ($value) => filled($value));
    @endphp

    <div class="admin-dashboard relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            open: false,
            chartKey: null,
            title: '',
            description: '',
            analyticsTabs: @js($analyticsTabKeys),
            insightsLoaded: @js($loadInsights),
            insightsLoading: false,
            supportLoaded: @js($loadSupport),
            supportLoading: false,
            autoApply: true,
            sectionStorageKey: 'admin-dashboard-section',
            autoApplyStorageKey: 'admin-dashboard-auto-apply',
            resolveSection() {
                const allowed = ['performance', 'support'].concat(this.analyticsTabs);
                const map = {
                    insights: 'overview',
                    reports: 'overview',
                    admin: 'events',
                    'support-reports': 'support',
                };
                const normalize = (value) => {
                    if (! value) return null;
                    const mapped = map[value] || value;
                    return allowed.includes(mapped) ? mapped : null;
                };

                const fromHash = normalize((window.location.hash || '').replace('#', ''));
                if (fromHash) return fromHash;

                try {
                    const fromStore = normalize(localStorage.getItem(this.sectionStorageKey));
                    if (fromStore) return fromStore;
                } catch (e) {}

                return 'performance';
            },
            section: 'performance',
            rememberSection(section) {
                try {
                    localStorage.setItem(this.sectionStorageKey, section);
                } catch (e) {}
            },
            deferredTabUrl(section, flag) {
                const url = new URL(@js(route('dashboard')), window.location.origin);
                const params = @js($filterQuery);
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        url.searchParams.set(key, String(value));
                    }
                });
                url.searchParams.set(flag, '1');
                if (flag !== 'support' && this.supportLoaded) {
                    url.searchParams.set('support', '1');
                }
                if (flag !== 'insights' && this.insightsLoaded) {
                    url.searchParams.set('insights', '1');
                }
                url.searchParams.set('section', section);
                url.hash = section;
                return url;
            },
            insightsUrl(section) {
                return this.deferredTabUrl(section, 'insights');
            },
            supportUrl() {
                return this.deferredTabUrl('support', 'support');
            },
            setSection(section) {
                const map = { admin: 'events', 'support-reports': 'support' };
                section = map[section] || section;

                if (section === 'support' && ! this.supportLoaded) {
                    this.rememberSection(section);
                    this.supportLoading = true;
                    window.location.assign(this.supportUrl().toString());
                    return;
                }

                if (this.analyticsTabs.includes(section) && ! this.insightsLoaded) {
                    this.rememberSection(section);
                    this.insightsLoading = true;
                    window.location.assign(this.insightsUrl(section).toString());
                    return;
                }

                this.section = section;
                this.rememberSection(section);
                history.replaceState(null, '', '#' + section);
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('admin-dashboard-section-changed', { detail: { section } }));
                    if (this.analyticsTabs.includes(section)) {
                        window.dispatchEvent(new CustomEvent('admin-reports-tab-changed', { detail: { tab: section } }));
                    }
                });
            },
            openChart(key, title, description) {
                this.chartKey = key;
                this.title = title;
                this.description = description;
                this.open = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('admin-chart-expand', {
                        detail: { key },
                    }));
                });
            },
            closeChart() {
                this.open = false;
                this.chartKey = null;
                document.body.classList.remove('overflow-hidden');
                window.dispatchEvent(new CustomEvent('admin-chart-collapse'));
            },
            init() {
                try {
                    const storedAuto = localStorage.getItem(this.autoApplyStorageKey);
                    if (storedAuto === '0' || storedAuto === 'false') {
                        this.autoApply = false;
                    }
                } catch (e) {}

                this.section = this.resolveSection();
                this.rememberSection(this.section);
                if (! (window.location.hash || '').replace('#', '')) {
                    history.replaceState(null, '', '#' + this.section);
                }

                if (this.section === 'support' && ! this.supportLoaded) {
                    this.supportLoading = true;
                    window.location.replace(this.supportUrl().toString());
                    return;
                }
                if (this.analyticsTabs.includes(this.section) && ! this.insightsLoaded) {
                    this.insightsLoading = true;
                    window.location.replace(this.insightsUrl(this.section).toString());
                }
            },
            toggleAutoApply() {
                this.autoApply = ! this.autoApply;
                try {
                    localStorage.setItem(this.autoApplyStorageKey, this.autoApply ? '1' : '0');
                } catch (e) {}
            },
        }"
        x-init="init()"
        @keydown.escape.window="if (open) closeChart()"
        @admin-open-overview.window="setSection('overview')"
        @admin-open-section.window="setSection($event.detail.section)">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Hero --}}
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
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-xs font-bold text-white shadow-sm ring-2 ring-white/70 backdrop-blur sm:h-10 sm:w-10 sm:text-sm">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $greeting }}, {{ $displayName }}
                                    </p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Administrator Dashboard
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">
                                Users, events, revenue, and support · {{ now()->format('l, M j, Y') }}
                                @if ($scopeFilter['selectedOrganizerName'] ?? null)
                                    · <span class="font-medium text-slate-700">{{ $scopeFilter['selectedOrganizerName'] }}</span>
                                @endif
                                @if ($scopeFilter['selectedEventName'] ?? null)
                                    · <span class="font-medium text-slate-700">{{ $scopeFilter['selectedEventName'] }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="admin.dashboard.export.pdf"
                                :params="request()->only(['organizer', 'event', 'cro'])"
                                :charts="[
                                    ['canvasId' => 'dashboardPaymentOverviewChart', 'title' => 'Payment Overview'],
                                    ['canvasId' => 'dashboardUserDistributionChart', 'title' => 'User Distribution'],
                                    ['canvasId' => 'dashboardEventsByCategoryChart', 'title' => 'Events by Category'],
                                ]"
                            />
                            <a href="{{ route('admin.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-sliders"></i>
                                Exports
                            </a>
                            
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-col gap-2 rounded-xl border border-white/70 bg-white/45 px-3 py-2.5 shadow-sm backdrop-blur-md sm:flex-row sm:items-center sm:gap-4 sm:px-4">
                        <div class="shrink-0 sm:border-r sm:border-slate-200/60 sm:pr-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Today</p>
                            <p class="text-xs text-slate-500">{{ now()->format('D, M j') }}</p>
                        </div>
                        @php
                            $todayDate = now()->toDateString();
                            $todayTiles = [
                                [
                                    'label' => 'Organizers',
                                    'value' => $todaySummary['newOrganizers'],
                                    'hint' => 'New organizer accounts today',
                                    'icon' => 'bi-person-badge',
                                    'bg' => 'bg-indigo-50/60',
                                    'iconBg' => 'bg-indigo-100/80',
                                    'iconColor' => 'text-indigo-600',
                                    'href' => route('admin.users', [
                                        'role' => \App\Models\UserRole::ORGANIZER,
                                        'from_date' => $todayDate,
                                        'to_date' => $todayDate,
                                    ]),
                                    'section' => null,
                                ],
                                [
                                    'label' => 'Events',
                                    'value' => $todaySummary['newEvents'],
                                    'hint' => 'Events created today',
                                    'icon' => 'bi-calendar-event',
                                    'bg' => 'bg-blue-50/60',
                                    'iconBg' => 'bg-blue-100/80',
                                    'iconColor' => 'text-blue-600',
                                    'href' => route('admin.audit-logs', [
                                        'model_type' => \App\Models\Event::class,
                                        'action' => 'Created',
                                        'from_date' => $todayDate,
                                        'to_date' => $todayDate,
                                    ]),
                                    'section' => null,
                                ],
                                [
                                    'label' => 'Tickets',
                                    'value' => $todaySummary['ticketsSold'],
                                    'hint' => 'Confirmed ticket sales today',
                                    'icon' => 'bi-ticket-perforated',
                                    'bg' => 'bg-cyan-50/60',
                                    'iconBg' => 'bg-cyan-100/80',
                                    'iconColor' => 'text-cyan-600',
                                    'href' => route('dashboard', array_merge($filterQuery, [
                                        'insights' => 1,
                                        'section' => 'overview',
                                    ])).'#overview',
                                    'section' => 'overview',
                                ],
                                [
                                    'label' => 'Support',
                                    'value' => $todaySummary['supportRequests'],
                                    'hint' => 'Inquiries and complaints opened today',
                                    'icon' => 'bi-headset',
                                    'bg' => 'bg-amber-50/60',
                                    'iconBg' => 'bg-amber-100/80',
                                    'iconColor' => 'text-amber-600',
                                    'href' => route('dashboard', array_merge($filterQuery, [
                                        'support' => 1,
                                        'section' => 'support',
                                    ])).'#support',
                                    'section' => 'support',
                                ],
                            ];
                        @endphp
                        <div class="grid min-w-0 flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($todayTiles as $item)
                                <a href="{{ $item['href'] }}"
                                    @if ($item['section'])
                                        @click.prevent="setSection(@js($item['section']))"
                                    @endif
                                    title="{{ $item['hint'] }}"
                                    class="btn-smooth group flex items-center gap-2 rounded-lg border border-white/50 {{ $item['bg'] }} px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 sm:px-2.5">
                                    <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $item['iconBg'] }} text-sm {{ $item['iconColor'] }} sm:flex">
                                        <i class="bi {{ $item['icon'] }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ number_format($item['value']) }}</p>
                                        <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $item['label'] }}</p>
                                    </div>
                                    <i class="bi bi-arrow-up-right hidden text-xs text-slate-400 opacity-0 transition group-hover:opacity-100 sm:inline"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- Shared filters above tabs --}}
            @php
                $organizerSelected = filled($scopeFilter['selectedOrganizerId'] ?? null);
                $organizerHasNoEvents = $organizerSelected && count($scopeFilter['events'] ?? []) === 0;
            @endphp
            <section class="dashboard-filters relative overflow-hidden rounded-2xl border border-white/50 bg-white/40 px-4 py-4 shadow-lg shadow-indigo-500/10 backdrop-blur-2xl sm:px-5">
                <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full bg-indigo-300/20 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-1/4 h-24 w-24 rounded-full bg-cyan-300/15 blur-2xl"></div>

                <div class="relative mb-3.5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-indigo-600 shadow-sm backdrop-blur-md">
                            <i class="bi bi-sliders text-sm"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold tracking-tight text-slate-900">Filters</h2>
                            <p class="text-xs text-slate-500">Shared controls · each filter applies to the tabs noted below</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <button type="button"
                            @click="toggleAutoApply()"
                            class="btn-smooth inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold backdrop-blur-md transition hover:-translate-y-0.5 hover:shadow-sm"
                            :class="autoApply
                                ? 'border-indigo-200/80 bg-indigo-50/80 text-indigo-700'
                                : 'border-white/70 bg-white/60 text-slate-600'"
                            :aria-pressed="autoApply.toString()"
                            title="When on, filters apply as soon as you change a dropdown">
                            
                        </button>
                        @if ($hasActiveFilters)
                            <a href="{{ route('dashboard') }}"
                                @click.prevent="window.location.href = @js(route('dashboard')) + '#' + section"
                                class="btn-smooth inline-flex items-center gap-1 rounded-xl border border-rose-200/70 bg-rose-50/70 px-3 py-1.5 text-xs font-semibold text-rose-700 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-rose-100/80 hover:shadow-sm">
                                <i class="bi bi-x-circle"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                <form id="admin-dashboard-filters" method="GET" action="{{ route('dashboard') }}"
                    class="relative grid gap-3 lg:grid-cols-12 lg:items-end"
                    @submit="$el.action = '{{ route('dashboard') }}' + '#' + section">
                    <input type="hidden" name="insights" value="1"
                        :disabled="!(insightsLoaded || analyticsTabs.includes(section))">
                    <input type="hidden" name="support" value="1"
                        :disabled="!(supportLoaded || section === 'support')">
                    <input type="hidden" name="section" :value="section">
                    <div class="lg:col-span-3">
                        <label for="admin_organizer" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Organizer</label>
                        <select id="admin_organizer" name="organizer"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                            <option value="">All organizers</option>
                            @foreach ($scopeFilter['organizers'] as $organizerOption)
                                <option value="{{ $organizerOption['id'] }}"
                                    @selected((int) ($scopeFilter['selectedOrganizerId'] ?? 0) === (int) $organizerOption['id'])>
                                    {{ $organizerOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] font-medium text-slate-400">Performance · Insights · Support</p>
                    </div>
                    <div class="lg:col-span-3">
                        <label for="admin_event" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event</label>
                        <select id="admin_event" name="event"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80
                                {{ $organizerHasNoEvents ? 'opacity-60' : '' }}"
                            @disabled(! $organizerSelected || $organizerHasNoEvents)>
                            <option value="">{{ $organizerHasNoEvents ? 'No events available' : 'All events' }}</option>
                            @foreach ($scopeFilter['events'] as $eventOption)
                                <option value="{{ $eventOption['id'] }}"
                                    @selected((int) ($scopeFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                    {{ $eventOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @if ($organizerHasNoEvents)
                            <p class="mt-1 text-[10px] font-medium text-amber-600">
                                This organizer has no events to filter by.
                            </p>
                        @else
                            <p class="mt-1 text-[10px] font-medium text-slate-400">Performance · Insights · Support</p>
                        @endif
                    </div>
                    <div class="lg:col-span-4">
                        <label for="admin_cro" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">CRO</label>
                        <select id="admin_cro" name="cro"
                            class="filter-control w-full rounded-xl border border-white/70 bg-white/55 px-3 py-2.5 text-sm font-medium text-slate-800 shadow-sm backdrop-blur-md transition hover:border-indigo-200 hover:bg-white/80 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200/80">
                            <option value="">All CROs</option>
                            @foreach ($croOptions as $croOption)
                                <option value="{{ $croOption['id'] }}"
                                    @selected((int) ($selectedCroId ?? 0) === (int) $croOption['id'])>
                                    {{ $croOption['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] font-medium text-slate-400">Support tab only</p>
                    </div>
                    <div class="flex gap-2 lg:col-span-2">
                        <button type="submit"
                            class="btn-smooth inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-500/30"
                            :title="autoApply ? 'Filters also apply automatically on change' : 'Apply the selected filters'">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>

                @if ($organizerHasNoEvents)
                    <div class="relative mt-3 flex items-start gap-2.5 rounded-xl border border-amber-200/70 bg-amber-50/70 px-3.5 py-3 text-sm text-amber-900">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <i class="bi bi-calendar-x"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold">No events for this organizer</p>
                            <p class="mt-0.5 text-xs text-amber-800/80">
                                Platform stats still reflect the organizer scope. Pick another organizer, or clear the filter to view all events.
                            </p>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Tabs --}}
            <nav class="admin-dashboard-tabs sticky z-30" aria-label="Dashboard sections">
                <div class="segmented-control overflow-x-auto rounded-2xl border border-white/60 bg-white/55 p-1 shadow-lg shadow-indigo-500/5 backdrop-blur-2xl sm:p-1.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex min-w-max gap-0.5 sm:gap-1">
                        @foreach ($sectionTabs as $key => $sectionTab)
                            <button type="button"
                                @click="setSection('{{ $key }}')"
                                class="btn-smooth group relative inline-flex items-center justify-center gap-1 rounded-xl px-2.5 py-2 text-[11px] font-semibold transition duration-200 sm:gap-2 sm:px-3.5 sm:py-2.5 sm:text-sm"
                                :class="section === '{{ $key }}'
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 hover:-translate-y-0.5 hover:bg-white/80 hover:text-slate-900 hover:shadow-sm'">
                                <i class="bi {{ $sectionTab['icon'] }} text-sm transition group-hover:scale-110 sm:text-base"></i>
                                <span class="max-[380px]:sr-only">{{ $sectionTab['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </nav>

            {{-- Panels --}}
            <div class="admin-dashboard-panels scroll-mt-[8.5rem] space-y-0 sm:scroll-mt-28">
                <div x-show="section === 'performance'" x-cloak x-transition.opacity.duration.200ms>
                    @include('admin.partials.dashboard-tab-performance')
                </div>
                <div x-show="section === 'support'" x-cloak x-transition.opacity.duration.200ms>
                    @if ($loadSupport && $supportReport)
                        @include('admin.partials.dashboard-tab-support')
                    @else
                        <div class="glass-panel !rounded-2xl px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                <i class="bi bi-arrow-repeat animate-spin text-xl" x-show="supportLoading"></i>
                                <i class="bi bi-headset text-xl" x-show="!supportLoading"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-800">Loading support…</p>
                            <p class="mt-1 text-xs text-slate-500">Inquiry and complaint queues load only when you open this tab.</p>
                        </div>
                    @endif
                </div>
                <div x-show="analyticsTabs.includes(section)" x-cloak x-transition.opacity.duration.200ms>
                    @if ($loadInsights && $reports)
                        <div class="mb-4 flex flex-col gap-2 rounded-xl border border-indigo-100/80 bg-indigo-50/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">Insights</p>
                                <p class="text-xs text-slate-500">Live snapshot charts for the current filters. Custom CSV/PDF downloads live in the export builder.</p>
                            </div>
                            <a href="{{ route('admin.reports') }}"
                                class="btn-smooth inline-flex shrink-0 items-center gap-1.5 self-start rounded-lg border border-white/70 bg-white/80 px-3 py-2 text-xs font-semibold text-indigo-700 shadow-sm hover:bg-white sm:self-auto">
                                <i class="bi bi-sliders"></i>
                                Build export
                            </a>
                        </div>
                        @include('admin.partials.insights')
                    @else
                        <div class="glass-panel !rounded-2xl px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                <i class="bi bi-arrow-repeat animate-spin text-xl" x-show="insightsLoading"></i>
                                <i class="bi bi-bar-chart-line text-xl" x-show="!insightsLoading"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-800">Loading insights…</p>
                            <p class="mt-1 text-xs text-slate-500">Charts and trends load only when you open this section.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Fullscreen chart modal (ops charts) --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>
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
                <div class="flex items-start justify-between gap-4 border-b border-white/50 bg-white/40 px-5 py-4 backdrop-blur-sm sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900" x-text="title"></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="description"></p>
                    </div>
                    <button type="button"
                        @click="closeChart()"
                        class="btn-smooth flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/70 bg-white/60 text-slate-500 backdrop-blur hover:bg-white hover:text-slate-800"
                        aria-label="Close fullscreen chart">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 p-4 sm:p-6">
                    <div class="h-full w-full">
                        <canvas id="adminChartFullscreen"></canvas>
                    </div>
                </div>
                <div class="border-t border-white/50 bg-white/30 px-5 py-3 text-xs text-slate-400 backdrop-blur-sm sm:px-6">
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
                    0 12px 36px -16px rgba(79, 70, 229, 0.22);
            }
            .filter-control:hover {
                box-shadow: 0 8px 20px -12px rgba(79, 70, 229, 0.28);
            }
            /* Keep sticky tabs below the fixed nav with a little breathing room on mobile. */
            .admin-dashboard-tabs {
                top: max(4.5rem, calc(env(safe-area-inset-top, 0px) + 4rem));
                margin-top: 0.25rem;
                padding-bottom: 0.35rem;
                background: linear-gradient(to bottom, rgba(248, 250, 252, 0.92), rgba(248, 250, 252, 0.72) 70%, transparent);
            }
            @media (min-width: 640px) {
                .admin-dashboard-tabs {
                    top: 5rem;
                }
            }
            .admin-dashboard-panels {
                padding-top: 0.25rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.adminDashboardData = @json($dashboard);
            window.adminReportData = @json($reports);
            (function () {
                var scrollKey = 'admin-dashboard-scroll';
                var sectionKey = 'admin-dashboard-section';
                var autoApplyKey = 'admin-dashboard-auto-apply';
                var analytics = @json($analyticsTabKeys);

                function currentScrollY() {
                    return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                }

                function restoreScroll(y) {
                    window.scrollTo(0, y);
                }

                function isAutoApplyEnabled() {
                    try {
                        var stored = localStorage.getItem(autoApplyKey);
                        if (stored === '0' || stored === 'false') return false;
                    } catch (e) {}
                    return true;
                }

                function resolveSectionFromPage() {
                    var hash = (window.location.hash || '').replace('#', '');
                    if (hash === 'insights' || hash === 'reports') return 'overview';
                    if (hash === 'admin') return 'events';
                    if (hash === 'support-reports') return 'support';
                    if (hash === 'performance' || hash === 'support' || analytics.includes(hash)) return hash;
                    try {
                        var stored = localStorage.getItem(sectionKey);
                        if (stored === 'performance' || stored === 'support' || analytics.includes(stored)) {
                            return stored;
                        }
                    } catch (e) {}
                    return 'performance';
                }

                function prepareAndSubmit(form, changedSelect) {
                    try {
                        sessionStorage.setItem(scrollKey, String(currentScrollY()));
                    } catch (e) {}

                    if (changedSelect && changedSelect.name === 'organizer') {
                        var eventSelect = form.querySelector('select[name="event"]');
                        if (eventSelect) eventSelect.selectedIndex = 0;
                    }

                    var section = resolveSectionFromPage();

                    var insightsInput = form.querySelector('input[name="insights"]');
                    if (insightsInput) {
                        var wantsInsights = @js($loadInsights) || analytics.includes(section);
                        insightsInput.disabled = !wantsInsights;
                    }

                    var supportInput = form.querySelector('input[name="support"]');
                    if (supportInput) {
                        var wantsSupport = @js($loadSupport) || section === 'support';
                        supportInput.disabled = !wantsSupport;
                    }

                    var sectionInput = form.querySelector('input[name="section"]');
                    if (sectionInput) sectionInput.value = section;

                    try {
                        localStorage.setItem(sectionKey, section);
                    } catch (e) {}

                    form.action = @js(route('dashboard')) + '#' + section;
                    form.submit();
                }

                try {
                    var saved = sessionStorage.getItem(scrollKey);
                    if (saved !== null) {
                        sessionStorage.removeItem(scrollKey);
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
                    var form = document.getElementById('admin-dashboard-filters');
                    if (!form) return;

                    form.querySelectorAll('select[name="organizer"], select[name="event"], select[name="cro"]').forEach(function (select) {
                        select.addEventListener('change', function () {
                            if (! isAutoApplyEnabled()) return;
                            prepareAndSubmit(form, this);
                        });
                    });

                    form.addEventListener('submit', function () {
                        try {
                            sessionStorage.setItem(scrollKey, String(currentScrollY()));
                            localStorage.setItem(sectionKey, resolveSectionFromPage());
                        } catch (e) {}
                    });
                });
            })();
        </script>
        @vite(['resources/js/admin-dashboard.js', 'resources/js/admin-reports.js'])
    @endpush
</x-app-layout>

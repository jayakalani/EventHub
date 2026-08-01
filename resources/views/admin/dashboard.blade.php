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
            'event' => 'Support for '.$supportScopeFilter['selectedEventName'],
            'cro' => 'Assigned to '.$supportScopeFilter['selectedCroName'],
            default => 'CRO module',
        };
    @endphp

    <div class="admin-dashboard relative isolate overflow-hidden py-5 sm:py-6"
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
        }"
        @keydown.escape.window="if (open) closeChart()">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- 1. Hero --}}
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
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                            <x-dashboard-export-pdf
                                route="admin.dashboard.export.pdf"
                                :params="request()->only([
                                    'organizer',
                                    'event',
                                    'payment_organizer',
                                    'payment_event',
                                    'support_cro',
                                    'support_event',
                                ])"
                                :charts="[
                                    ['canvasId' => 'dashboardUserGrowthChart', 'title' => 'User Growth'],
                                    ['canvasId' => 'dashboardUserDistributionChart', 'title' => 'User Distribution'],
                                    ['canvasId' => 'dashboardRevenueChart', 'title' => 'Revenue Trend'],
                                    ['canvasId' => 'dashboardTicketSalesChart', 'title' => 'Ticket Sales'],
                                    ['canvasId' => 'dashboardPaymentOverviewChart', 'title' => 'Payment Overview'],
                                    ['canvasId' => 'dashboardEventsByCategoryChart', 'title' => 'Events by Category'],
                                ]"
                            />
                            <a href="{{ route('admin.reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-graph-up-arrow"></i>
                                Full Reports
                            </a>
                            <a href="{{ route('admin.users') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-people"></i>
                                Users
                            </a>
                            <a href="{{ route('admin.support-reports') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-headset"></i>
                                Support
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
                                ['label' => 'Organizers', 'value' => $todaySummary['newOrganizers'], 'icon' => 'bi-person-badge', 'bg' => 'bg-indigo-50/60', 'iconBg' => 'bg-indigo-100/80', 'iconColor' => 'text-indigo-600'],
                                ['label' => 'Events', 'value' => $todaySummary['newEvents'], 'icon' => 'bi-calendar-event', 'bg' => 'bg-blue-50/60', 'iconBg' => 'bg-blue-100/80', 'iconColor' => 'text-blue-600'],
                                ['label' => 'Tickets', 'value' => $todaySummary['ticketsSold'], 'icon' => 'bi-ticket-perforated', 'bg' => 'bg-cyan-50/60', 'iconBg' => 'bg-cyan-100/80', 'iconColor' => 'text-cyan-600'],
                                ['label' => 'Support', 'value' => $todaySummary['supportRequests'], 'icon' => 'bi-headset', 'bg' => 'bg-amber-50/60', 'iconBg' => 'bg-amber-100/80', 'iconColor' => 'text-amber-600'],
                            ] as $item)
                                <div class="btn-smooth flex items-center gap-2 rounded-lg border border-white/50 {{ $item['bg'] }} px-2 py-1.5 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 sm:px-2.5">
                                    <span class="hidden h-7 w-7 items-center justify-center rounded-md {{ $item['iconBg'] }} text-sm {{ $item['iconColor'] }} sm:flex">
                                        <i class="bi {{ $item['icon'] }}"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ number_format($item['value']) }}</p>
                                        <p class="truncate text-[10px] font-medium text-slate-500 sm:text-xs">{{ $item['label'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative mt-3 flex flex-wrap gap-1.5">
                        @foreach ([
                            ['label' => 'Users', 'route' => route('admin.users'), 'icon' => 'bi-people'],
                            ['label' => 'Categories', 'route' => route('admin.event-categories'), 'icon' => 'bi-tags'],
                            ['label' => 'Reports', 'route' => route('admin.reports'), 'icon' => 'bi-bar-chart'],
                            ['label' => 'Support', 'route' => route('admin.support-reports'), 'icon' => 'bi-headset'],
                            ['label' => 'Audit Logs', 'route' => route('admin.audit-logs'), 'icon' => 'bi-journal-text'],
                        ] as $shortcut)
                            <a href="{{ $shortcut['route'] }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/45 px-2.5 py-1 text-[11px] font-semibold text-slate-600 backdrop-blur hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white/80 hover:text-indigo-700 hover:shadow-sm">
                                <i class="bi {{ $shortcut['icon'] }}"></i>
                                {{ $shortcut['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

{{-- 2. Platform snapshot --}}
            <section class="space-y-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-slate-900">Platform snapshot</h2>
                        <p class="text-xs text-slate-500">{{ $scopeCaption }}</p>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}"
                        class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-end lg:w-auto">
                        @if ($paymentScopeFilter['selectedOrganizerId'])
                            <input type="hidden" name="payment_organizer" value="{{ $paymentScopeFilter['selectedOrganizerId'] }}">
                        @endif
                        @if ($paymentScopeFilter['selectedEventId'])
                            <input type="hidden" name="payment_event" value="{{ $paymentScopeFilter['selectedEventId'] }}">
                        @endif
                        @if ($supportScopeFilter['selectedCroId'])
                            <input type="hidden" name="support_cro" value="{{ $supportScopeFilter['selectedCroId'] }}">
                        @endif
                        @if ($supportScopeFilter['selectedEventId'])
                            <input type="hidden" name="support_event" value="{{ $supportScopeFilter['selectedEventId'] }}">
                        @endif
                        <label for="admin_organizer" class="sr-only">Filter by organizer</label>
                        <select
                            id="admin_organizer"
                            name="organizer"
                            class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500 sm:w-56"
                        >
                            <option value="">All Events</option>
                            @foreach ($scopeFilter['organizers'] as $organizerOption)
                                <option value="{{ $organizerOption['id'] }}"
                                    @selected((int) ($scopeFilter['selectedOrganizerId'] ?? 0) === (int) $organizerOption['id'])>
                                    {{ $organizerOption['name'] }}
                                </option>
                            @endforeach
                        </select>

                        @if ($scopeFilter['selectedOrganizerId'])
                            <label for="admin_event" class="sr-only">Filter by event</label>
                            <select
                                id="admin_event"
                                name="event"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500 sm:w-56"
                            >
                                <option value="">All Events</option>
                                @foreach ($scopeFilter['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($scopeFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </form>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600'],
                                'blue' => ['top' => 'border-t-blue-500', 'iconBg' => 'bg-blue-100/70', 'iconText' => 'text-blue-600'],
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                                default => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                                    <p class="mt-1 text-xs font-medium {{ $kpi['subClass'] }}">{{ $kpi['sub'] }}</p>
                                </div>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Active', 'value' => $platformAnalytics['active'], 'color' => 'text-cyan-700', 'bg' => 'bg-cyan-50/55', 'border' => 'border-cyan-200/50'],
                    ['label' => 'Upcoming', 'value' => $platformAnalytics['upcoming'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/55', 'border' => 'border-amber-200/50'],
                    ['label' => 'Completed', 'value' => $platformAnalytics['completed'], 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/55', 'border' => 'border-emerald-200/50'],
                    ['label' => 'Cancelled', 'value' => $platformAnalytics['cancelled'], 'color' => 'text-rose-700', 'bg' => 'bg-rose-50/55', 'border' => 'border-rose-200/50'],
                ] as $item)
                    <div class="btn-smooth rounded-xl border {{ $item['border'] }} {{ $item['bg'] }} px-3 py-3 backdrop-blur-md hover:-translate-y-1 hover:bg-white/70 hover:shadow-md sm:px-4">
                        <p class="text-xs font-medium text-slate-500">{{ $item['label'] }} Events</p>
                        <p class="mt-0.5 text-xl font-bold {{ $item['color'] }} sm:text-2xl">{{ number_format($item['value']) }}</p>
                    </div>
                @endforeach
            </div>
            </section>

            {{-- 3. Analytics + calendar --}}
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-stretch">
                <div class="xl:col-span-8">
    {{-- 3. Analytics --}}
                <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Analytics</h2>
                            <p class="text-sm text-slate-500">Click a chart to open fullscreen</p>
                        </div>
                        <a href="{{ route('admin.reports') }}" class="btn-smooth text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            Open full reports →
                        </a>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <x-report-chart-card
                            class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                            title="User Growth"
                            description="New registrations over time"
                            canvas-id="dashboardUserGrowthChart"
                            expand-key="userGrowth"
                        />
                        <x-report-chart-card
                            class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                            title="Revenue Trend"
                            description="Monthly platform revenue"
                            canvas-id="dashboardRevenueChart"
                            expand-key="revenue"
                        />
                        <x-report-chart-card
                            class="glass-card !shadow-none border-white/50 hover:!-translate-y-1"
                            title="Ticket Sales"
                            description="Weekly confirmed sales"
                            canvas-id="dashboardTicketSalesChart"
                            expand-key="ticketSales"
                        />
                    </div>
                </section>
                </div>
                <div class="xl:col-span-4">
{{-- Mini calendar --}}
                <section class="h-full">
                    <x-dashboard-mini-calendar :calendar="$dashboard['miniCalendar']" class="h-full" />
                </section>
                </div>
            </div>

{{-- 4. Performance + payments --}}
            <div class="grid gap-4 lg:grid-cols-5">
                <section class="glass-card lg:col-span-3 overflow-hidden !p-0">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Organizer Performance</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Top organizers by revenue</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Events</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Revenue</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Tickets</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($organizerPerformance as $index => $organizer)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold backdrop-blur-sm
                                                    {{ $index === 0 ? 'bg-amber-100/80 text-amber-700' : ($index === 1 ? 'bg-slate-200/80 text-slate-700' : ($index === 2 ? 'bg-orange-100/80 text-orange-700' : 'bg-white/60 text-slate-600')) }}">
                                                    {{ $index + 1 }}
                                                </span>
                                                <span class="font-semibold text-slate-900">{{ $organizer['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['events']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700 sm:px-5">{{ $organizer['revenueLabel'] }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['ticketsSold']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="glass-card lg:col-span-2 p-4 sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-900">Payment Overview</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $paymentScopeCaption }}</p>
                        </div>
                        <button type="button"
                            @click="openChart('payments', @js('Payment Overview'), @js('Successful, pending, refunded, and failed payments'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-emerald-50/70 text-emerald-600 backdrop-blur hover:bg-emerald-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View Payment Overview fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="mb-3 flex flex-col gap-2">
                        @if ($scopeFilter['selectedOrganizerId'])
                            <input type="hidden" name="organizer" value="{{ $scopeFilter['selectedOrganizerId'] }}">
                        @endif
                        @if ($scopeFilter['selectedEventId'])
                            <input type="hidden" name="event" value="{{ $scopeFilter['selectedEventId'] }}">
                        @endif
                        @if ($supportScopeFilter['selectedCroId'])
                            <input type="hidden" name="support_cro" value="{{ $supportScopeFilter['selectedCroId'] }}">
                        @endif
                        @if ($supportScopeFilter['selectedEventId'])
                            <input type="hidden" name="support_event" value="{{ $supportScopeFilter['selectedEventId'] }}">
                        @endif
                        <label for="payment_organizer" class="sr-only">Filter payments by organizer</label>
                        <select
                            id="payment_organizer"
                            name="payment_organizer"
                            class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">All Events</option>
                            @foreach ($paymentScopeFilter['organizers'] as $organizerOption)
                                <option value="{{ $organizerOption['id'] }}"
                                    @selected((int) ($paymentScopeFilter['selectedOrganizerId'] ?? 0) === (int) $organizerOption['id'])>
                                    {{ $organizerOption['name'] }}
                                </option>
                            @endforeach
                        </select>

                        @if ($paymentScopeFilter['selectedOrganizerId'])
                            <label for="payment_event" class="sr-only">Filter payments by event</label>
                            <select
                                id="payment_event"
                                name="payment_event"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">All Events</option>
                                @foreach ($paymentScopeFilter['events'] as $eventOption)
                                    <option value="{{ $eventOption['id'] }}"
                                        @selected((int) ($paymentScopeFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                        {{ $eventOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </form>

                    <button type="button"
                        @click="openChart('payments', @js('Payment Overview'), @js('Successful, pending, refunded, and failed payments'))"
                        class="btn-smooth mx-auto block h-40 w-full max-w-[200px] cursor-pointer rounded-xl hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        aria-label="Open Payment Overview fullscreen">
                        <canvas id="dashboardPaymentOverviewChart" class="pointer-events-none"></canvas>
                    </button>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        @foreach ([
                            ['Successful', $payments['completed'], 'text-emerald-700', 'bg-emerald-50/55 border-emerald-200/50'],
                            ['Pending', $payments['pending'], 'text-amber-700', 'bg-amber-50/55 border-amber-200/50'],
                            ['Refunded', $payments['refunded'], 'text-purple-700', 'bg-purple-50/55 border-purple-200/50'],
                            ['Failed', $payments['failed'], 'text-rose-700', 'bg-rose-50/55 border-rose-200/50'],
                        ] as [$label, $value, $color, $bg])
                            <div class="btn-smooth rounded-xl border {{ $bg }} px-2.5 py-2.5 text-center backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm">
                                <p class="text-[11px] font-medium text-slate-500">{{ $label }}</p>
                                <p class="mt-0.5 text-lg font-bold {{ $color }}">{{ number_format($value) }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- 5. Users --}}
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">User Distribution</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Platform role composition</p>
                        </div>
                        <button type="button"
                            @click="openChart('userDistribution', @js('User Distribution'), @js('Platform role composition'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 backdrop-blur hover:bg-indigo-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View User Distribution fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <button type="button"
                        @click="openChart('userDistribution', @js('User Distribution'), @js('Platform role composition'))"
                        class="btn-smooth block h-56 w-full cursor-pointer rounded-xl text-left hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-64"
                        aria-label="Open User Distribution fullscreen">
                        <canvas id="dashboardUserDistributionChart" class="pointer-events-none"></canvas>
                    </button>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @foreach ($rolePercents as $role)
                            <div class="btn-smooth rounded-lg border border-white/60 bg-white/40 px-2.5 py-2 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70">
                                <p class="truncate text-[11px] font-medium text-slate-500">{{ $role['label'] }}</p>
                                <p class="text-sm font-bold text-slate-900">{{ $role['percent'] }}% <span class="font-medium text-slate-400">({{ number_format($role['count']) }})</span></p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="glass-card overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Registrations</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Latest users on the platform</p>
                        </div>
                        <a href="{{ route('admin.users') }}" class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            View All →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Name</th>
                                    <th class="px-4 py-2.5 sm:px-5">Role</th>
                                    <th class="px-4 py-2.5 sm:px-5">Registered</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse (collect($users['recent'])->take(6) as $recentUser)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $recentUser['name'] }}</td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <span class="inline-flex rounded-md border border-white/60 bg-white/50 px-2 py-0.5 text-xs font-semibold text-slate-700 backdrop-blur-sm">
                                                {{ $recentUser['role'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-500">{{ $recentUser['joined'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-10 text-center text-slate-500">No recent registrations.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- 6. Support + categories --}}
            <div class="grid gap-4 lg:grid-cols-5">
                <section class="glass-card lg:col-span-2 overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-900">Support Overview</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $supportScopeCaption }}</p>
                        </div>
                        <a href="{{ route('admin.support-reports') }}" class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            View all →
                        </a>
                    </div>
                    <div class="space-y-2.5 p-4 sm:p-5">
                        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-2">
                            @if ($scopeFilter['selectedOrganizerId'])
                                <input type="hidden" name="organizer" value="{{ $scopeFilter['selectedOrganizerId'] }}">
                            @endif
                            @if ($scopeFilter['selectedEventId'])
                                <input type="hidden" name="event" value="{{ $scopeFilter['selectedEventId'] }}">
                            @endif
                            @if ($paymentScopeFilter['selectedOrganizerId'])
                                <input type="hidden" name="payment_organizer" value="{{ $paymentScopeFilter['selectedOrganizerId'] }}">
                            @endif
                            @if ($paymentScopeFilter['selectedEventId'])
                                <input type="hidden" name="payment_event" value="{{ $paymentScopeFilter['selectedEventId'] }}">
                            @endif
                            <label for="support_cro" class="sr-only">Filter support by CRO</label>
                            <select
                                id="support_cro"
                                name="support_cro"
                                class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">All Events</option>
                                @foreach ($supportScopeFilter['cros'] as $croOption)
                                    <option value="{{ $croOption['id'] }}"
                                        @selected((int) ($supportScopeFilter['selectedCroId'] ?? 0) === (int) $croOption['id'])>
                                        {{ $croOption['name'] }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($supportScopeFilter['selectedCroId'])
                                <label for="support_event" class="sr-only">Filter support by event</label>
                                <select
                                    id="support_event"
                                    name="support_event"
                                    class="block w-full rounded-xl border-white/70 bg-white/60 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="">All Events</option>
                                    @foreach ($supportScopeFilter['events'] as $eventOption)
                                        <option value="{{ $eventOption['id'] }}"
                                            @selected((int) ($supportScopeFilter['selectedEventId'] ?? 0) === (int) $eventOption['id'])>
                                            {{ $eventOption['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </form>

                        @foreach ([
                            ['label' => 'Open Inquiries', 'value' => $support['openInquiries'], 'icon' => 'bi-chat-left-text', 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/55', 'border' => 'border-amber-200/50', 'iconBg' => 'bg-amber-100/80'],
                            ['label' => 'Open Complaints', 'value' => $support['openComplaints'], 'icon' => 'bi-exclamation-triangle', 'color' => 'text-rose-700', 'bg' => 'bg-rose-50/55', 'border' => 'border-rose-200/50', 'iconBg' => 'bg-rose-100/80'],
                            ['label' => 'Resolved Today', 'value' => $support['resolvedToday'], 'icon' => 'bi-check2-circle', 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/55', 'border' => 'border-emerald-200/50', 'iconBg' => 'bg-emerald-100/80'],
                        ] as $item)
                            <div class="btn-smooth flex items-center gap-3 rounded-xl border {{ $item['border'] }} {{ $item['bg'] }} px-3 py-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $item['iconBg'] }} backdrop-blur-sm">
                                    <i class="bi {{ $item['icon'] }} {{ $item['color'] }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-slate-500">{{ $item['label'] }}</p>
                                    <p class="text-xl font-bold {{ $item['color'] }}">{{ number_format($item['value']) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="lg:col-span-3">
                    <x-report-chart-card
                        class="glass-card h-full"
                        title="Event Categories"
                        description="Number of events by category"
                        canvas-id="dashboardEventsByCategoryChart"
                        expand-key="eventsByCategory"
                    />
                </section>
            </div>

            

        </div>

        {{-- Fullscreen chart modal --}}
        <div x-show="open"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-md" @click="closeChart()"></div>

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
        </style>
    @endpush

    @push('scripts')
        <script>
            window.adminDashboardData = @json($dashboard);
            (function () {
                var key = 'admin-dashboard-scroll';
                var parentChild = {
                    organizer: '#admin_event',
                    payment_organizer: '#payment_event',
                    support_cro: '#support_event',
                };

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
                    document.querySelectorAll(
                        '.admin-dashboard select[name="organizer"],' +
                        '.admin-dashboard select[name="event"],' +
                        '.admin-dashboard select[name="payment_organizer"],' +
                        '.admin-dashboard select[name="payment_event"],' +
                        '.admin-dashboard select[name="support_cro"],' +
                        '.admin-dashboard select[name="support_event"]'
                    ).forEach(function (select) {
                        select.addEventListener('change', function () {
                            try {
                                sessionStorage.setItem(key, String(currentScrollY()));
                            } catch (e) {}

                            var childSelector = parentChild[this.name];
                            if (childSelector && this.form) {
                                var eventSelect = this.form.querySelector(childSelector);
                                if (eventSelect) eventSelect.selectedIndex = 0;
                            }

                            if (this.form) this.form.submit();
                        });
                    });
                });
            })();
        </script>
        @vite('resources/js/admin-dashboard.js')
    @endpush
</x-app-layout>
